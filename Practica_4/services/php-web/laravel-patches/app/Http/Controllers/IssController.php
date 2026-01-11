<?php

namespace App\Http\Controllers;

use App\Services\IssService;

/**
 * ISS Controller
 * Использует Dependency Injection и Service слой
 */
class IssController extends Controller
{
    public function __construct(
        private readonly IssService $issService
    ) {
    }

    /**
     * Страница с данными МКС
     */
    public function index()
    {
        $lastData = $this->issService->getLastData();
        $trendData = $this->issService->getTrendData();

        return view('iss', [
            'last' => $lastData?->toArray() ?? [],
            'trend' => $trendData,
            'base' => getenv('RUST_BASE') ?: 'http://rust_iss:3000'
        ]);
    }
}
