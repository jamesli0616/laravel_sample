<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRecords extends Model
{
    protected $table = 'leave_records';
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'leave_date',
        'leave_type',
        'leave_comment',
        'leave_start',
        'leave_period',
        'valid_status',
    ];
}
