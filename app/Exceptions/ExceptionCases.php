<?php

namespace App\Exceptions;

enum ExceptionCases
{
    case StationNameNotExist;
    case CrossDependantStations;
}
