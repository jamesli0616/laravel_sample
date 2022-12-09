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
        return $this->model->select('*')->where(DB::raw('YEAR(leave_date)'), $year);
    }

    // 取得請假紀錄所有年份
    public function getLeaveRecordsYears()
    {
        return $this->model->select(DB::raw('YEAR(leave_date) as years'))->distinct(DB::raw('YEAR(leave_date)'));
    }

    // 取得User請假紀錄 by 年份
    public function getLeaveRecordsByUserID(int $uid, int $year)
    {
        return $this->model->select('*')->where('user_id', $uid)->where(DB::raw('YEAR(leave_date)'), $year);
    }

    // 取得User請假紀錄所有年份
    public function getLeaveRecordsYearsByUserID(int $uid)
    {
        return $this->model->select(DB::raw('YEAR(leave_date) as years'))->distinct(DB::raw('YEAR(leave_date)'))->where('user_id', $uid);
    }

    // 建立請假紀錄
    public function createLeaveRecords(int $uid, string $leave_date, int $leave_type, string $leave_comment, int $leave_start, int $leave_period)
    {
        $LeaveRecords = new LeaveRecords();
        $LeaveRecords->user_id = $uid;
        $LeaveRecords->leave_date = $leave_date;
        $LeaveRecords->leave_type = $leave_type;
        $LeaveRecords->leave_comment = $leave_comment;
        $LeaveRecords->leave_start = $leave_start;
        $LeaveRecords->leave_period = $leave_period;
        $LeaveRecords->valid_status = 0;
        $LeaveRecords->save();
    }
    
    // 設定假單許可狀態
    public function updateLeaveRecordsStatus(int $uid, string $leave_date, int $valid_status)
    {
        $this->model->where('user_id', $uid)->where('leave_date', $leave_date)->update([
            'valid_status' => $valid_status
        ]);
    }
}