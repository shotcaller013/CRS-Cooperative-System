<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\AgingReportRequest;
use App\Http\Requests\Report\CollectionSummaryRequest;
use App\Http\Requests\Report\OutstandingBalanceRequest;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ExportService $exportService,
    ) {}

    public function collection(CollectionSummaryRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = $this->reportService->collectionSummary($request->validated());
        return $this->respond($request, $data, 'collection', 'Collection Summary');
    }

    public function aging(AgingReportRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = $this->reportService->agingReport($request->validated());
        return $this->respond($request, $data, 'aging', 'Aging Report');
    }

    public function outstanding(OutstandingBalanceRequest $request): JsonResponse|BinaryFileResponse
    {
        $data = $this->reportService->outstandingBalance($request->validated());
        return $this->respond($request, $data, 'outstanding', 'Outstanding Balance');
    }

    private function respond(
        $request, array $data, string $type, string $title
    ): JsonResponse|BinaryFileResponse {
        $export = $request->input('export');

        if ($export === 'excel') {
            $path = $this->exportService->toExcel($type, $data, [
                'include_summary_sheet' => true,
            ]);
            return response()->download(
                $path,
                str_replace(' ', '_', $title) . '_' . now()->format('Ymd') . '.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        }

        if ($export === 'pdf') {
            $path = $this->exportService->toPdf($type, $data);
            return response()->download(
                $path,
                str_replace(' ', '_', $title) . '_' . now()->format('Ymd') . '.pdf',
                ['Content-Type' => 'application/pdf']
            )->deleteFileAfterSend(true);
        }

        return response()->json(['data' => $data]);
    }
}
