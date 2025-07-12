<?php

namespace App\ApiResource\Person\Details\Model;

use App\ApiResource\Person\Details\Model\MoviesSummary\Summary;
use App\ApiResource\Person\Details\Model\PersonDetails\Details;

class PersonDetailsOutput {
    public private(set) Details $personDetails;
    public private(set) Summary $moviesSummary;
}