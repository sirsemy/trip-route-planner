<?php

namespace App\Http\Helpers;

use App\Exceptions\PlanningException;
use App\Exceptions\ExceptionCases;
use App\Http\Controllers\RoutePlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckSubmittedParams
{
    private RoutePlanController $routePlanContr;
    private Collection $collectedErrors;

    public function __construct(RoutePlanController $rp)
    {
        $this->routePlanContr = $rp;
        $this->collectedErrors = collect();
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

        foreach ($tripList as $value) {
            if (!empty($value) && !$tripList->has($value)) {
                $this->collectedErrors->add($value);
            }
        }

        $this->throwExceptionIfHaveWrongValues(ExceptionCases::StationNameNotExist);
    }

    /**
     * @throws PlanningException
     */
    public function checkHasStarterStation(): void
    {
        $tripList = $this->routePlanContr->getTripList();

        $starterStations = $tripList->search(0);

        if (!$starterStations) {
            throw new PlanningException(ExceptionCases::MissingStarterStation);
        }
    }

    /**
     * @throws PlanningException
     */
    public function checkHasMultipleBeforeStations(): void
    {
        $tripList = $this->routePlanContr->getTripList();

        $this->collectedErrors = $tripList->duplicates()->whereNotNull()
            ->filter(fn (int|string $value) => $value !== 0);

        $this->throwExceptionIfHaveWrongValues(ExceptionCases::MultipleBeforeStations);
    }

    /**
     * @throws PlanningException
     */
    private function throwExceptionIfHaveWrongValues(ExceptionCases $errorCase): void
    {
        if ($this->collectedErrors->isNotEmpty()) {
            $errorSupplement = $this->collectedErrors->implode( ', ');
            $this->collectedErrors = collect();

            throw new PlanningException($errorCase, $errorSupplement);
        }
    }
}
