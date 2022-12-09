<?php

namespace App\Services;

use App\Repositories\LeaveRecordsRepository;

class LeaveRecordsService
{
    protected $LeaveRecordsRepository;

    public function __construct(
        LeaveRecordsRepository $LeaveRecordsRepository
    )
	{
        $this->LeaveRecordsRepository = $LeaveRecordsRepository;
	}

    public function getLeaveRecordsByYear(int $year)
    {
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByYear($year)->get(),
            'leaveCalendarYears' => $this->LeaveRecordsRepository->getLeaveRecordsYears()->get(),
            'leaveRecordYear' => $year
        ];
    }

    public function getLeaveRecordsByUserID(int $uid, int $year)
    {
        return [
            'leaveCalendar' => $this->LeaveRecordsRepository->getLeaveRecordsByUserID($uid, $year)->get(),
            'leaveCalendarYears' => $this->LeaveRecordsRepository->getLeaveRecordsYearsByUserID($uid)->get(),
            'leaveRecordYear' => $year
        ];
    }

    public function createLeaveRecords(int $uid, string $leave_date, int $leave_type, string $leave_comment, int $leave_start, int $leave_period)
    {
        $this->LeaveRecordsRepository->createLeaveRecords($uid, $leave_date, $leave_type, $leave_comment, $leave_start, $leave_period);
    }

    public function updateLeaveRecordsStatus(int $uid, string $leave_date, int $valid_status)
    {
        $this->LeaveRecordsRepository->updateLeaveRecordsStatus($uid, $leave_date, $valid_status);
    }
}
