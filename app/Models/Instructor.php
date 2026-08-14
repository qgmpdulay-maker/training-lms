<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'training_type', 'certificate_code', 'deployment', 'agency_organization', 'lgu', 'region', 'rating'])]
class Instructor extends Model
{
    //
}
