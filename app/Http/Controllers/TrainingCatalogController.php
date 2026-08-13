<?php

namespace App\Http\Controllers;

class TrainingCatalogController extends Controller
{
    public function index()
    {
        $trainings = config('trainings.catalog');

        return view('trainings.catalog', compact('trainings'));
    }
}
