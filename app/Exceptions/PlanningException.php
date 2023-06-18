<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response as Response;

class PlanningException extends Exception
{
    public function __construct(ExceptionCases $errorCase, string $errorSupplement = '')
    {
        parent::__construct();

        match ($errorCase) {
            ExceptionCases::StationNameNotExist => $this->set422ErrorMessage(
                "Route sequence criteria has not existent station name(s): " . $errorSupplement),
            ExceptionCases::CircularDependantStations => $this->set422ErrorMessage(
                "Not should be circular dependent stations. Those are: " . $errorSupplement),
            ExceptionCases::MultipleBeforeStations => $this->set422ErrorMessage(
                "Multiple before stations not allowed for one station. These are: " . $errorSupplement),
            ExceptionCases::MissingStarterStation => $this->set422ErrorMessage(
                "Needs at least one starter station without dependent!")
        };
    }

    private function set422ErrorMessage(string $errorMessage): void
    {
        $this->message = $errorMessage;
        $this->code = Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
