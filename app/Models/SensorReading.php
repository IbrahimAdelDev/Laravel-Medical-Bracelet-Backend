<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $fillable = [
        'device_id', 
        'patient_id', 
        'heart_rate', 
        'spo2', 
        'body_temperature', 
        'ecg_signal', 
        'acc_x', 
        'acc_y', 
        'acc_z', 
        'uv_index', 
        'lat', 
        'lon', 
        'device_timestamp'
    ];

    public function device() {
        return $this->belongsTo(Device::class);
    }

    public function patient() {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
