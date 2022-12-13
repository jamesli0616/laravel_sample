<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $table = 'calendar';
    public $timestamps = false;
    
    protected $fillable = [
        'date',
        'weekdays',
        'holiday',
        'comment',
    ];
}