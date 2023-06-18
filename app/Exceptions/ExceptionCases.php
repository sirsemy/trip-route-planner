<?php

namespace App\Exceptions;

enum ExceptionCases
{
    case StationNameNotExist;
    case CircularDependantStations;
    case MultipleBeforeStations;
    case MissingStarterStation;
}
