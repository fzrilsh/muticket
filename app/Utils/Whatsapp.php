<?php

namespace App\Utils;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Whatsapp {

    static string $HEAD = "*MuTicket* Information

";
    static string $MESSAGE_VERIF = "*MuTicket* Verifcation Message

> Please verify your number by visiting the link below:

";
    public function __construct(protected string $message)
    {}

    public function send($no){
        Http::post('http://127.0.0.1:3000/send', [
            'message' => $this->message,
            'no' => $no
        ]);
    }
}
