<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct()
	{
	}

    public function index()
    {
        return view('home', ['user_name' => Auth::user()->name]);
    }
}