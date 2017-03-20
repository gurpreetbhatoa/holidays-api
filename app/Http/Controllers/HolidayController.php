<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Holiday\ApiV1Service;

class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sp = new ApiV1Service();
        $apiResponse = $sp->getHolidays($request->all());
        return response()->json($apiResponse, $apiResponse['status']);
    }
}
