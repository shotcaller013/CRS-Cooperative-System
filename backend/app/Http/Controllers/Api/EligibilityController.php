<?php
// app/Http/Controllers/Api/EligibilityController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoanType;
use App\Models\Member;
use App\Services\EligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EligibilityController extends Controller
{
    public function __construct(private EligibilityService $eligibility) {}

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id'    => 'required|exists:members,id',
            'loan_type_id' => 'required|exists:loan_types,id',
            'amount'       => 'required|numeric|min:0.01',
        ]);

        $member   = Member::findOrFail($data['member_id']);
        $loanType = LoanType::findOrFail($data['loan_type_id']);

        $result = $this->eligibility->check($member, $loanType, (float) $data['amount']);

        if ($result['eligible']) {
            return response()->json(['eligible' => true, 'checks' => $result['checks']]);
        }

        return response()->json([
            'eligible' => false,
            'checks'   => $result['checks'],
            'reasons'  => collect($result['checks'])->where('pass', false)->pluck('reason')->values(),
        ], 422);
    }
}
