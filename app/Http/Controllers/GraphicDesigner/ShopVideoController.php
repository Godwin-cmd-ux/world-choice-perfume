<?php

namespace App\Http\Controllers\GraphicDesigner;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use App\Services\CompanySettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The background clip on the public shop page.
 *
 * The clip used to be a file name written into the shop blade, so only a
 * developer could change it. The URL now lives in company_settings, which
 * lets the graphics designer upload a replacement or remove the video from
 * their own page.
 *
 * Three states are kept deliberately distinct:
 *   - no setting row    -> the bundled public/videos/reef_33.mp4 plays
 *   - a URL in the row  -> that uploaded clip plays
 *   - an empty string   -> no video at all (the designer removed it)
 */
class ShopVideoController extends Controller
{
    private const URL_KEY = 'shop_hero_video_url';

    private const PUBLIC_ID_KEY = 'shop_hero_video_public_id';

    public function index()
    {
        $url = CompanySettingService::get(self::URL_KEY);

        // A missing row means the site is still on the clip that ships with
        // the app; once the designer has acted the row exists (possibly empty).
        $previewUrl = $url === null ? asset('videos/reef_33.mp4') : $url;

        return view('graphic-designer.shop-video', [
            'videoUrl' => $url,
            'usingBundledClip' => $url === null,
            'hasVideo' => $url !== null && $url !== '',
            'previewUrl' => $previewUrl,
            'previewType' => $this->videoType($previewUrl),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/webm|max:49152',
        ], [
            'video.mimetypes' => 'The clip must be an MP4 or WebM video.',
            'video.max' => 'The clip must be 48 MB or smaller. Export a compressed web version instead of the master file.',
        ]);

        $cloudinary = new CloudinaryService;
        $uploaded = $cloudinary->uploadVideo($request->file('video'));

        if ($uploaded === null) {
            return back()->with(
                'error',
                'The clip could not be uploaded. '.($cloudinary->lastError() ?? 'Please try again.')
            );
        }

        $previousId = CompanySettingService::get(self::PUBLIC_ID_KEY) ?? '';

        CompanySettingService::set(self::URL_KEY, $uploaded['url']);
        CompanySettingService::set(self::PUBLIC_ID_KEY, $uploaded['public_id']);

        // The replaced clip is no longer referenced anywhere, so remove it
        // rather than letting every attempted version pile up in Cloudinary.
        if ($previousId !== '' && $previousId !== $uploaded['public_id']) {
            if (! $cloudinary->deleteVideo($previousId)) {
                Log::warning('Shop video: old clip could not be deleted', [
                    'public_id' => $previousId,
                    'error' => $cloudinary->lastError(),
                ]);
            }
        }

        return redirect()->route('graphic-designer.shop-video.index')
            ->with('success', 'Shop header video updated. The shop page now plays the new clip.');
    }

    public function destroy()
    {
        $publicId = CompanySettingService::get(self::PUBLIC_ID_KEY) ?? '';

        if ($publicId !== '') {
            $cloudinary = new CloudinaryService;

            if (! $cloudinary->deleteVideo($publicId)) {
                Log::warning('Shop video: uploaded clip could not be deleted', [
                    'public_id' => $publicId,
                    'error' => $cloudinary->lastError(),
                ]);
            }
        }

        // An empty value, not a missing row: "never set" must keep playing the
        // bundled clip, while "removed" has to leave the header blank.
        CompanySettingService::set(self::URL_KEY, '');
        CompanySettingService::set(self::PUBLIC_ID_KEY, '');

        return redirect()->route('graphic-designer.shop-video.index')
            ->with('success', 'Shop header video removed. The shop page no longer plays a clip.');
    }

    private function videoType(string $url): string
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return str_ends_with($path, '.webm') ? 'video/webm' : 'video/mp4';
    }
}
