<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\MetricsService;

/**
 * 責任者・店長・オーナー用の集計ダッシュボード（§6）。
 * 数値は現場支援のための材料であり、単純比較で評価しない。
 */
class DashboardController extends Controller
{
    public function index(MetricsService $metrics)
    {
        return view('manager.dashboard', [
            'summary' => $metrics->storeSummary(),
            'casts' => $metrics->castBreakdown(),
            'assets' => $metrics->customerAssets(),
            'month' => now()->format('Y年n月'),
        ]);
    }
}
