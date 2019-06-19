<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscussionPoints extends Model
{
    protected $table = 'discussion_points';
        
    protected  $fillable = ['meeting_id', 'description'];
}
