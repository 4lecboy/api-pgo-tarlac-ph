<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'control_no',
        'date',
        'particulars',
        'department',
        'organization_barangay',
        'municipality_address',
        'name',
        'contact',
        'action_taken',
        'amount_approved',
        'status',
        'user_id',
    ];

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
