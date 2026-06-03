<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index()
    {
        $districts = District::where('status', 'active')->get();
        return response()->json([
            'status' => 'success',
            'data' => $districts
        ]);
    }
}
