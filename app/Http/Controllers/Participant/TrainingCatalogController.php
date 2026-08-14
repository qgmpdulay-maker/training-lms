<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;

class TrainingCatalogController extends Controller
{
    public function index()
    {
        $trainings = config('trainings.catalog');

        return view('participant.trainings.index', compact('trainings'));
    }
}
