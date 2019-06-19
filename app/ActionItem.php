<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActionItem extends Model
{

    protected $table = 'action_item';
        
    protected  $fillable = ['meeting_id', 'description'];

}
