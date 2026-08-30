<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'sale_number', 'branch_id', 'cashier_id', 'customer_id',
        'subtotal', 'discount', 'total', 'payment_status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public static function generateSaleNumber(): string
    {
        $last = static::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;
        return 'SALE-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
