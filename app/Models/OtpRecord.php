<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtpRecord extends Model
{
    protected $fillable = ['user_id', 'email', 'otp', 'type', 'expires_at', 'used'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generate(string $email, string $type = 'registration', ?int $userId = null): self
    {
        // Invalidate previous OTPs
        static::where('email', $email)
            ->where('type', $type)
            ->where('used', false)
            ->update(['used' => true]);

        return static::create([
            'user_id' => $userId,
            'email' => $email,
            'otp' => str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function verify(string $otp): bool
    {
        if ($this->used || $this->expires_at->isPast() || $this->otp !== $otp) {
            return false;
        }
        $this->update(['used' => true]);
        return true;
    }
}
