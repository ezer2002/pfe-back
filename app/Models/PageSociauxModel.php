<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageSociauxModel extends Model
{
    use HasFactory;
    protected $table = 'pagesociaux';

    protected $fillable = ['page_name','page_id','access_token'];

}
