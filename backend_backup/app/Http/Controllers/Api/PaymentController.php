<?php
// app/Http/Controllers/Api/PaymentController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service)
    {
        $this->authorizeResource(Payment::class, 'payment');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = $this->service->paginate($request->only(['loan_id', 'date_from', 'date_to']));
        return PaymentResource::collection($payments);
    }

    public function store(StorePaymentRequest $request): PaymentResource
    {
        $payment = $this->service->record($request->validated());
        return new PaymentResource($payment);
    }

    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource($payment->load(['loan.member', 'schedule', 'receiver']));
    }

    public function byLoan(int $loanId): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        $payments = $this->service->getByLoan($loanId);
        return response()->json(['data' => PaymentResource::collection($payments)]);
    }
}
