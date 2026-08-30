<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discrepancy extends Model
{
    protected $fillable = [
        'cashier_account_id', 'branch_id', 'cashier_id', 'reason',
        'amount', 'description', 'reference_type', 'reference_id',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function cashierAccount()
    {
        return $this->belongsTo(CashierAccount::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
