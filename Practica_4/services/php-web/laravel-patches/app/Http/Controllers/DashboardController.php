<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IssService;
use App\Services\JwstService;
use App\Services\CmsService;
use App\DTO\DashboardMetricsDTO;

/**
 * Dashboard Controller
 * Использует Dependency Injection для всех зависимостей
 * Вся бизнес-логика вынесена в Service слой
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly IssService $issService,
        private readonly JwstService $jwstService,
        private readonly CmsService $cmsService
    ) {
    }

    /**
     * Главная страница дашборда
     */
    public function index()
    {
        // Получаем данные через сервисы
        $issData = $this->issService->getLastData();
        $metrics = DashboardMetricsDTO::fromIssData($issData);
        
        // Получаем CMS блок для дашборда
        $experimentBlock = $this->cmsService->getBlockContent('dashboard_experiment', '<div class="text-muted">блок не найден</div>');

        return view('dashboard', [
            'iss' => $issData?->toArray() ?? [],
            'trend' => [], // фронт сам заберёт /api/iss/trend (через nginx прокси)
            'jw_gallery' => [], // не нужно сервером
            'jw_observation_raw' => [],
            'jw_observation_summary' => [],
            'jw_observation_images' => [],
            'jw_observation_files' => [],
            'metrics' => $metrics->toArray(),
            'cms_experiment_content' => $experimentBlock,
        ]);
    }

    /**
     * /api/jwst/feed — серверный прокси/нормализатор JWST картинок.
     * QS:
     *  - source: jpg|suffix|program (default jpg)
     *  - suffix: напр. _cal, _thumb, _crf
     *  - program: ID программы (число)
     *  - instrument: NIRCam|MIRI|NIRISS|NIRSpec|FGS
     *  - page, perPage
     */
    public function jwstFeed(Request $request)
    {
        $params = [
            'source' => $request->query('source', 'jpg'),
            'suffix' => $request->query('suffix', ''),
            'program' => $request->query('program', ''),
            'instrument' => $request->query('instrument', ''),
            'page' => $request->query('page', 1),
            'perPage' => $request->query('perPage', 24),
        ];

        $result = $this->jwstService->getFeed($params);

        return response()->json($result);
    }
}
