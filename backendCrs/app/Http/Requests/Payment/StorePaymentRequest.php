<?php
// app/Http/Requests/Payment/StorePaymentRequest.php
namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Payment::class);
    }

    public function rules(): array
    {
        return [
            'loan_id'       => 'required|exists:loans,id',
            'schedule_id'   => 'required|exists:amortization_schedules,id',
            'amount_paid'   => 'required|numeric|min:0.01',
            'payment_type'  => 'required|in:full,partial,advance,penalty',
            'or_number'     => 'nullable|string|max:50',
            'payment_date'  => 'required|date|before_or_equal:today',
            'penalty_paid'  => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
        ];
    }
}
