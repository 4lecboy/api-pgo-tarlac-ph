<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiving_record_id',
        'file_path',
    ];

    /**
     * Get the record that owns the image.
     */
    public function receivingRecord()
    {
        return $this->belongsTo(ReceivingRecord::class);
    }
}
