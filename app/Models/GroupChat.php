<?php

namespace App\Models;

use App\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GroupChat extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $fillable=[
        'sender_id',
        'group_id',
        'message',
    ];

    public function userInfo(){
        return $this->belongsTo(User::class, 'sender_id','id');
    }
}
