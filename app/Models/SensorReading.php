<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $fillable = [
        'device_id', 
        'patient_id', 
        'payload'
    ];

    protected $casts = [
        'payload' => 'array', 
    ];

    public function device() {
        return $this->belongsTo(Device::class);
    }

    public function patient() {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
