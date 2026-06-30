<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable = [
        'patient_id', 
        'doctor_id', 
        'name', 
        'dosage', 
        'frequency_type', 
        'frequency_value', 
        'start_date', 
        'end_date',
        'condition_id',
        'stop_reason'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function patient() {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor() {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function times() {
        return $this->hasMany(MedicationTime::class);
    }

    public function logs() {
        return $this->hasMany(MedicationLog::class);
    }

    public function doses()
    {
        return $this->hasMany(MedicationDose::class);
    }

    public function condition() {
        return $this->belongsTo(Condition::class);
    }
}
