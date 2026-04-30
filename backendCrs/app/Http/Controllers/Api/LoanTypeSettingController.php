<?php
// app/Http/Controllers/Api/LoanTypeSettingController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoanType\StoreLoanTypeRequest;
use App\Http\Requests\LoanType\UpdateLoanTypeRequest;
use App\Http\Resources\LoanTypeResource;
use App\Models\LoanType;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanTypeSettingController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', \App\Models\CoopProfile::class);
        return LoanTypeResource::collection($this->settings->getLoanTypes());
    }

    public function store(StoreLoanTypeRequest $request): LoanTypeResource
    {
        $loanType = $this->settings->createLoanType($request->validated());
        return new LoanTypeResource($loanType);
    }

    public function show(LoanType $loanType): LoanTypeResource
    {
        $this->authorize('viewAny', \App\Models\CoopProfile::class);
        return new LoanTypeResource($loanType->load('approvalThresholds'));
    }

    public function update(UpdateLoanTypeRequest $request, LoanType $loanType): LoanTypeResource
    {
        $updated = $this->settings->updateLoanType($loanType, $request->validated());
        return new LoanTypeResource($updated);
    }

    public function destroy(LoanType $loanType): JsonResponse
    {
        $this->authorize('edit-setting');
        $this->settings->deleteLoanType($loanType);
        return response()->json(['message' => 'Loan type deleted.']);
    }
}
