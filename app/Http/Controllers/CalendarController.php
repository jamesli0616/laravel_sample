<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CalendarController extends Controller
{
    public function __construct()
	{
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

        $records = file(public_path('files').'/'.$fileName, FILE_IGNORE_NEW_LINES);

        return view('calendarDisplay', ['csvContents' => $records]);
    }
}