<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wilaya;

class WilayaController extends Controller
{
    public function index()
    {
        return Wilaya::query()->orderBy('code')->get();
    }
}
