<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    public function items()
    {
        return $this->hasMany(Item::class)->with('product:name,hsn_code');
    }
        /**
     * Get the billing address for the invoice.
     */
    public function billingAddress()
    {
        return $this->hasOne(Addres::class, 'id', 'billing_address_id');
    }

    /**
     * Get the shipping address for the invoice.
     */
    public function shippingAddress()
    {
        return $this->hasOne(Addres::class, 'id', 'shipping_address_id');
    }

    /**
     * Get the products for the invoice.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'items', 'invoice_id', 'product_id');
    }
}
