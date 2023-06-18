<?php

namespace App\Http\Controllers;

use App\Exceptions\PlanningException;
use App\Http\Helpers\CheckSubmittedParams;
use App\Http\Helpers\TripLoopChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Routing\Annotation\Route;

class RoutePlanController extends Controller
{
    private JsonResponse $routeResponse;

    private Collection $tripList;
    private Collection $resultRoute;

    /**
     * @Route("/route_plan", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|PlanningException
     */
    public function composeRoutePlan(Request $request): JsonResponse
    {
        $paramsChecker = new CheckSubmittedParams($this);

        $paramsChecker->validateParameters($request);

        $this->tripList = collect($request->input('trips'));

        $paramsChecker->checkTripListHasNotExistentDependence();
        $paramsChecker->checkHasStarterStation();
        $paramsChecker->checkHasMultipleBeforeStations();
        unset($paramsChecker);

        (new TripLoopChecker($this))->checkHasCircularDependentStations();

        $this->plan();

        $this->composeSuccessResponse();

        return $this->routeResponse;
    }

    private function plan()
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
    }

    private function getFirstDependentFreeStation(): int|string
    {
        return $this->tripList->search(static function (int|string $value, int|string $key) {
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

    private function composeSuccessResponse(): void
    {
        $this->routeResponse = response()->json(['data' => [
            'trip_sequence' => $this->resultRoute,
        ]], \Symfony\Component\HttpFoundation\Response::HTTP_OK);

        unset($this->resultRoute);
    }

    /**
     * @return Collection
     */
    public function getTripList(): Collection
    {
        return $this->tripList;
    }
}
