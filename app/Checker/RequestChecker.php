<?php

namespace App\Checker;

use App\Validator\RequestValidator;
use Illuminate\Http\Request;

class RequestChecker 
{
    protected $RequestValidator;

    public function __construct(
        RequestValidator $RequestValidator
    )
    {
        $this->RequestValidator = $RequestValidator;
    }

    public function checkRequestYear(Request $request)
    {
        return $this->RequestValidator->checkRequestYear($request);
    }

    public function checkUploadCSVFile(Request $request)
    {
        return $this->RequestValidator->checkUploadCSVFile($request);
    }

    public function checkUpdateCalendar(Request $request)
    {
        return $this->RequestValidator->checkUpdateCalendar($request);
    }
}