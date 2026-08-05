<?php

namespace App\Http\Controllers\Cast;

use App\Enums\AfterStatus;
use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\Visit;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * キャストが「今日この客とアフターに行けそうか」を申告する。
 * 店側（店長・オーナー）はこの申告を集約して「誰が誰とアフターか」を管理できる。
 */
class AfterStatusController extends Controller
{
    public function update(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'after_status' => ['required', Rule::in(array_keys(AfterStatus::options()))],
            'after_status_note' => ['nullable', 'string', 'max:255'],
        ]);

        // 対象顧客の来店中(なければ最新の当日来店)を探す
        $today = now()->toDateString();
        $visit = Visit::where('customer_id', $customer->customer_id)
            ->where('status', 'present')
            ->orderByDesc('arrived_at')->first()
            ?? Visit::where('customer_id', $customer->customer_id)
                ->whereDate('arrived_at', $today)
                ->orderByDesc('arrived_at')->first();

        if (! $visit) {
            return back()->withErrors(['after_status' => 'この顧客の本日の来店が見つかりません。来店中に申告してください。']);
        }

        $visit->update([
            'after_status' => $data['after_status'],
            'after_status_note' => $data['after_status_note'] ?? null,
            // 指名キャストが未設定なら、申告した本人を紐づける（誰が行くかを明確化）
            'primary_cast_id' => $visit->primary_cast_id ?? $customer->cast_id,
        ]);

        return back()->with('status', 'アフター見込みを更新しました。');
    }
}
