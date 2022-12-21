<?php

namespace App\Repositories;

use App\Models\LeaveRecords;
use Illuminate\Support\Facades\DB;

class LeaveRecordsRepository
{
    protected $model;

    public function __construct(
        LeaveRecords $LeaveRecords
    )
    {
        $this->model = $LeaveRecords;
    }

    // 取得指定日期範圍所有請假紀錄
    public function getLeaveRecordsByDataRange(string $start_date = '1970-01-01', string $end_date = '2038-01-19')
    {
        return $this->model->join('users', 'user_id', '=', 'users.id')->where(function ($query) use ($start_date, $end_date) {
            $query->where('start_date', '<', $start_date);
            $query->where('end_date', '>', $start_date);
        })->orWhere(function ($query) use ($start_date, $end_date) {
            $query->where('start_date', '<', $end_date);
            $query->where('end_date', '>', $end_date);
        })->orWhere(function ($query) use ($start_date, $end_date) {
            $query->where('start_date', '>', $start_date);
            $query->where('end_date', '<', $end_date);
        })->orderBy('start_date')->get();
    }

    // 建立請假紀錄
    public function createLeaveRecords(array $params)
    {
        $this->model->insert($params);
    }
    
    // 設定假單許可狀態
    public function updateLeaveRecord(array $params)
    {
        $this->model->where('lid', $params['leave_id'])->update([
            'valid_status' => $params['valid_status']
        ]);
    }

    // 判斷請假日期是否重疊
    public function getLeaveRecordConflict(array $params)
    {
        return $this->model->where(function ($query) use ($params) {
            $query->where('start_date', '>', $params['start_date']);
            $query->where('start_date', '<', $params['end_date']);
            $query->where('user_id', $params['user_id']);
        })->orWhere(function ($query) use ($params) {
            $query->where('end_date', '>', $params['start_date']);
            $query->where('end_date', '<', $params['end_date']);
            $query->where('user_id', $params['user_id']);
        })->orWhere(function ($query) use ($params) {
            $query->where('start_date', '<=', $params['start_date']);
            $query->where('end_date', '>=', $params['end_date']);
            $query->where('user_id', $params['user_id']);
        })->orWhere(function ($query) use ($params) {
            $query->where('start_date', '>', $params['start_date']);
            $query->where('end_date', '<', $params['end_date']);
            $query->where('user_id', $params['user_id']);
        // 同一日上下半天請假重疊情況
        })->orWhere(function ($query) use ($params) {
            $query->where('start_date', $params['end_date']);
            $query->where('start_hour', '<', $params['end_hour']);
            $query->where('user_id', $params['user_id']);
        })->orWhere(function ($query) use ($params) {
            $query->where('end_date', $params['start_date']);
            $query->where('end_hour', '>', $params['start_hour']);
            $query->where('user_id', $params['user_id']);
        })->get();
    }
}