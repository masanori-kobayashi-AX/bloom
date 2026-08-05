<?php

namespace App\Http\Controllers\Cast;

use App\Enums\AlertCategory;
use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\CustomerAlert;
use App\Services\AuditLogger;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 重大注意情報（§5-5-D）。事実と主観を分け、情報源・発生日・対応方針・失効日で構造化。
 * 性格評価・容姿・病名推測・国籍・思想等の記載は禁止（画面で注意喚起）。
 */
class CustomerAlertController extends Controller
{
    public function store(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(AlertCategory::options()))],
            'fact' => ['required', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:100'],
            'occurred_on' => ['nullable', 'date'],
            'subjective' => ['nullable', 'string', 'max:1000'],
            'action_plan' => ['nullable', 'string', 'max:500'],
            'expires_on' => ['nullable', 'date', 'after:today'],
            'severity' => ['required', Rule::in(['low', 'mid', 'high'])],
        ]);

        $alert = $customer->customer->alerts()->create([
            'store_id' => $customer->store_id,
            'cast_id' => $customer->cast_id,
            'category' => $data['category'],
            'fact' => $data['fact'],
            'source' => $data['source'] ?? null,
            'occurred_on' => $data['occurred_on'] ?? null,
            'subjective' => $data['subjective'] ?? null,
            'action_plan' => $data['action_plan'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
            'severity' => $data['severity'],
        ]);

        AuditLogger::record('alert.create', $alert, '重大注意を記録', storeId: $customer->store_id);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '重大注意情報を記録しました。')
            ->withFragment('alerts');
    }

    /** 取り下げ（解決）。データは残す（論理）。破壊操作のため確認＋監査。 */
    public function resolve(Request $request, CustomerAlert $alert): RedirectResponse
    {
        $castId = CustomerAccess::currentCastId();
        // 本人（記録者キャスト）またはオーナーのみ取り下げ可
        abort_unless(($castId !== null && $alert->cast_id === $castId) || $request->user()->isAdmin(), 403);

        $alert->update(['resolved' => true]);
        AuditLogger::record('alert.resolve', $alert, '重大注意を取り下げ', storeId: $alert->store_id);

        return back()->with('status', '重大注意を取り下げました（記録は保持されます）。');
    }
}
