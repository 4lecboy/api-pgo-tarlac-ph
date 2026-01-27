<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTransactionHistory extends Model
{
    protected $fillable = [
        'name',
        'credit_amount',
        'status',
        'initiated_by',
        'message',
        'recipient',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
