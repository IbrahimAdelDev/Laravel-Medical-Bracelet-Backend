<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'title', 
        'body', 
        'message', 
        'payload', 
        'type', 
        'related_model', 
        'related_id'
    ];

    protected $casts = [
        'payload' => 'array', 
    ];

    public function users() {
        return $this->belongsToMany(User::class, 'notification_user')
                    ->withPivot('is_read', 'read_at')
                    ->withTimestamps();
    }
}
