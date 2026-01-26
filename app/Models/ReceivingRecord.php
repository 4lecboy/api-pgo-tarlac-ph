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
        'province',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'amount_approved' => 'decimal:2',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Images associated with the record
     */
    public function images()
    {
        return $this->hasMany(DocumentImage::class);
    }

    /**
     * User who created the record (Receiving department)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * User who processed the record (Department staff)
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    /**
     * History of remarks added by different users/departments
     */
    public function remarksHistory()
    {
        return $this->hasMany(RecordRemark::class, 'receiving_record_id')->with('user')->orderBy('created_at', 'asc');
    }
}
