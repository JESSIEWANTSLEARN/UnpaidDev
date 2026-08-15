<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    protected $table = 'WBO_Batches';

    protected $primaryKey = 'batch_id';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'batch_number',
        'quantity_received',
        'current_quantity',
        'received_date',
        'expiry_date',
    ];

    protected $casts = [
        'received_date' => 'datetime',
        'expiry_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id',
            'product_id'
        );
    }
}