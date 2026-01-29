<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingRecord extends Model
{
    protected $fillable = [
        'category',
        'date',
        'particulars',
        'type',
        'recipient',
        'vehicle',
        'driver',
        'amount',
        'file_path',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
