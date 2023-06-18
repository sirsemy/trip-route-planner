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

        $falseDependence = collect();

        foreach ($tripList as $value) {
            if (!empty($value) && !$tripList->has($value)) {
                $falseDependence->add($value);
            }
        }

        if ($falseDependence->isNotEmpty()) {
            $errorSupplement = $falseDependence->implode($falseDependence, ', ');

            throw new PlanningException(ExceptionCases::StationNameNotExist, $errorSupplement);
        }
    }

    /**
     * @throws PlanningException
     */
    public function checkHasMultipleBeforeStations(): void
    {
        $tripList = $this->routePlanContr->getTripList();

        $multipleStations = $tripList->duplicates()->whereNotNull()->filter(fn (int|string $value) => $value !== 0);

        if ($multipleStations->isNotEmpty()) {
            $errorSupplement = $multipleStations->implode( ', ');

            throw new PlanningException(ExceptionCases::MultipleBeforeStations, $errorSupplement);
        }
    }

    /**
     * @throws PlanningException
     */
    public function checkHasCircularDependentStations(): void
    {
        $tripList = $this->routePlanContr->getTripList();

        $circularDependents = collect();



        if ($circularDependents->isNotEmpty()) {
            $errorSupplement = $circularDependents->implode($circularDependents, ' => ');

            throw new PlanningException(ExceptionCases::CircularDependantStations, $errorSupplement);
        }
    }

}
