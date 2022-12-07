<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Services\CalendarService;
use App\Checker\RequestChecker;
use App\Imports\CalendarImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\UploadCSVRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Http\Requests\DisplayCalendarRequest;

class CalendarController extends Controller
{
    protected $CalendarService;

    public function __construct(
        CalendarService $CalendarService
    )
	{
        $this->CalendarService = $CalendarService;
	}

    public function showUpload()
    {
        return view('calendarUpload');
    }

    public function index(DisplayCalendarRequest $request)
    {
        $validated = $request->validated();

        return view('calendarDisplay', $this->CalendarService->displayCalendarPage($validated['year']));
    }

    public function upload(UploadCSVRequest $request)
    {
        $validated = $request->validated();
        
        Excel::import(new CalendarImport, $validated['upfile']);

        return redirect()->route('calendar', ['year' => DATE('Y')]);
    }

    public function update(UpdateCalendarRequest $request)
    {
        $validated = $request->validated();
        
        $this->CalendarService->updateCalendarByDate($validated['edit_date'], $validated['holiday'], $validated['comment']);

        return redirect()->route('calendar', ['year' => date_parse($validated['edit_date'])['year']]);
    }
}