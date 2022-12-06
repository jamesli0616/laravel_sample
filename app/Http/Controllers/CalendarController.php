<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CalendarService;
use App\Checker\FormChecker;

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
        if($this->FormChk->checkUploadCSV($request))
        {
            $fileName = 'upload.csv';
            $request->upfile->move(public_path('files'), $fileName);
            $this->CalendarSrv->importCalendar(public_path('files').'/'.$fileName);

            return redirect('calendar');
        }   
        
        return view('calendarUpload', ['message' => 'upload error']);
    }
}