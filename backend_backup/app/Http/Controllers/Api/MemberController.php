<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\MemberCollection;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $memberService)
    {
        $this->authorizeResource(Member::class, 'member');
    }

    public function index(Request $request): MemberCollection
    {
        $members = $this->memberService->paginate(
            $request->only(['search', 'status', 'emp_status', 'company', 'sort_by', 'sort_dir']),
            (int) $request->get('per_page', 15)
        );

        return new MemberCollection($members);
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $member = $this->memberService->create($request->validated());

        return (new MemberResource($member))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Member $member): MemberResource
    {
        return new MemberResource(
            $this->memberService->find($member->id)
        );
    }

    public function update(UpdateMemberRequest $request, Member $member): MemberResource
    {
        $updated = $this->memberService->update($member, $request->validated());
        return new MemberResource($updated);
    }

    public function destroy(Member $member): JsonResponse
    {
        $this->memberService->delete($member);
        return response()->json(['message' => 'Member deleted successfully.']);
    }

    // Dropdown endpoint (no pagination)
    public function dropdown(): JsonResponse
    {
        $this->authorize('viewAny', Member::class);
        $members = $this->memberService->listForDropdown();
        return response()->json(['data' => $members]);
    }
}
