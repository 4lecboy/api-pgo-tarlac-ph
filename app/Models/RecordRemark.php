<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordRemark extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiving_record_id',
        'user_id',
        'remark'
    ];

    public function record()
    {
        return $this->belongsTo(ReceivingRecord::class, 'receiving_record_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
