<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MetricsService $metrics): View
    {
        $days = in_array($request->integer('dias'), [7, 30, 90], true) ? $request->integer('dias') : 30;
        $origin = $request->query('dados'); // sinteticos | reais | null (todos)

        return view('dashboard', [
            'm' => $metrics->summary($days, match ($origin) {
                'sinteticos' => true,
                'reais' => false,
                default => null,
            }),
            'days' => $days,
            'origin' => $origin,
        ]);
    }
}
