<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'device_uid', 
        'patient_id', 
        'status'
    ];

    public function patient() {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function sensorReadings() {
        return $this->hasMany(SensorReading::class);
    }
    
    public function alerts() {
        return $this->hasMany(Alert::class);
    }
}
