<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Requests\Loan\UpdateLoanRequest;
use App\Http\Resources\LoanCollection;
use App\Http\Resources\LoanResource;
use App\Http\Resources\LoanTypeResource;
use App\Models\Loan;
use App\Models\LoanType;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private readonly LoanService $loanService)
    {
        $this->authorizeResource(Loan::class, 'loan');
    }

    public function index(Request $request): LoanCollection
    {
        $loans = $this->loanService->paginate(
            $request->only(['status', 'member_id', 'loan_type_id', 'search', 'sort_by', 'sort_dir']),
            (int) $request->get('per_page', 15)
        );

        return new LoanCollection($loans);
    }

    public function store(StoreLoanRequest $request): JsonResponse
    {
        $loan = $this->loanService->create($request->validated());

        return (new LoanResource($loan))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Loan $loan): LoanResource
    {
        return new LoanResource(
            $this->loanService->find($loan->id)
        );
    }

    public function update(UpdateLoanRequest $request, Loan $loan): LoanResource
    {
        $updated = $this->loanService->update($loan, $request->validated());
        return new LoanResource($updated);
    }

    public function destroy(Loan $loan): JsonResponse
    {
        $this->loanService->delete($loan);
        return response()->json(['message' => 'Loan deleted successfully.']);
    }

    // POST /api/v1/loans/{loan}/approve
    public function approve(Request $request, Loan $loan): LoanResource
    {
        $this->authorize('approve', $loan);

        $request->validate([
            'approved_by_hr'   => 'nullable|string|max:150',
            'approved_by_coop' => 'nullable|string|max:150',
        ]);

        $updated = $this->loanService->approve($loan, $request->all());
        return new LoanResource($updated);
    }

    // GET /api/v1/loans/pipeline
    public function pipeline(): JsonResponse
    {
        $this->authorize('viewAny', Loan::class);
        return response()->json(['data' => $this->loanService->pipeline()]);
    }

    // GET /api/v1/loan-types
    public function loanTypes(): JsonResponse
    {
        $types = LoanType::where('is_active', true)->get();
        return response()->json(['data' => LoanTypeResource::collection($types)]);
    }

    // POST /api/v1/loans/calculate
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'amount'      => 'required|numeric|min:1',
            'term_months' => 'required|integer|min:1',
            'frequency'   => 'required|in:monthly,bimonthly,weekly',
            'annual_rate' => 'required|numeric|min:0',
        ]);

        $calc = $this->loanService->computeSchedule(
            (float) $request->amount,
            (int)   $request->term_months,
            $request->frequency,
            (float) $request->annual_rate
        );

        return response()->json(['data' => $calc]);
    }
}
