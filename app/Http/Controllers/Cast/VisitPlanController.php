<?php

namespace App\Http\Controllers\Cast;

use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * キャストが顧客カードから来店予定を登録する（§9-2）。黒服画面に表示される。
 */
class VisitPlanController extends Controller
{
    public function store(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'planned_date' => ['required', 'date'],
            'planned_time' => ['nullable', 'date_format:H:i'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:99'],
            'dohan' => ['nullable', 'boolean'],
            'bottle_note' => ['nullable', 'string', 'max:100'],
            'prep' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $customer->customer->visitPlans()->create([
            'store_id' => $customer->store_id,
            'cast_id' => $customer->cast_id,
            'planned_date' => $data['planned_date'],
            'planned_time' => $data['planned_time'] ?? null,
            'party_size' => $data['party_size'] ?? null,
            'dohan' => (bool) ($data['dohan'] ?? false),
            'bottle_note' => $data['bottle_note'] ?? null,
            'prep' => $data['prep'] ?? null,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '来店予定を登録しました。黒服・店長に共有されます。')
            ->withFragment('visits');
    }
}
