<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Services\CalendarService;
use App\Checker\RequestChecker;
use App\Imports\CalendarImport;
use Maatwebsite\Excel\Facades\Excel;

class CalendarController extends Controller
{
    protected $CalendarService;
    protected $RequestChecker;

    public function __construct(
        CalendarService $CalendarService,
        RequestChecker $RequestChecker
    )
	{
        $this->CalendarService = $CalendarService;
        $this->RequestChecker = $RequestChecker;
	}

    public function showUpload()
    {
        return view('calendarUpload');
    }

    public function index(Request $request)
    {
        if($this->RequestChecker->checkRequestYear($request)) {

            return view('calendarDisplay', $this->CalendarService->displayCalendarPage($request['year']));
        }   
        
        return view('calendarDisplay', $this->CalendarService->displayCalendarPage(Date('Y')));
    }

    public function upload(Request $request)
    {
        if($this->RequestChecker->checkUploadCSVFile($request)) {
            Excel::import(new CalendarImport, $request->file('upfile'));

            return redirect('calendar');
        }   
        
        return back()->withErrors(['message' => 'request fails']);
    }

    public function update(Request $request)
    {
        if($this->RequestChecker->checkUpdateCalendar($request)) {
            $this->CalendarService->updateCalendarByDate($request['edit_date'], $request['holiday'], $request['comment']);

            return redirect()->route('calendar', ['year' => date_parse($request['edit_date'])['year']]);
        } 

        return back()->withErrors(['message' => 'request fails']);
    }
}