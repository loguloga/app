<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoogleAccount extends Model
{   
    protected $table = 'google_account';
    
    public $timestamps = true; 
    
    protected  $fillable = ['user_id', 'email', 'picture', 'name', 'access_token', 'refresh_token', 'expires_in', 'status' ];

    public function users()
    {
    	return $this->belongsTo(User_auth::class) ;
    }
}
