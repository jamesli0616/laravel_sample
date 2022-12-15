<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRecords extends Model
{
    protected $table = 'leave_records';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'comment',
        'start_date',
        'end_date',
        'start_hour',
        'end_hour',
        'period',
        'warning',
        'valid_status'
    ];
}
