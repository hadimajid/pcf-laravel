<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;
    protected $guarded=[];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function permissions(){
        return $this->belongsToMany(Permission::class);
    }
}
