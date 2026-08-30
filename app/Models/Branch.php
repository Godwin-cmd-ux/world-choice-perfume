<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'name', 'address', 'latitude', 'longitude', 'profile_picture', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_admin_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branchStock(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function cashierAccounts(): HasMany
    {
        return $this->hasMany(CashierAccount::class);
    }

    public function cashiers()
    {
        return $this->hasMany(User::class)->where('role', 'cashier');
    }
}
