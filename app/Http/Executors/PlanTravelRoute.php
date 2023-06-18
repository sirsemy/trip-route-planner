<?php

namespace App\Http\Executors;

use App\Http\Controllers\RoutePlanController;
use Illuminate\Support\Collection;

class PlanTravelRoute
{
    private Collection $tripList;
    private Collection $resultRoute;
    private RoutePlanController $routePlanContr;

    public function __construct(RoutePlanController $rp)
    {
        $this->routePlanContr = $rp;
        $this->tripList = $this->routePlanContr->getTripList();
    }

    public function plan(): void
    {
        $routs = collect($this->tripList);

        $firstStation = $this->getFirstDependentFreeStation();
        $this->resultRoute = collect();

        foreach ($routs as $station => $beforeStat) {
            if ($this->resultRoute->isEmpty()) {
                $this->iterateBeforeStationsChain($firstStation, $routs);
                unset($firstStation);
                continue;
            }

            $hasStationInList = $this->resultRoute->search($beforeStat, true);
            $hasBeforeStatInList = $this->resultRoute->search($station, true);

            if ($hasBeforeStatInList && $hasStationInList) {
                continue;
            }

            if (!empty($beforeStat) && $hasStationInList === false) {
                $this->resultRoute->add($beforeStat);
            }

            if ($hasBeforeStatInList) {
                continue;
            }

            $this->iterateBeforeStationsChain($station, $routs);
        }

        $this->routePlanContr->setResultRoute($this->resultRoute);
    }

    private function getFirstDependentFreeStation(): int|string
    {
        return $this->tripList->search(static function (int|string $value) {
            return empty($value);
        });
    }

    private function iterateBeforeStationsChain(int|string $station, Collection $routs): void
    {
        $beforeStation = $routs->search($station);

        $this->resultRoute->add($station);

        if (!empty($beforeStation)) {
            $this->iterateBeforeStationsChain($beforeStation, $routs);
        }
    }
}
