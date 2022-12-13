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

    // 取得所有請假紀錄 by 年份
    public function getLeaveRecordsByYear(int $year)
    {
        return $this->model->join('users', 'user_id', '=', 'users.id')->where(DB::raw('YEAR(start_date)'), $year);
    }

    // 取得請假紀錄所有年份
    public function getLeaveRecordsYears()
    {
        return $this->model->select(DB::raw('YEAR(start_date) as years'))->distinct(DB::raw('YEAR(start_date)'));
    }

    // 取得User請假紀錄 by 年份
    public function getLeaveRecordsByUserID(int $user_id, int $year)
    {
        return $this->model->where('user_id', $user_id)->where(DB::raw('YEAR(start_date)'), $year);
    }

    // 取得User請假紀錄所有年份
    public function getLeaveRecordsYearsByUserID(int $user_id)
    {
        return $this->model->select(DB::raw('YEAR(start_date) as years'))->distinct(DB::raw('YEAR(start_date)'))->where('user_id', $user_id);
    }

    // 建立請假紀錄
    public function createLeaveRecords(mixed $params)
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
        });
    }
}