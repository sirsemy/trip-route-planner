<?php

namespace App\Http\Helpers;

use App\Exceptions\ExceptionCases;
use App\Exceptions\PlanningException;
use App\Http\Controllers\RoutePlanController;
use Illuminate\Support\Collection;

class TripLoopChecker
{
    private RoutePlanController $routePlanContr;
    private Collection $circularDependents;
    private Collection $tripList;

    private int|string $firstStation;
    private bool $hasLoop;

    public function __construct(RoutePlanController $rp)
    {
        $this->routePlanContr = $rp;
        $this->tripList = $this->routePlanContr->getTripList();
        $this->circularDependents = collect();
    }

    /**
     * @throws PlanningException
     */
    public function checkHasCircularDependentStations(): void
    {
        $this->hasLoop = false;

        foreach ($this->tripList as $station => $beforeStat) {
            if (empty($beforeStat)) {
                continue;
            }

            $this->firstStation = $beforeStat;

            $this->circularDependents->add($beforeStat);
            $this->checkTripListDependenciesLoop($station);

            if ($this->hasLoop) {
                break;
            }

            $this->circularDependents = collect();
        }

        $this->throwExceptionIfHasLoop();
    }

    private function checkTripListDependenciesLoop(int|string $station): void
    {
        $beforeStation = $this->tripList->search($station);

        if (!empty($beforeStation) && $beforeStation === $this->firstStation) {
            $this->circularDependents->add($station);
            $this->circularDependents->add($beforeStation);
            $this->hasLoop = true;
        } elseif (!empty($beforeStation)) {
            $this->circularDependents->add($station);
            $this->checkTripListDependenciesLoop($beforeStation);
        }
    }

    /**
     * @throws PlanningException
     */
    private function throwExceptionIfHasLoop(): void
    {
        if ($this->circularDependents->isNotEmpty()) {
            $errorSupplement = $this->circularDependents->implode(' => ');

            throw new PlanningException(ExceptionCases::CircularDependantStations, $errorSupplement);
        }
    }
}
