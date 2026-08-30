<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'role', 'status',
        'branch_id', 'profile_picture', 'company_secret_code', 'otp_verified',
        'supabase_id',
    ];

    protected $hidden = [
        'password', 'remember_token', 'company_secret_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_verified' => 'boolean',
        ];
    }

    // Scopes
    public function scopeSuperAdmin($query) { return $query->where('role', 'super_admin'); }
    public function scopeBranchAdmin($query) { return $query->where('role', 'branch_admin'); }
    public function scopeCashiers($query) { return $query->where('role', 'cashier'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeActive($query) { return $query->whereIn('status', ['active', 'approved']); }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashierSales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function cashierExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'user_id');
    }

    public function cashierAccounts(): HasMany
    {
        return $this->hasMany(CashierAccount::class, 'cashier_id');
    }

    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'cashier_id');
    }

    public function stockEntries(): HasMany
    {
        return $this->hasMany(BranchStock::class, 'entered_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications_records(): HasMany
    {
        return $this->hasMany(NotificationRecord::class);
    }

    // Helpers
    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isBranchAdmin(): bool { return $this->role === 'branch_admin'; }
    public function isCashier(): bool { return $this->role === 'cashier'; }
    public function isApproved(): bool { return in_array($this->status, ['active', 'approved']); }
    public function isPending(): bool { return $this->status === 'pending'; }
}
