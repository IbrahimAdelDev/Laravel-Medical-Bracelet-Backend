<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicationLog extends Model
{
    protected $fillable = [
        'medication_id', 
        'expected_time', 
        'status', 
        'taken_at'
    ];

    protected $casts = [
        'expected_time' => 'datetime',
        'taken_at' => 'datetime',
    ];

    public function medication() {
        return $this->belongsTo(Medication::class);
    }
}
