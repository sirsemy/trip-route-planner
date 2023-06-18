<?php

namespace App\Http\Controllers;

use App\Exceptions\PlanningException;
use App\Http\Executors\PlanTravelRoute;
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

        (new PlanTravelRoute($this))->plan();

        $this->composeSuccessResponse();

        return $this->routeResponse;
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

    /**
     * @param Collection $resultRoute
     */
    public function setResultRoute(Collection $resultRoute): void
    {
        $this->resultRoute = $resultRoute;
    }
}
