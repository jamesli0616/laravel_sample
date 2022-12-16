<?php

namespace App\Exceptions;

use Exception;

class CreateLeaveRecordExceptions extends Exception
{
    public function render()
    {
        return response()
            ->json(
                ["error" => true, "message" => $this->getMessage()],
                200,
                ['Content-type'=>'application/json;charset=utf-8'],
                JSON_UNESCAPED_UNICODE
            );  
    }
}
