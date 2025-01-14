<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Http\Requests\StoreAirportRequest;
use App\Http\Requests\UpdateAirportRequest;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'query' => ['required']
        ]);
        $query = $data['query'];

        $airports = Airport::select(['name', 'iata'])->whereAny(['city', 'name', 'iata'], 'like', "%$query%")->get();

        return [
            'data' => [
                'items' => $airports
            ]
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAirportRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Airport $airport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAirportRequest $request, Airport $airport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Airport $airport)
    {
        //
    }
}
