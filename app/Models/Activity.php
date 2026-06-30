<?php

namespace App\Models;

use App\Traits\HasTenant;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use HasTenant;
}
