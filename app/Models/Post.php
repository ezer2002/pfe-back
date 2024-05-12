<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = ['social_id','page_name','page_id', 'message', 'media_path','media_paths', 'access_token', 'Programming_options', 'scheduledDateTime'];

    protected $casts = [
        'media_paths' => 'json', // Convertir le champ media_paths en JSON lors de la lecture/écriture
    ];
    public function page()
    {
     return $this->belongsTo(PageSociauxModel::class, 'idpage','id');
    }

}
