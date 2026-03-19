<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $fillable = [
        'user_id', 
        'birth_date', 
        'gender', 
        'blood_type', 
        'weight', 
        'height'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
