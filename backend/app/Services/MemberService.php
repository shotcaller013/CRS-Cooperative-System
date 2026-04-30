<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class MemberService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Member::withCount(['loans', 'activeLoans']);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('member_status', $filters['status']);
        }

        if (!empty($filters['emp_status'])) {
            $query->where('status', $filters['emp_status']);
        }

        if (!empty($filters['company'])) {
            $query->where('company', $filters['company']);
        }

        $sortBy  = $filters['sort_by']  ?? 'member_no';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function find(int $id): Member
    {
        return Member::with(['loans.loanType', 'creator'])->findOrFail($id);
    }

    public function create(array $data): Member
    {
        $data['created_by'] = Auth::id();
        return Member::create($data);
    }

    public function update(Member $member, array $data): Member
    {
        $member->update($data);
        return $member->fresh();
    }

    public function delete(Member $member): void
    {
        $member->delete();
    }

    public function listForDropdown(): \Illuminate\Database\Eloquent\Collection
    {
        return Member::active()
            ->select('id', 'member_no', 'first_name', 'last_name', 'status', 'monthly_salary')
            ->orderBy('last_name')
            ->get();
    }
}
