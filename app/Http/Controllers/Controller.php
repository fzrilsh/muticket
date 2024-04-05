<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    public $validation_errors = null;
    public function validator($rules): \Illuminate\Validation\Validator
    {
        $validator = Validator::make(\request()->all(), $rules);
        if($validator->fails()){
            $this->validation_errors = $validator->errors();
        }

        return $validator;
    }
}
