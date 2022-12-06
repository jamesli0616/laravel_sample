<?php

namespace App\Checker;

use App\Validator\UploadFileValidator;

class FormChecker 
{
    protected $UploadFileV;

    public function __construct(
        UploadFileValidator $UploadFileValidator
    )
    {
        $this->UploadFileV = $UploadFileValidator;
    }

    public function checkUploadCSV($input)
    {
        return $this->UploadFileV->validateUploadCSV($input);
    }
}