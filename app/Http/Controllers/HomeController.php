<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(SeoService $seo): Response
    {
        return Inertia::render('animatorsho/index', [
            'seo' => $seo->homePageSeoProps(),
        ]);
    }
}
