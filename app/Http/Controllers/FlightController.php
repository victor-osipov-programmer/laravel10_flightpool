<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Http\Requests\StoreFlightRequest;
use App\Http\Requests\UpdateFlightRequest;
use App\Models\Airport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FlightController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', Rule::exists('airports', 'iata')],
            'to' => ['required', Rule::exists('airports', 'iata')],
            'date1' => ['required', 'date_format:Y-m-d'],
            'date2' => ['nullable', 'date_format:Y-m-d'],
            'passengers' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        $airport_from = Airport::where('iata', $data['from'])->first();
        $airport_to = Airport::where('iata', $data['to'])->first();

        $flights_to = Flight::with(['bookings_from.passengers', 'bookings_back.passengers'])->where('from_id', $airport_from->id)
        ->where('to_id', $airport_to->id)->get();

        $flights_back = empty($data['date2']) ? collect() : 
        Flight::with(['bookings_from.passengers', 'bookings_back.passengers'])->where('from_id', $airport_to->id)
        ->where('to_id', $airport_from->id)->get();
        

        return [
            'data' => [
                'flights_to' => $flights_to
                ->filter(fn ($flight) => $flight->get_passengers($data['date1'])->count() + $data['passengers'] <= Flight::$number_seats)
                ->values()
                ->map(fn ($flight) => [
                    'flight_id' => $flight->id,
                    'flight_code' => $flight->flight_code,
                    'from' => [
                        'city' =>  $flight->from->city,
                        'airport' =>  $flight->from->name,
                        'iata' =>  $flight->from->iata,
                        'date' =>  $data['date1'],
                        'time' =>  (new Carbon($flight->time_from))->format('H:i'),
                    ],
                    'to' => [
                        'city' =>  $flight->to->city,
                        'airport' =>  $flight->to->name,
                        'iata' =>  $flight->to->iata,
                        'date' =>  $data['date1'],
                        'time' =>  (new Carbon($flight->time_to))->format('H:i'),
                    ],
                    'cost' => $flight->cost,
                    'availability' => 156,
                ]),
                'flights_back' => $flights_back
                ->filter(fn ($flight) => $flight->get_passengers($data['date1'])->count() + $data['passengers'] <= Flight::$number_seats)
                ->values()
                ->map(fn ($flight) => [
                    'flight_id' => $flight->id,
                    'flight_code' => $flight->flight_code,
                    'from' => [
                        'city' =>  $flight->from->city,
                        'airport' =>  $flight->from->name,
                        'iata' =>  $flight->from->iata,
                        'date' =>  $data['date2'],
                        'time' =>  (new Carbon($flight->time_from))->format('H:i'),
                    ],
                    'to' => [
                        'city' =>  $flight->to->city,
                        'airport' =>  $flight->to->name,
                        'iata' =>  $flight->to->iata,
                        'date' =>  $data['date2'],
                        'time' =>  (new Carbon($flight->time_to))->format('H:i'),
                    ],
                    'cost' => $flight->cost,
                    'availability' => 156,
                ])
            ]
        ];


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFlightRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Flight $flight)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFlightRequest $request, Flight $flight)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Flight $flight)
    {
        //
    }
}
