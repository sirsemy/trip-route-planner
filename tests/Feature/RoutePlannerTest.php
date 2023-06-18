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
    public function test_wrong_parameter_structure(): void
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

    /**
     * @return void
     * @see RoutePlanController::composeRoutePlan()
     *
     * GET /route_plan
     */
    public function test_planner_response_proper_values(): void
    {
        $successValues = [
            'simple' => [
                'input' => [
                    'first' => 0,
                    'second' => 0,
                    'third' => 0,
                ],
                'output' => [
                    0 => 'first',
                    1 => 'second',
                    2 => 'third',
                ],
            ],
            'oneToBePlan' => [
                'input' => [
                    'first' => 0,
                    'second' => 'third',
                    'third' => 'first',
                ],
                'output' => [
                    0 => 'first',
                    1 => 'third',
                    2 => 'second',
                ],
            ],
            'multipleToBePlan' => [
                'input' => [
                    'first' => 0,
                    'second' => 'third',
                    'third' => 'sixth',
                    'fourth' => 'first',
                    'fifth' => 'second',
                    'sixth' => 0,
                ],
                'output' => [
                     0 => 'first',
                     1 => 'fourth',
                     2 => 'third',
                     3 => 'second',
                     4 => 'fifth',
                     5 => 'sixth',
                ],
            ],
        ];

        foreach ($successValues as $item) {
            $q = $this->call(
                'GET',
                '/api/route_plan',
                [
                    'trips' => $item['input']
                ]
            );
            $q->assertJson([
                "data" => [
                    'trip_sequence' => $item['output'],
                ]
            ]);
        }
    }

    /**
     * @return void
     * @see RoutePlanController::composeRoutePlan()
     *
     * GET /route_plan
     */
    public function test_planner_fail_with_confusing_criteria(): void
    {
        $failingValues = [
            'A' => 'I',
            'B' => 'F',
            'C' => 'E',
            'D' => 'G',
            'E' => 'A',
            'F' => 'C',
            'G' => 'E',
            'H' => 'A',
            'I' => 0,
        ];

        $q = $this->call(
            'GET',
            '/api/route_plan',
            [
                'trips' => $failingValues
            ]
        );
        $q->assertBadRequest();
    }

    /**
     * @return void
     * @see RoutePlanController::composeRoutePlan()
     *
     * GET /route_plan
     */
    public function test_trip_list_has_wrong_values(): void
    {
        $failureValues = [
            'oneNotexistentName' => [
                'tripList' => [
                    'first' => 0,
                    'second' => 0,
                    'third' => 'second',
                    'fourth' => 'foo',
                    'fifth' => 'first',
                ],
                'message' => "Route sequence criteria has not existent station name(s): foo"
            ],
            'moreNotexistentNames' => [
                'tripList' => [
                    'first' => 0,
                    'second' => 0,
                    'third' => 'second',
                    'fourth' => 'foo',
                    'fifth' => 'bar',
                ],
                'message' => "Route sequence criteria has not existent station name(s): foo, bar"
            ],
            'multipleBeforeStations' => [
                'tripList' => [
                    'first' => 0,
                    'second' => 0,
                    'third' => 'first',
                    'foo' => 'bar',
                    'bar' => 'first',
                    'fourth' => 0,
                    'sixth' => 'bar',
                    'seventh' => 0,
                ],
                'message' => "Multiple before stations not allowed for one station. These are: first, bar",
            ],
            'missingStarterStation' => [
                'tripList' => [
                    'third' => 'foo',
                    'foo' => 'bar',
                    'bar' => 'sixth',
                    'sixth' => 'third',
                ],
                'message' => 'Needs at least one starter station without dependent!',
            ],
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
