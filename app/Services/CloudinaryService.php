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
            $toSign = "folder={$folder}&public_id={$publicId}&timestamp={$timestamp}{$this->apiSecret}";
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
