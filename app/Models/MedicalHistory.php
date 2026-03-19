<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    protected $fillable = [
        'patient_id', 
        'doctor_id', 
        'condition_title', 
        'description', 
        'date_recorded'
    ];

    public function patient() {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor() {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
