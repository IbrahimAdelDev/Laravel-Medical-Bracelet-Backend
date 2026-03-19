<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'user_id', 
        'title', 
        'address_details', 
        'country',
        'city', 
        'lat', 
        'lon', 
        'is_primary'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
