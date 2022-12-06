<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CalendarService;

class CalendarController extends Controller
{
    protected $CalendarSrv;

    public function __construct(
        CalendarService $CalendarService
    )
	{
        $this->CalendarSrv = $CalendarService;
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
        if($request->hasFile('upfile'))
        {
            $fileName = 'upload.csv';

            $request->upfile->move(public_path('files'), $fileName);

            $this->CalendarSrv->importCalendar(public_path('files').'/'.$fileName);

            return redirect('calendar');
        }   
        
        return back()->withErrors(['message' => 'No file selected']);
    }
}