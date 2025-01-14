<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Flight;
use App\Models\Passenger;
use App\Utils\Random;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $bookings = Passenger::with(['booking'])->where('document_number', $user->document_number)->get()->pluck('booking');

        return [
            'data' => [
                'items' => $bookings->map(fn ($booking) => [
                    'code' => $booking->code,
                    'cost' => ($booking->from->cost + $booking->back->cost) * $booking->passengers->count(),
                    'flights' => collect([$booking->from, $booking->back])->map(fn ($flight) => [
                        'flight_id' => $flight->id,
                        'flight_code' => $flight->flight_code,
                        'from' => [
                            'city' =>  $flight->from->city,
                            'airport' =>  $flight->from->name,
                            'iata' =>  $flight->from->iata,
                            'date' =>  $booking->date_from,
                            'time' =>  (new Carbon($flight->time_from))->format('H:i'),
                        ],
                        'to' => [
                            'city' =>  $flight->to->city,
                            'airport' =>  $flight->to->name,
                            'iata' =>  $flight->to->iata,
                            'date' =>  $booking->date_back,
                            'time' =>  (new Carbon($flight->time_to))->format('H:i'),
                        ],
                        'cost' => $flight->cost,
                        'availability' => 156,
                    ]),
                    'passengers' => $booking->passengers->map(fn ($passenger) => [
                        'id' => $passenger->id,
                        'first_name' => $passenger->first_name,
                        'last_name' => $passenger->last_name,
                        'birth_date' => $passenger->birth_date,
                        'document_number' => $passenger->document_number,
                        'place_from' => $passenger->place_from,
                        'place_back' => $passenger->place_back,
                    ])
                ])
            ]
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();

        $new_code = Random::unique_code(Booking::count());
        DB::transaction(function () use ($data, $new_code) {
            $new_booking = Booking::create([
                'flight_from' => $data['flight_from']['id'],
                'flight_back' => $data['flight_back']['id'],
                'date_from' => $data['flight_from']['date'],
                'date_back' => $data['flight_back']['date'],
                'code' => $new_code
            ]);
            $passengers = collect($data['passengers'])->map(fn ($passenger) => [...$passenger, 'booking_id' => $new_booking->id])->all();
            Passenger::insert($passengers);
        });
        
        return response([
            'data' => [
                'code' => $new_code
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        return [
            'data' => [
                'code' => $booking->code,
                'cost' => ($booking->from->cost + $booking->back->cost) * $booking->passengers->count(),
                'flights' => collect([$booking->from, $booking->back])->map(fn ($flight) => [
                    'flight_id' => $flight->id,
                    'flight_code' => $flight->flight_code,
                    'from' => [
                        'city' =>  $flight->from->city,
                        'airport' =>  $flight->from->name,
                        'iata' =>  $flight->from->iata,
                        'date' =>  $booking->date_from,
                        'time' =>  (new Carbon($flight->time_from))->format('H:i'),
                    ],
                    'to' => [
                        'city' =>  $flight->to->city,
                        'airport' =>  $flight->to->name,
                        'iata' =>  $flight->to->iata,
                        'date' =>  $booking->date_back,
                        'time' =>  (new Carbon($flight->time_to))->format('H:i'),
                    ],
                    'cost' => $flight->cost,
                    'availability' => 156,
                ]),
                'passengers' => $booking->passengers->map(fn ($passenger) => [
                    'id' => $passenger->id,
                    'first_name' => $passenger->first_name,
                    'last_name' => $passenger->last_name,
                    'birth_date' => $passenger->birth_date,
                    'document_number' => $passenger->document_number,
                    'place_from' => $passenger->place_from,
                    'place_back' => $passenger->place_back,
                ])
            ]
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        //
    }


    function occupied_seats(Booking $booking) {
        return [
            'data' => [
                'occupied_from' => $booking->passengers->filter(fn ($passenger) => isset($passenger->place_from))
                ->values()
                ->map(fn ($passenger) => [
                    'passenger_id' => $passenger->id,
                    'place' => $passenger->place_from,
                ]),
                'occupied_back' => $booking->passengers->filter(fn ($passenger) => isset($passenger->place_back))
                ->values()
                ->map(fn ($passenger) => [
                    'passenger_id' => $passenger->id,
                    'place' => $passenger->place_back,
                ]),
            ]
        ];
    }

    function select_place(Request $request, Booking $booking) {
        $data = $request->validate([
            'passenger' => ['required'],
            'seat' => ['required', 'string'],
            'type' => ['required', Rule::in(['from', 'back'])],
        ]);
        $type = $data['type'];
        $place_type = 'place_' . $type;
        $passenger = Passenger::find($data['passenger']);

        $passenger_in_booking = $booking->passengers->contains(fn ($passenger) => $passenger->id == $data['passenger']);
        if (!$passenger_in_booking) {
            return response([
                'error' => [
                    'code' => 403,
                    'message' => 'Passenger does not apply to booking',
                ]
            ]);
        }
        
        $is_occupied = $booking->$type->get_passengers($booking->{'date_' . $type})
        ->contains(fn ($passenger) => $passenger->$place_type == $data['seat']);
        if ($is_occupied) {
            return response([
                'error' => [
                    'code' => 422,
                    'message' => 'Seat is occupied'
                ]
            ], 422);
        }

        $passenger->update([
            $place_type => $data['seat']
        ]);

        return [
            'data' => [
                'id' => $passenger->id,
                'first_name' => $passenger->first_name,
                'last_name' => $passenger->last_name,
                'birth_date' => $passenger->birth_date,
                'document_number' => $passenger->document_number,
                'place_from' => $passenger->place_from,
                'place_back' => $passenger->place_back,
            ]
        ];
    }
}
