<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierAccount extends Model
{
    protected $table = 'cashier_accounts';

    protected $fillable = [
        'branch_id', 'cashier_id', 'date', 'expected_cash',
        'actual_cash', 'difference', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function discrepancies()
    {
        return $this->hasMany(Discrepancy::class, 'cashier_account_id');
    }
}
