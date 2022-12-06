<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Repositories\CalendarRepository;

class CalendarController extends Controller
{
    protected $CalendarRepo;
    public function __construct(CalendarRepository $CalendarRepository)
	{
        $this->CalendarRepo = $CalendarRepository;
	}

    public function index(Request $request)
    {
        return view('calendarDisplay', [
            'calendarDate' => $this->CalendarRepo->getCalendarByYear($request['year'])->get()
        ]);
    }

    public function showUpload(Request $request)
    {
        return view('calendar');
    }

    public function upload(Request $request)
    {
        $fileName = 'upload.csv';

        $request->upfile->move(public_path('files'), $fileName);

        $this->CalendarRepo->importCalendarCSV(public_path('files').'/'.$fileName);

        return redirect()->route('calendar', [
            'year' => date("Y")
        ]);
    }
}