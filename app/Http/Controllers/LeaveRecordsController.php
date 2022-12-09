<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRecordsCalendarRequest;
use App\Http\Requests\LeaveRecordCreateRequest;
use App\Http\Requests\ValidLeaveRecordRequest;
use App\Services\LeaveRecordsService;
use Illuminate\Support\Facades\Auth;

class LeaveRecordsController extends Controller
{
    protected $LeaveRecordsService;

    public function __construct(
        LeaveRecordsService $LeaveRecordsService
    )
	{
        $this->LeaveRecordsService = $LeaveRecordsService;
		$this->middleware('auth');
	}

    public function adminIndex(LeaveRecordsCalendarRequest $request)
    {
        $validated = $request->validated();

        $leaveCalendar = $this->LeaveRecordsService->getLeaveRecordsByYear(
            $validated['year']
        );

        return view('leaveRecordsManagement', $leaveCalendar);
    }

    public function index(LeaveRecordsCalendarRequest $request)
    {
        $validated = $request->validated();

        $leaveCalendar = $this->LeaveRecordsService->getLeaveRecordsByUserID(
            Auth::user()->id,
            $validated['year']
        );

        return view('leaveRecordsDisplay', $leaveCalendar);
    }

    public function showCreateForm()
    {
        return view('leaveRecordsCreate');
    }

    public function create(LeaveRecordCreateRequest $request)
    {
        $validated = $request->validated();
        
        $this->LeaveRecordsService->createLeaveRecords(
            $validated['user_id'],
            $validated['leave_date'],
            $validated['leave_type'],
            $validated['leave_comment'],
            $validated['leave_start'],
            $validated['leave_period']
        );

        return redirect()->route('showLeaveCalendar', ['year' => date_parse($validated['leave_date'])['year']]);
    }

    public function validLeaveRecord(ValidLeaveRecordRequest $request)
    {
        $validated = $request->validated();
 
        $this->LeaveRecordsService->updateLeaveRecordsStatus(
            $validated['user_id'],
            $validated['leave_date'],
            $validated['valid_status']
        );

        return redirect()->route('showLeaveCalendarAdmin', ['year' => date_parse($validated['leave_date'])['year']]);
    }
}
