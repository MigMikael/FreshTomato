<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Celeb extends Model
{
    public $timestamps = true;
    protected $table = 'celebrity';
    protected $fillable = [
        'name',
        'birthday',
        'birthplace',
        'info',
        'image',
        'highest_rate',
        'lowest_rate',
        'url'
    ];
}
