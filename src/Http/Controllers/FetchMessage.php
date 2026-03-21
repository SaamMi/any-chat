<?php

namespace SaamMi\AnyChat\Http\Controllers;

use Illuminate\Http\Request;

class FetchMessage extends Controller
{
    public function __invoke(Request $request)
    {
        return view('fm', ['message' => 'Hello from Invokable Controller!']);
    }
}
