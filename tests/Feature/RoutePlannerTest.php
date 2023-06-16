<?php

namespace Tests\Feature;

use App\Http\Controllers\RoutePlanController;
use Tests\TestCase;

class RoutePlannerTest extends TestCase
{
    /**
     * @return void
     * @see RoutePlanController::composeRoutePlan()
     *
     * GET /route_plan
     */
    public function test_request_parameter_missing(): void
    {
        $this->call('GET',
            '/api/route_plan',
            [

            ])->assertUnprocessable();
    }

    /**
     * @return void
     * @see RoutePlanController::composeRoutePlan()
     *
     * GET /route_plan
     */
    public function test_wrong_parameter(): void
    {
        $maxStations = config('params.station_limit');

        $assertionParameter = [
            'required' => [],
            'array' => "adff 214d-45",
            'between' => $this->generateMoreStationThanLimit($maxStations),
        ];

        $errorMessages = [
            'required' => "The trips field is required.",
            'array' => 'The trips must be an array.',
            'between' => "The trips must have between 1 and $maxStations items."
        ];

        foreach ($assertionParameter as $key => $value) {
            $this->call('GET',
                '/api/route_plan',
                [
                    'trips' => $value
                ])->assertJson([
                "error" => [
                    "trips" => [
                        $errorMessages[$key]
                    ]
                ]
            ]);
        }
    }

    private function generateMoreStationThanLimit(int $maxStations): array
    {
        $stations = [];

        for ($i = 0; $i < $maxStations + 1; $i++) {
            $stations["station$i"] = 0;
        }

        return $stations;
    }

    /**
     * @return void
     * @see RoutePlanController::composeRoutePlan()
     *
     * GET /route_plan
     */
    public function test_planner_returns_a_successful_response(): void
    {
        $tripList = [
          'first' => 0,
          'second' => 0,
        ];

        $this->call(
            'GET',
            '/api/route_plan',
            [
                'trips' => $tripList
            ]
        )->assertStatus(200);
    }

    public function test_trip_list_has_not_existent_station_dependence(): void
    {
        $failureValues = [
            'oneWrongName' => [
                'tripList' => [
                    'first' => 0,
                    'second' => 0,
                    'third' => 'second',
                    'fourth' => 'foo',
                    'fifth' => 'first',
                ],
                'message' => "Route sequence criteria has not existent station name(s): foo"
            ],
            'moreWrongNames' => [
                'tripList' => [
                    'first' => 0,
                    'second' => 0,
                    'third' => 'second',
                    'fourth' => 'foo',
                    'fifth' => 'bar',
                ],
                'message' => "Route sequence criteria has not existent station name(s): foo, bar"
            ]
        ];

        foreach ($failureValues as $item) {
            $this->call(
                'GET',
                '/api/route_plan',
                [
                    'trips' => $item['tripList']
                ]
            )->assertJson([
                "error" => $item['message']
            ]);
        }
    }


}
