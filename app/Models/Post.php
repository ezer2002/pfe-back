<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts'; 

    protected $fillable = ['page_id', 'message', 'media_path', 'post_id', 'access_token'];
}
