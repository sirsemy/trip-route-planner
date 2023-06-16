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
    }

    /**
     * @return array
     */
    public function getTripList(): array
    {
        return $this->tripList;
    }
}
