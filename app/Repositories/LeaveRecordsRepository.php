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
    public function getLeaveRecordsByDateRange(string $startDate, string $endDate)
    {
        return $this->model->join('users', 'user_id', '=', 'users.id')->where(function($query) use($startDate, $endDate) {
            $query->where('start_date', '<', $startDate);
            $query->where('end_date', '>=', $startDate);
        })->orWhere(function($query) use($startDate, $endDate) {
            $query->where('start_date', '<=', $endDate);
            $query->where('end_date', '>', $endDate);
        })->orWhere(function($query) use($startDate, $endDate) {
            $query->where('start_date', '>=', $startDate);
            $query->where('end_date', '<=', $endDate);
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
        return $this->model->where(function($query) use($params) {
            $query->where('start_date', '>', $params['start_date']);
            $query->where('start_date', '<', $params['end_date']);
            $query->where('user_id', $params['user_id']);
        })->orWhere(function($query) use($params) {
            $query->where('end_date', '>', $params['start_date']);
            $query->where('end_date', '<', $params['end_date']);
            $query->where('user_id', $params['user_id']);
        })->orWhere(function($query) use($params) {
            $query->where('start_date', '<=', $params['start_date']);
            $query->where('end_date', '>=', $params['end_date']);
            $query->where('user_id', $params['user_id']);
        })->orWhere(function($query) use($params) {
            $query->where('start_date', '>', $params['start_date']);
            $query->where('end_date', '<', $params['end_date']);
            $query->where('user_id', $params['user_id']);
        // 同一日上下半天請假重疊情況
        })->orWhere(function($query) use($params) {
            $query->where('start_date', $params['end_date']);
            $query->where('start_hour', '<', $params['end_hour']);
            $query->where('user_id', $params['user_id']);
        })->orWhere(function($query) use($params) {
            $query->where('end_date', $params['start_date']);
            $query->where('end_hour', '>', $params['start_hour']);
            $query->where('user_id', $params['user_id']);
        })->get();
    }
}