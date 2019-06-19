<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    protected $table = 'user_details';

    protected $fillable = [
        'user_id', 'name','company', 'designation', 'mobile', 'status', 'ip_address', 'region', 'country', 'image_path'
    ];
}
