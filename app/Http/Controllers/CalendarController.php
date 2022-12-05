<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Repositories\CalendarRepository;
use App\Imports\CalendarImport;
use Maatwebsite\Excel\Facades\Excel;

class CalendarController extends Controller
{
    protected $CalendarRepo;
    public function __construct(CalendarRepository $CalendarRepository)
	{
        $this->CalendarRepo = $CalendarRepository;
	}

    public function index(Request $request)
    {
        return view('calendar');
    }

    public function upload(Request $request)
    {
        // debug: always use test.csv file name
        $fileName = 'test.csv';

        $request->upfile->move(public_path('files'), $fileName);

        //$records = file(public_path('files').'/'.$fileName, FILE_IGNORE_NEW_LINES);

        Excel::import(new CalendarImport, public_path('files').'/'.$fileName);
        
        return redirect('calendar');
    }
}