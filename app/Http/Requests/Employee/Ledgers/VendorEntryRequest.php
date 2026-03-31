<?php

namespace App\Http\Requests\Employee\Ledgers;

use App\Http\Requests\Concerns\HasAttachmentRules;
use App\Http\Requests\Concerns\HasLedgerEntryRules;
use App\Support\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorEntryRequest extends FormRequest
{
    use HasAttachmentRules;
    use HasLedgerEntryRules;

    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(Role::EMPLOYEE_B);
    }

    public function rules(): array
    {
        $venueId = (int) $this->session()->get('selected_venue_id');

        return array_merge($this->ledgerEntryRules(), $this->attachmentRules(), [
            'venue_vendor_id' => [
                'required',
                'integer',
                Rule::exists('venue_vendors', 'id')->where(function ($query) use ($venueId) {
                    $query->where('venue_id', $venueId);
                }),
            ],
        ]);
    }
}
