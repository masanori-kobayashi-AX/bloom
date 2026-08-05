<?php

namespace App\Http\Controllers\Cast;

use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 今日の申し送り（キャスト→黒服「今日伝えておきたい情報」）。
 * 大元の共有情報(店舗共有事項)＝持続的 とは別チャンネルの当日情報。
 */
class DailyHandoverController extends Controller
{
    public function store(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'for_date' => ['nullable', 'date'],
        ]);

        $customer->customer->handovers()->create([
            'store_id' => $customer->store_id,
            'cast_id' => $customer->cast_id,
            'relationship_id' => $customer->id,
            'for_date' => $data['for_date'] ?? now()->toDateString(),
            'body' => $data['body'],
        ]);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '今日の申し送りを登録しました。黒服が本日確認できます。')
            ->withFragment('handovers');
    }
}
