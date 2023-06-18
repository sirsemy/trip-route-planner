<?php

namespace App\Http\Controllers;

use App\Exceptions\PlanningException;
use App\Http\Helpers\CheckSubmittedParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Routing\Annotation\Route;

class RoutePlanController extends Controller
{
    private JsonResponse $routeResponse;

    private array $tripList;
    private Collection $resultRoute;
//    private array $resultRoute;

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

        $this->tripList = $request->input('trips');

        $paramsChecker->checkTripListHasNotExistentDependence();
        $paramsChecker->checkHasCrossDependentStations();

        $this->plan();

        $this->composeSuccessResponse();

        return $this->routeResponse;
    }

    private function plan()
    {
        $routs = collect($this->tripList);

        $this->resultRoute = new Collection();

        foreach ($routs as $station => $beforeStat) {
            $isStationInList = $this->resultRoute->search($beforeStat, true);
            $isBeforeStatInList = $this->resultRoute->search($station, true);

            if ($isBeforeStatInList && $isStationInList) {
                continue;
            }

            if (!empty($beforeStat) && $isStationInList === false) {
                $this->resultRoute->add($beforeStat);
            }

            if ($isBeforeStatInList) {
                continue;
            }

            $this->iterateBeforeStationsChain($station, $routs);
        }
    }

    private function iterateBeforeStationsChain(int|string $nextStation, Collection $routs)
    {
        $nextRoute = $routs->search($nextStation);

        $this->resultRoute->add($nextStation);

        if (!empty($nextRoute)) {
            $this->iterateBeforeStationsChain($nextRoute, $routs);
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
     * @return array
     */
    public function getTripList(): array
    {
        return $this->tripList;
    }
}
