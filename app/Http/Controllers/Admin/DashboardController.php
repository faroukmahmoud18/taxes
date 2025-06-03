<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller; // Make sure it extends the base Controller
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }
}
