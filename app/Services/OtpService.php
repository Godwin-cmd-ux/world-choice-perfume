<?php

namespace App\Services;

use App\Mail\OtpMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function generate(string $email, string $type = 'registration', ?int $userId = null, string $userName = 'User'): array
    {
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $expiresAt = now()->addMinutes(10);

        // Store OTP in Supabase
        $record = $this->supabase->insert('otp_records', [
            'email' => $email,
            'otp' => $otp,
            'type' => $type,
            'user_id' => $userId,
            'expires_at' => $expiresAt->toIso8601String(),
            'used' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        // Determine the purpose label
        $purposeLabel = match($type) {
            'registration' => 'Registration',
            'password_reset' => 'Password Reset',
            default => ucfirst($type),
        };

        // Send OTP via email using Mailable
        Mail::to($email)->send(new OtpMail(
            otpCode: $otp,
            purpose: $purposeLabel,
            userName: $userName,
        ));

        return $record ?? ['otp' => $otp, 'email' => $email];
    }

    public function verify(string $email, string $otp, string $type = 'registration'): bool
    {
        // Find the OTP record in Supabase
        $records = $this->supabase->query('otp_records', [
            'email' => "eq.{$email}",
            'type' => "eq.{$type}",
            'used' => 'eq.false',
            'order' => 'created_at.desc',
            'limit' => 1,
        ]);

        if (empty($records)) {
            return false;
        }

        $record = $records[0];

        // Check expiry
        if (isset($record['expires_at']) && Carbon::parse($record['expires_at'])->isPast()) {
            return false;
        }

        // Check OTP match
        if (($record['otp'] ?? '') !== $otp) {
            return false;
        }

        // Mark as used
        $this->supabase->update('otp_records', [
            'used' => true,
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $record['id']]);

        return true;
    }
}
