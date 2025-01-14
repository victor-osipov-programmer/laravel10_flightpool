<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    use HasFactory;

    static public $number_seats = 25;


    function from() {
        return $this->belongsTo(Airport::class, 'from_id');
    }
    function to() {
        return $this->belongsTo(Airport::class, 'to_id');
    }


    function bookings_from() {
        return $this->hasMany(Booking::class, 'flight_from');
    }
    function bookings_back() {
        return $this->hasMany(Booking::class, 'flight_back');
    }

    // function get_bookings(string $date) {
    //     return Booking::withCount('passengers')->where('flight_from', $this->id)->where('date_from', $date)
    //     ->orWhere(fn ($query) => $query->where('flight_back', $this->id)->where('date_back', $date))->get();
    // }
    // function get_passengers(string $date) {
    //     return $this->get_bookings($date)->pluck('passengers_count')->sum();
    // }

    function get_bookings(string $date) {
        return collect([...$this->bookings_from, ...$this->bookings_back])
        ->filter(fn ($booking) => 
            $booking->flight_from == $this->id && $booking->date_from == $date || 
            $booking->flight_back == $this->id && $booking->date_back == $date
        )->unique();
    }


    function get_passengers(string $date) {
        return $this->get_bookings($date)->pluck('passengers')->collapse();
    }
}
