<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController; // Import the base controller

abstract class Controller extends BaseController // Extend the base controller
{
    // Laravel's base controller already includes AuthorizesRequests, DispatchesJobs, ValidatesRequests traits.
}
