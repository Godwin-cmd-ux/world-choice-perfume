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
    private string $rawUploadUrl;
    private ?string $lastError = null;

    public function __construct()
    {
        $this->cloudName = (string) config('services.cloudinary.cloud_name');
        $this->apiKey = (string) config('services.cloudinary.api_key');
        $this->apiSecret = (string) config('services.cloudinary.api_secret');
        $this->uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload";
        $this->rawUploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/raw/upload";
    }

    /**
     * Why the last upload or delete failed, for callers that must report the
     * reason instead of quietly carrying on (the nightly backup does).
     */
    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Upload a non-image file (the nightly .sql backups) as a raw resource.
     *
     * A public_id containing slashes is what creates the folders in Cloudinary:
     * "4ftdshm54xvhks/backup_2026-10-30/seed_2026-10-30.sql" lands in that
     * folder. `overwrite` means a re-run on the same day replaces the file
     * instead of failing with "already exists".
     *
     * Returns the secure URL, or null with lastError() set.
     */
    public function uploadRaw(string $contents, string $publicId, string $contentType = 'application/octet-stream'): ?string
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $timestamp = now()->timestamp;

            $signPairs = [
                'overwrite' => 'true',
                'public_id' => $publicId,
                'timestamp' => (string) $timestamp,
            ];
            $signature = sha1($this->buildSignatureString($signPairs) . $this->apiSecret);

            $response = Http::timeout(180)
                ->attach('file', $contents, basename($publicId), ['Content-Type' => $contentType])
                ->post($this->rawUploadUrl, [
                    'api_key' => $this->apiKey,
                    'timestamp' => $timestamp,
                    'public_id' => $publicId,
                    'overwrite' => 'true',
                    'signature' => $signature,
                ]);

            if ($response->successful()) {
                return $response->json('secure_url');
            }

            $this->lastError = (string) ($response->json('error.message') ?? ('HTTP ' . $response->status()));

            return null;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            report($e);

            return null;
        }
    }

    /**
     * Every raw file under a prefix, so the Backups page can show what is
     * actually in Cloudinary rather than only what this app remembers writing.
     *
     * Returns a list of ['public_id', 'bytes', 'url', 'created_at'], or null with
     * lastError() set. Follows Cloudinary's cursor so a long history is not cut
     * off at the first page.
     *
     * @return list<array{public_id: string, bytes: int, url: string, created_at: ?string}>|null
     */
    public function listRaw(string $prefix, int $maxResults = 500): ?array
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            return null;
        }

        $files = [];
        $cursor = null;

        try {
            // 20 pages of 500 is 10,000 backups: far past any real history, and
            // a hard stop so a broken cursor cannot loop forever.
            for ($page = 0; $page < 20; $page++) {
                $query = ['prefix' => $prefix, 'max_results' => $maxResults];

                if ($cursor !== null) {
                    $query['next_cursor'] = $cursor;
                }

                // The admin API wants both halves in the path: /resources/raw/upload.
                // /resources/raw alone is answered with "Missing required parameter - type".
                $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                    ->timeout(60)
                    ->get("https://api.cloudinary.com/v1_1/{$this->cloudName}/resources/raw/upload", $query);

                if (! $response->successful()) {
                    $this->lastError = (string) ($response->json('error.message') ?? ('HTTP ' . $response->status()));

                    return $files === [] ? null : $files;
                }

                foreach ((array) $response->json('resources', []) as $resource) {
                    $publicId = (string) ($resource['public_id'] ?? '');

                    if ($publicId === '') {
                        continue;
                    }

                    $files[] = [
                        'public_id' => $publicId,
                        'bytes' => (int) ($resource['bytes'] ?? 0),
                        'url' => (string) ($resource['secure_url'] ?? $resource['url'] ?? ''),
                        'created_at' => $resource['created_at'] ?? null,
                    ];
                }

                $cursor = $response->json('next_cursor');

                if ($cursor === null || $cursor === '') {
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            report($e);

            return $files === [] ? null : $files;
        }

        return $files;
    }

    /**
     * Delete a raw file (a backup .sql). Raw files live under their own
     * resource type, so the image destroy endpoint does not reach them.
     *
     * Returns false with lastError() set when Cloudinary did not confirm it.
     */
    public function deleteRaw(string $publicId): bool
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $timestamp = now()->timestamp;

            $response = Http::timeout(60)->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/raw/destroy", [
                'public_id' => $publicId,
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'invalidate' => 'true',
                'signature' => $this->generateSignature($publicId, $timestamp),
            ]);

            // "not found" is the answer for a file that is already gone, which
            // is what the caller wanted; refusing it would leave the database
            // row listing a file nobody can delete.
            if ($response->successful() && in_array($response->json('result'), ['ok', 'not found'], true)) {
                return true;
            }

            $this->lastError = (string) ($response->json('error.message')
                ?? $response->json('result')
                ?? ('HTTP ' . $response->status()));

            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            report($e);

            return false;
        }
    }

    private function isConfigured(): bool
    {
        if ($this->cloudName === '' || $this->apiKey === '' || $this->apiSecret === '') {
            $this->lastError = 'Cloudinary is not configured (CLOUDINARY_CLOUD_NAME / API_KEY / API_SECRET are empty).';

            return false;
        }

        return true;
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
