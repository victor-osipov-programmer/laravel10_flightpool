<?php

namespace App\Http\Requests;

use App\Models\Flight;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public $flight_from;
    public $flight_back;

    public function rules(): array
    {

        $this->flight_from = Flight::find($this['flight_from']['id']);
        $this->flight_back = Flight::find($this['flight_back']['id']);

        return [
            'flight_from' => ['required', 'array'],
            'flight_from.id' => ['required', Rule::exists('flights', 'id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($this->flight_from->get_passengers($this['flight_from']['date'])->count() + count($this['passengers']) > Flight::$number_seats) {
                        $fail("$attribute seats is occupied");
                    }
                }
            ],
            'flight_from.date' => ['required','date_format:Y-m-d'],

            'flight_back' => ['required', 'array'],
            'flight_back.id' => ['required', Rule::exists('flights', 'id'), 
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($this->flight_from->get_passengers($this['flight_from']['date'])->count() + count($this['passengers']) > Flight::$number_seats) {
                        $fail("$attribute seats is occupied");
                    }
                }
            ],
            'flight_back.date' => ['required','date_format:Y-m-d'],

            'passengers' => ['required', 'array'],
            'passengers.*.first_name' => ['required', 'string'],
            'passengers.*.last_name' => ['required', 'string'],
            'passengers.*.birth_date' => ['required', 'date_format:Y-m-d'],
            'passengers.*.document_number' => ['required', 'digits:10'],
        ];
    }
}
