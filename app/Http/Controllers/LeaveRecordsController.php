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
        $params = $request->validated();
        
        $response = $this->LeaveRecordsService->createLeaveRecords($params);

        if($response['status'] == -1)
        {
            return back()->withErrors(['message' => $response['message']]);
        }

        return redirect()->route('showLeaveCalendar', ['year' => DATE('Y')]);
    }

    public function validLeaveRecord(ValidLeaveRecordRequest $request)
    {
        $params = $request->validated();
 
        $response = $this->LeaveRecordsService->updateLeaveRecordsStatus($params);

        return redirect()->route('showLeaveCalendarAdmin', ['year' => DATE('Y')]);
    }
}
