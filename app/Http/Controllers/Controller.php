<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

abstract class Controller
{
    public MessageBag $validation_errors;
    public \Illuminate\Validation\Validator $validation;
    public function validator($rules): \Illuminate\Validation\Validator
    {
        $validator = Validator::make(\request()->all(), $rules);
        if($validator->fails()){
            $this->validation_errors = $validator->errors();
        }

        $this->validation = $validator;
        return $validator;
    }

    public function createSlug($string) {
        $slug = strtolower(trim($string));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = preg_replace('/-+/', "-", $slug); // Mengganti beberapa strip menjadi satu strip
        return $slug;
    }

}
