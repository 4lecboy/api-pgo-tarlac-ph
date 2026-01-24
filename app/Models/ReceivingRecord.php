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
        'district',
        'category',
        'type',
        'requisitioner',
        'served_request',
        'remarks',
        'processed_by_user_id',
        'processed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'amount_approved' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // User who created this record (Receiving department)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // User who processed this record (Department staff)
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
