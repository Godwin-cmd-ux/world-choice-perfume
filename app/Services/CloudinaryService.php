<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class CloudinaryService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;
    private string $uploadUrl;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
        $this->uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload";
    }

    public function upload(UploadedFile $file, string $folder = 'world-choice-perfumes'): ?string
    {
        try {
            $timestamp = now()->timestamp;
            $publicId = $file->getClientOriginalName() . '_' . $timestamp;

            $signPairs = [
                'folder'    => $folder,
                'public_id' => $publicId,
                'timestamp' => (string) $timestamp,
            ];
            $toSign = $this->buildSignatureString($signPairs) . $this->apiSecret;
            $signature = sha1($toSign);

            $response = Http::attach(
                'file', file_get_contents($file->getPathname()), $file->getClientOriginalName()
            )->post($this->uploadUrl, [
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'public_id' => $publicId,
                'folder' => $folder,
                'signature' => $signature,
            ]);

            if ($response->successful()) {
                return $response->json('secure_url');
            }

            return null;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Upload an image that already exists at a public URL into Cloudinary.
     *
     * This is intentionally a separate method from the file-based upload above.
     * It is used only by the one-off product-image import script and is not
     * exposed through any HTTP controller, so user-uploaded content never flows
     * through it.
     *
     * Cloudinary validates the remote source and returns the final image URL,
     * so the image actually needs to be a real, downloadable image for the
     * upload to succeed.
     *
     * When the source is a remote URL (the "file" param), Cloudinary excludes
     * "file" from the signature but still includes any other params we send
     * (for example "overwrite"). To avoid signature mismatches across SDK
     * versions and endpoints, we include only the params that are part of the
     * canonical signed set here: folder, public_id, timestamp, and overwrite.
     */
    public function uploadFromUrl(string $url, string $folder = 'products', string $publicId = null): ?string
    {
        try {
            $timestamp = now()->timestamp;
            $publicId = $publicId ?? ('import_' . $timestamp . '_' . md5($url));

            // Signed params for a URL-based upload: exclude "file".
            $signPairs = [
                'folder'    => $folder,
                'public_id' => $publicId,
                'timestamp' => (string) $timestamp,
                'overwrite' => 'true',
            ];
            $toSign = $this->buildSignatureString($signPairs) . $this->apiSecret;
            $signature = sha1($toSign);

            $response = Http::timeout(60)->post($this->uploadUrl, [
                'file'      => $url,
                'api_key'   => $this->apiKey,
                'timestamp' => $timestamp,
                'public_id' => $publicId,
                'folder'    => $folder,
                'signature' => $signature,
                'overwrite' => 'true',
            ]);

            if ($response->successful()) {
                return $response->json('secure_url');
            }

            return null;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Build the alphabetically-sorted, &-joined param string Cloudinary
     * expects when computing the upload signature.
     */
    private function buildSignatureString(array $pairs): string
    {
        $keys = array_keys($pairs);
        sort($keys, SORT_STRING);
        $parts = [];
        foreach ($keys as $k) {
            $parts[] = $k . '=' . $pairs[$k];
        }
        return implode('&', $parts);
    }

    public function delete(string $publicId): bool
    {
        try {
            $timestamp = now()->timestamp;
            $signature = $this->generateSignature($publicId, $timestamp);

            $response = Http::post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy", [
                'public_id' => $publicId,
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    private function generateSignature(string $publicId, int $timestamp): string
    {
        $toSign = "public_id={$publicId}&timestamp={$timestamp}{$this->apiSecret}";
        return sha1($toSign);
    }
}
