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
        $request->validated();

        return view('calendarDisplay', $this->CalendarService->displayCalendarPage($request['year']));
    }

    public function upload(UploadCSVRequest $request)
    {
        $request->validated();
        
        Excel::import(new CalendarImport, $request->file('upfile'));

        return redirect('calendar');
    }

    public function update(UpdateCalendarRequest $request)
    {
        $request->validated();
        
        $this->CalendarService->updateCalendarByDate($request['edit_date'], $request['holiday'], $request['comment']);

        return redirect()->route('calendar', ['year' => date_parse($request['edit_date'])['year']]);
    }
}