<?php

namespace App\Checker\Validator;

use Validator;

class UploadFileValidator {
    public function validateUploadCSV($input)
    {
        if($input->hasFile('upfile')==null)
        {
            return false;
        }

        $rules = [
            'upfile' => 'required|file|mimes:csv',
        ];

        $this->validator = Validator::make($input->all(), $rules);

        if ($this->validator->fails())
        {
            return false;
        }

        return true;
    }
}
