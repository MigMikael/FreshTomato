<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MovieCast extends Model
{
    public $timestamps = true;
    protected $table = 'movie_cast';
    protected $fillable = [
        'movie_id',
        'celeb_id',
        'relation',
    ];
}
