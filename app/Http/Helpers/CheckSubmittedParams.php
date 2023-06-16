<?php

namespace App\Http\Helpers;

use App\Exceptions\PlanningException;
use App\Exceptions\ExceptionCases;
use App\Http\Controllers\RoutePlanController;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckSubmittedParams
{
    private RoutePlanController $routePlanContr;

    public function __construct(RoutePlanController $rp)
    {
        $this->routePlanContr = $rp;
    }

    /**
     * @throws ValidationException
     */
    public function validateParameters(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'trips' => 'required|array|between:1,'.config('params.station_limit'),
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @throws PlanningException
     */
    public function checkTripListHasNotExistentDependence(): void
    {
        $tripList = $this->routePlanContr->getTripList();

        $falseDependence = [];

        foreach ($tripList as $value) {
            if (!empty($value) && !array_key_exists($value, $tripList)) {
                $falseDependence[] = $value;
            }
        }

        if (!empty($falseDependence)) {
            $errorSupplement = implode(', ', $falseDependence);

            throw new PlanningException($errorSupplement);
        }
    }
}
