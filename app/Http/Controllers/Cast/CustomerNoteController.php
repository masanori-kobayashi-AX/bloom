<?php

namespace App\Http\Controllers\Cast;

use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 顧客メモの追加。自分用（本人＋オーナーのみ）と 黒服共有 を明確に分離する。
 */
class CustomerNoteController extends Controller
{
    public function storePrivate(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $customer->notes()->create([
            'store_id' => $customer->store_id,
            'cast_id' => $customer->cast_id,
            'body' => $data['body'],
        ]);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '自分用メモを追加しました。')
            ->withFragment('private-notes');
    }

    public function storeShared(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:50'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $customer->sharedNotes()->create([
            'store_id' => $customer->store_id,
            'customer_id' => $customer->customer_id,
            'cast_id' => $customer->cast_id,
            'category' => $data['category'] ?? null,
            'body' => $data['body'],
        ]);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '店舗共有事項を追加しました。担当黒服・店長が確認できます。')
            ->withFragment('shared-notes');
    }
}
