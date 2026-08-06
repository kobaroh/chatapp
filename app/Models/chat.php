<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class chat extends Model
{
    protected $fillable = ['user_id','other_user_id','message','group_id','is_read'];
}
