<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice_new_Item extends Model
{
    protected $fillable = [
        'product_id', 'unit_price', 'qty'
    ];

    public function product()
    {
        return $this->belongsTo(Productnew::class);
    }
}