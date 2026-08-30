<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchStock extends Model
{
    protected $table = 'branch_stock';

    protected $fillable = [
        'branch_id', 'product_id', 'quantity', 'buying_cost',
        'selling_price', 'supplier', 'date_received', 'entered_by',
    ];

    protected function casts(): array
    {
        return [
            'buying_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'date_received' => 'date',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
