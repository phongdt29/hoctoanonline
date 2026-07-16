<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Laravel 12 base Controller toi gian — them AuthorizesRequests de dung
    // $this->authorize() (Policy) trong controller (ticket L1).
    use AuthorizesRequests;
}
