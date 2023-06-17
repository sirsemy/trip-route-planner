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
                    'third' => 0,
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
                     1 => 'sixth',
                     2 => 'third',
                     3 => 'second',
                     4 => 'fourth',
                     5 => 'fifth',
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
//    public function test_planner_response_wrong_values(): void
//    {
//        $successValues = [
//            'multipleToBePlan' => [
//                'request' => [
//                    'first' => 0,
//                    'second' => 'third',
//                    'third' => 'sixth',
//                    'fourth' => 'first',
//                    'fifth' => 'second',
//                    'sixth' => 0,
//                ],
//                'response' => [
//                     0 => 'first',
//                     1 => 'sixth',
//                     2 => 'third',
//                     3 => 'second',
//                     4 => 'fourth',
//                     5 => 'fifth',
//                ],
//            ],
//        ];
//
//        foreach ($successValues as $item) {
//            $q = $this->call(
//                'GET',
//                '/api/route_plan',
//                [
//                    'trips' => $item['request']
//                ]
//            );
//            $q->assertJsonStructure([
//                "data" => [
//                    'trip_sequence' => $item['response'],
//                ]
//            ]);
//        }
//    }

    /**
     * @return void
     * @see RoutePlanController::composeRoutePlan()
     *
     * GET /route_plan
     */
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

    /**
     * @return void
     * @see RoutePlanController::composeRoutePlan()
     *
     * GET /route_plan
     */
    public function test_trip_list_has_cross_dependent_stations(): void
    {
        $tripList = [
            'first' => 0,
            'second' => 0,
            'third' => 'second',
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $message = "Cross-dependent travel route stations not allowed. These are: 'foo => bar', 'bar => foo'";


        $this->call(
            'GET',
            '/api/route_plan',
            [
                'trips' => $tripList
            ]
        )->assertJson([
            "error" => $message
        ]);
    }
}
