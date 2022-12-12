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
        return $this->model->select('*', 'name')
            ->join('users', 'user_id', '=', 'users.id')
            ->where(DB::raw('YEAR(start_date)'), $year);
    }

    // 取得請假紀錄所有年份
    public function getLeaveRecordsYears()
    {
        return $this->model->select(DB::raw('YEAR(start_date) as years'))->distinct(DB::raw('YEAR(start_date)'));
    }

    // 取得User請假紀錄 by 年份
    public function getLeaveRecordsByUserID(int $uid, int $year)
    {
        return $this->model->select('*')->where('user_id', $uid)->where(DB::raw('YEAR(start_date)'), $year);
    }

    // 取得User請假紀錄所有年份
    public function getLeaveRecordsYearsByUserID(int $uid)
    {
        return $this->model->select(DB::raw('YEAR(start_date) as years'))->distinct(DB::raw('YEAR(start_date)'))->where('user_id', $uid);
    }

    // 建立請假紀錄
    public function createLeaveRecords(int $uid, int $type, string $comment, string $start_date, string $end_date, int $start_hour, int $end_hour, int $period)
    {
        $LeaveRecords = new LeaveRecords();
        $LeaveRecords->user_id = $uid;
        $LeaveRecords->type = $type;
        $LeaveRecords->comment = $comment;
        $LeaveRecords->start_date = $start_date;
        $LeaveRecords->end_date = $end_date;
        $LeaveRecords->start_hour = $start_hour;
        $LeaveRecords->end_hour = $end_hour;
        $LeaveRecords->period = $period;
        $LeaveRecords->valid_status = 0;
        $LeaveRecords->save();
    }
    
    // 設定假單許可狀態
    public function updateLeaveRecordsStatus(int $lid, int $valid_status)
    {
        $this->model->where('lid', $lid)->update([
            'valid_status' => $valid_status
        ]);
    }

    // 取得calendar內假日天數
    public function getHloidaysInCalendar(string $start_date, string $end_date)
    {
        return DB::table('calendar')->select('*')->whereBetween('date', [$start_date, $end_date])
            ->where('holiday', 2);
    }

    // 判斷請假日期是否重疊
    public function getLeaveRecordConflict(string $start_date, string $end_date, int $uid)
    {
        return $this->model->where(function ($query) use ($start_date, $end_date) {
            $query->whereBetween('start_date', [$start_date, $end_date]);
        })->orWhere(function ($query) use ($start_date, $end_date) {
            $query->where('start_date', '<', $start_date);
            $query->where('end_date', '>', $end_date);
        })->orWhere(function ($query) use ($start_date, $end_date) {
            $query->whereBetween('end_date', [$start_date, $end_date]);
        })->orWhere(function ($query) use ($start_date, $end_date) {
            $query->where('start_date', '>', $start_date);
            $query->where('end_date', '<', $end_date);
        })->where('user_id', $uid);
    }
}