<?php

namespace App\Http\Controllers\Cast;

use App\Enums\AlertCategory;
use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 重大注意情報の記録（§5-5-D）。事実と主観を分けて記録する。
 */
class CustomerAlertController extends Controller
{
    public function store(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(AlertCategory::options()))],
            'fact' => ['required', 'string', 'max:1000'],
            'subjective' => ['nullable', 'string', 'max:1000'],
            'severity' => ['required', Rule::in(['low', 'mid', 'high'])],
        ]);

        $customer->customer->alerts()->create([
            'store_id' => $customer->store_id,
            'cast_id' => $customer->cast_id,
            'category' => $data['category'],
            'fact' => $data['fact'],
            'subjective' => $data['subjective'] ?? null,
            'severity' => $data['severity'],
        ]);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '重大注意情報を記録しました。')
            ->withFragment('alerts');
    }
}
