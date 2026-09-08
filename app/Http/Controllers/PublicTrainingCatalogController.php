<?php

namespace App\Http\Controllers;

class PublicTrainingCatalogController extends Controller
{
    public function index()
    {
        $trainings = config('trainings.catalog');

        return view('public.landing', compact('trainings'));
    }
}
