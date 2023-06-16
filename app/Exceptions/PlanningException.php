<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response as Response;

class PlanningException extends Exception
{
    public function __construct(string $errorSupplement)
    {
        parent::__construct();

        $this->set422ErrorMessage($errorSupplement);
    }

    private function set422ErrorMessage(string $errorSupplement): void
    {
        $this->message = "Route sequence criteria has not existent station name(s): " . $errorSupplement;
        $this->code = Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
