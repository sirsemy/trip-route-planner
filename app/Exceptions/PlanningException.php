<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response as Response;

class PlanningException extends Exception
{
    public function __construct(ExceptionCases $errorCase, string $errorSupplement)
    {
        parent::__construct();

        match ($errorCase) {
            ExceptionCases::StationNameNotExist => $this->set422ErrorMessage(
                "Route sequence criteria has not existent station name(s): " . $errorSupplement),
            ExceptionCases::CircularDependantStations => $this->set422ErrorMessage(
                "Cross-dependent travel route stations not allowed. These are: " . $errorSupplement),
            ExceptionCases::MultipleBeforeStations => $this->set422ErrorMessage(
                "Multiple before stations not allowed for one station. These are: " . $errorSupplement
            ),
        };
    }

    private function set422ErrorMessage(string $errorMessage): void
    {
        $this->message = $errorMessage;
        $this->code = Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
