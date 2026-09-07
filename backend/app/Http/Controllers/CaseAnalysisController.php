<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCaseAnalysisRequest;
use App\Jobs\ProcessCaseAnalysisJob;
use App\Models\CaseAnalysis;
use App\Repositories\CaseRepository;
use App\Services\CaseAnalysisAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CaseAnalysisController extends Controller
{
    public function __construct(
        protected CaseRepository $caseRepository,
        protected CaseAnalysisAuditService $auditService,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->trim()->toString();

        $analysesQuery = CaseAnalysis::query()
            ->where('user_id', Auth::id())
            ->when($status && in_array($status, ['draft', 'reviewed', 'approved', 'rejected'], true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($search, function (Builder $query) use ($search): void {
                $query->where('external_case_id', 'like', "%{$search}%");
            });

        $analyses = $analysesQuery->latest()->limit(50)->get([
            'id',
            'external_case_id',
            'external_offense_id',
            'status',
            'error_message',
            'created_at',
            'updated_at',
        ]);

        $baseQuery = CaseAnalysis::where('user_id', Auth::id());

        return Inertia::render('CaseAnalysis/Index', [
            'analyses' => $analyses,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'processing' => (clone $baseQuery)->where('status', 'draft')->count(),
                'completed' => (clone $baseQuery)->whereIn('status', ['reviewed', 'approved'])->count(),
                'failed' => (clone $baseQuery)->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $analysis = CaseAnalysis::with(['evidence', 'facts', 'hypotheses', 'audits.user'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $caseData = $this->caseDataForAnalysis($analysis);

        return Inertia::render('CaseAnalysis/Show', [
            'analysis' => $analysis,
            'caseData' => $caseData,
        ]);
    }

    private function caseDataForAnalysis(CaseAnalysis $analysis): array
    {
        [$expediente, $idCarpeta] = $this->splitExternalCaseId($analysis->external_case_id);
        $caseData = $this->caseRepository->findByIdCarpeta($expediente, $idCarpeta);

        if ($caseData) {
            return $caseData;
        }

        $factsBreakdown = $analysis->facts_breakdown ?? [];

        return [
            'EXPEDIENTE' => $expediente ?: $analysis->external_case_id,
            'ID_CARPETA' => $idCarpeta ?: 'N/D',
            'TIPO' => 'Carpeta',
            'DELITO' => null,
            'MODALIDAD' => null,
            'ESTADO' => 'No disponible en la fuente externa',
            'UNIDAD' => 'No disponible en la fuente externa',
            'MUNICIPIO' => 'No disponible en la fuente externa',
            'FECHA_HECHO' => optional($analysis->fact_date)->toDateString(),
            'DESCRIPCION_HECHOS' => $factsBreakdown['narrative'] ?? '',
        ];
    }

    private function splitExternalCaseId(string $externalCaseId): array
    {
        $separatorPosition = strrpos($externalCaseId, '-');

        if ($separatorPosition === false) {
            return [$externalCaseId, ''];
        }

        return [
            substr($externalCaseId, 0, $separatorPosition),
            substr($externalCaseId, $separatorPosition + 1),
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'external_case_id' => 'required|string',
            'external_offense_id' => 'required|integer',
            'fact_narrative' => 'required|string|min:10',
            'fact_date' => 'nullable|date',
        ]);

        $analysis = CaseAnalysis::create([
            'external_case_id' => $validated['external_case_id'],
            'external_offense_id' => $validated['external_offense_id'],
            'user_id' => Auth::id(),
            'fact_date' => $validated['fact_date'] ?? null,
            'facts_breakdown' => ['narrative' => $validated['fact_narrative']],
            'status' => 'draft',
            'error_message' => null,
        ]);

        ProcessCaseAnalysisJob::dispatch($analysis, $validated['fact_narrative']);

        return response()->json([
            'message' => 'Análisis de causa iniciado en segundo plano.',
            'analysis_id' => $analysis->id,
            'status' => $analysis->status,
        ], 202);
    }

    public function update(UpdateCaseAnalysisRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        $analysis = CaseAnalysis::with('evidence')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $before = [
            'elements_status' => $analysis->elements_status ?? [],
            'suggested_diligences' => $analysis->suggested_diligences ?? [],
            'evidence' => $analysis->evidence,
            'status' => $analysis->status,
        ];

        $analysis->update([
            'elements_status' => $validated['elements_status'],
            'suggested_diligences' => $validated['suggested_diligences'],
            'status' => $validated['status'],
        ]);

        foreach ($validated['evidence'] ?? [] as $evidence) {
            $caseEvidence = $analysis->evidence()->findOrFail($evidence['id']);
            $caseEvidence->update([
                'evidence_type' => $evidence['evidence_type'],
                'source' => $evidence['source'],
                'evidence_date' => $evidence['evidence_date'] ?? null,
                'related_fact' => $evidence['related_fact'],
                'authenticity_status' => $evidence['authenticity_status'],
                'valuation_status' => $evidence['valuation_status'],
                'procedural_relation' => $evidence['procedural_relation'],
                'origin' => 'usuario',
                'is_verified' => true,
                'reviewed_by' => Auth::id() ?? $analysis->user_id,
                'reviewed_at' => now(),
            ]);
        }

        $this->auditService->recordHumanReview(
            $analysis,
            Auth::id() ?? $analysis->user_id,
            $before,
            $validated,
        );

        return redirect()->back()->with('success', 'Revisión ministerial actualizada correctamente.');
    }
}
