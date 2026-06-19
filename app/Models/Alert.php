<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'patient_id', 
        'device_id', 
        'type', 
        'is_resolved', 
        'resolved_by',
        'notes'
    ];

    public function patient() {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function device() {
        return $this->belongsTo(Device::class);
    }

    public function resolver() {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
