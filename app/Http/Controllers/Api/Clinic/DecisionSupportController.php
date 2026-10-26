<?php

namespace App\Http\Controllers\Api\Clinic;

use App\Http\Controllers\Controller;
use App\Services\ClinicAiAnalysisService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DecisionSupportController extends Controller
{
    public function __construct(private ClinicAiAnalysisService $analysis) {}

    public function analyze(Request $request): JsonResponse
    {
        if (! $this->analysis->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'AI analysis is not configured. Check the selected provider API key and model settings.',
            ], 503);
        }

        $validated = $request->validate([
            'focus_areas' => ['required', 'array', 'min:1', 'max:10'],
            'focus_areas.*' => ['required', 'array:key,title,finding,limitation'],
            'focus_areas.*.key' => ['required', 'string', 'distinct', 'in:complaint_coverage,common_complaint,complaint_concentration,complaints_by_department,complaints_by_campus,monthly_complaints,monthly_visits,common_outcome,insight_1,insight_2,insight_3,insight_4,insight_5,insight_6,insight_7,insight_8,insight_9,insight_10'],
            'focus_areas.*.title' => ['required', 'string', 'max:100'],
            'focus_areas.*.finding' => ['required', 'string', 'max:500'],
            'focus_areas.*.limitation' => ['required', 'string', 'max:500'],
        ]);

        try {
            $summaries = $this->analysis->generateInsightSummaries(
                $validated['focus_areas'],
            );
        } catch (ConnectionException|RuntimeException $exception) {
            Log::warning('Clinic AI decision-support analysis failed.', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The AI provider could not complete the analysis. Check its API key, model availability, and usage limits.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'summaries' => $summaries,
        ]);
    }
}
