<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function getLocation()
    {
        return Location::latest()->first();
    }

    public function updateLocation(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        return Location::updateOrCreate(['id' => 1], $data);
    }
}
