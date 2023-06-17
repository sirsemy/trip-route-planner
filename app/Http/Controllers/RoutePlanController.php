<?php

namespace App\Http\Controllers;

use App\Exceptions\PlanningException;
use App\Http\Helpers\CheckSubmittedParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Routing\Annotation\Route;

class RoutePlanController extends Controller
{
    private JsonResponse $routeResponse;

    private array $tripList;
    private array $resultRoute;

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
        $paramsChecker->checkHaveCrossDependentStations();

        $this->plan();

        $this->composeSuccessResponse();

        return $this->routeResponse;
    }

    private function plan()
    {
        $tripStationKeys = array_keys($this->tripList);

        foreach ($this->tripList as $station => $beforeStat) {
            if (empty($beforeStat)) {
                $this->resultRoute[$station] = $beforeStat - 1;
                continue;
            } elseif (!key_exists($beforeStat, $this->resultRoute) && !key_exists($station, $this->resultRoute)) {
                $this->resultRoute[$beforeStat] = $this->getSpecifiedIndexNumber($tripStationKeys, $station) - 1;
            } elseif (key_exists($station, $this->resultRoute) &&
                $this->resultRoute[$station] < $this->getSpecifiedIndexNumber($tripStationKeys, $beforeStat)
            ) {
                $this->resultRoute[$beforeStat] = $this->resultRoute[$station] - 1;
            }

            if (!key_exists($station, $this->resultRoute)) {
                $this->resultRoute[$station] = $this->getSpecifiedIndexNumber($tripStationKeys, $station);
            }
        }

        asort($this->resultRoute);
        $this->resultRoute = array_keys($this->resultRoute);
    }

    private function getSpecifiedIndexNumber(array $filterable, int|string $searchable): int
    {
        $oneElement = array_filter($filterable,
            static function(int|string $value) use ($searchable) {
                return $value === $searchable;
            });

        return array_key_first($oneElement);
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
