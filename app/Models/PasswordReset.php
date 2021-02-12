<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function setUpdatedAtAttribute($value)
    {
        // to Disable updated_at
    }
}
