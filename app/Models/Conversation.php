<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    public function admin()
    {
        return $this->belongsTo(User::class,'admin_id')->with('roles:name');
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
