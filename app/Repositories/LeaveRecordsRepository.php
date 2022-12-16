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

    // 取得所有請假紀錄指定日期範圍
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
        })->get();
    }

    // 取得所有請假紀錄指定日期範圍 by user id
    public function getLeaveRecordsByDataRangeAndUserID(int $user_id, string $start_date = '1970-01-01', string $end_date = '2038-01-19')
    {
        return $this->model->where(function ($query) use ($start_date, $end_date, $user_id) {
            $query->where('start_date', '<', $start_date);
            $query->where('end_date', '>', $start_date);
            $query->where('user_id', $user_id);
        })->orWhere(function ($query) use ($start_date, $end_date, $user_id) {
            $query->where('start_date', '<', $end_date);
            $query->where('end_date', '>', $end_date);
            $query->where('user_id', $user_id);
        })->orWhere(function ($query) use ($start_date, $end_date, $user_id) {
            $query->where('start_date', '>', $start_date);
            $query->where('end_date', '<', $end_date);
            $query->where('user_id', $user_id);
        })->get();
    }

    // 建立請假紀錄
    public function createLeaveRecords(array $params)
    {
        $this->model->insert($params);
    }
    
    // 設定假單許可狀態
    public function updateLeaveRecord(int $lid, int $valid_status)
    {
        $this->model->where('lid', $lid)->update([
            'valid_status' => $valid_status
        ]);
    }

    // 判斷請假日期是否重疊
    public function getLeaveRecordConflict(string $start_date, string $end_date, int $start_hour, int $end_hour, int $user_id)
    {
        return $this->model->where(function ($query) use ($start_date, $end_date, $user_id) {
            $query->where('start_date', '>', $start_date);
            $query->where('start_date', '<', $end_date);
            $query->where('user_id', $user_id);
        })->orWhere(function ($query) use ($start_date, $end_date, $user_id) {
            $query->where('end_date', '>', $start_date);
            $query->where('end_date', '<', $end_date);
            $query->where('user_id', $user_id);
        })->orWhere(function ($query) use ($start_date, $end_date, $user_id) {
            $query->where('start_date', '<=', $start_date);
            $query->where('end_date', '>=', $end_date);
            $query->where('user_id', $user_id);
        })->orWhere(function ($query) use ($start_date, $end_date, $user_id) {
            $query->where('start_date', '>', $start_date);
            $query->where('end_date', '<', $end_date);
            $query->where('user_id', $user_id);
        // 同一日上下半天請假重疊情況
        })->orWhere(function ($query) use ($end_date, $end_hour, $user_id) {
            $query->where('start_date', $end_date);
            $query->where('start_hour', '<', $end_hour);
            $query->where('user_id', $user_id);
        })->orWhere(function ($query) use ($start_date, $start_hour, $user_id) {
            $query->where('end_date', $start_date);
            $query->where('end_hour', '>', $start_hour);
            $query->where('user_id', $user_id);
        })->get();
    }
}