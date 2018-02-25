<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    public $timestamps = true;
    protected $table = 'movie';
    protected $fillable = [
        'name',
        'year',
        'rating',
        'genre',
        'director',
        'writer',
        'runtime',
        'critics_score',
        'audience_score',
        'fresh_rotten',
        'info',
        'poster',
        'url'
    ];

}
