<?php

namespace App\Http\Controllers\Cast;

use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\CustomerBottle;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * キープボトルの登録・空き更新。黒服が来店時に即確認できる（§5-7・§5-10）。
 */
class CustomerBottleController extends Controller
{
    public function store(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'opened_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->customer->bottles()->create([
            'store_id' => $customer->store_id,
            'name' => $data['name'],
            'opened_on' => $data['opened_on'] ?? now()->toDateString(),
            'status' => 'kept',
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', 'ボトルを登録しました。')
            ->withFragment('bottles');
    }

    public function markEmpty(CustomerBottle $bottle): RedirectResponse
    {
        // 店舗スコープはグローバルスコープで担保。関係の編集権限を確認。
        $rel = CastCustomerRelationship::where('customer_id', $bottle->customer_id)
            ->where('cast_id', CustomerAccess::currentCastId())->first();
        abort_if($rel === null, 403);

        $bottle->update(['status' => 'empty']);

        return back()->with('status', 'ボトルを空きにしました。');
    }
}
