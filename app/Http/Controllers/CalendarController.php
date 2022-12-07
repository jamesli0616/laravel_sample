<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CalendarService;
use App\Checker\FormChecker;
use App\Imports\CalendarImport;
use Maatwebsite\Excel\Facades\Excel;

class CalendarController extends Controller
{
    protected $CalendarSrv;
    protected $FormChk;

    public function __construct(
        CalendarService $CalendarService,
        FormChecker $FormChecker
    )
	{
        $this->CalendarSrv = $CalendarService;
        $this->FormChk = $FormChecker;
	}

    public function showUpload(Request $request)
    {
        return view('calendarUpload');
    }

    public function index(Request $request)
    {
        return view('calendarDisplay', $this->CalendarSrv->displayCalendarPage($request['year']));
    }

    public function upload(Request $request)
    {
        if($this->FormChk->checkUploadCSV($request)) {
            Excel::import(new CalendarImport, $request->file('upfile'));

            return redirect('calendar');
        }   
        
        return view('calendarUpload', ['message' => 'upload error']);
    }

    public function update(Request $request)
    {
        $this->CalendarSrv->updateCalendarByDate($request['edit_date'], $request['holiday'], $request['comment']);

        return redirect()->route('calendar', ['year' => date_parse($request['edit_date'])['year']]);
    }
}