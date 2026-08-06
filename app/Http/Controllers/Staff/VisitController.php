<?php

namespace App\Http\Controllers\Staff;

use App\Enums\NominationType;
use App\Enums\VisitPlanStatus;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\Customer;
use App\Models\DailyHandover;
use App\Models\SalesRecord;
use App\Models\Visit;
use App\Models\VisitCast;
use App\Models\VisitPlan;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * 黒服による来店運用（来店開始→来店中→退店処理）。§9-3・§9-4。
 */
class VisitController extends Controller
{
    /** 来店開始（検索からの直接開始 or 予定からの来店）。二重作成を防ぐ。 */
    public function start(Request $request): RedirectResponse
    {
        $storeId = CurrentStore::id();
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'visit_plan_id' => ['nullable', 'integer'],
            'cast_id' => ['nullable', 'integer'],
            'seat' => ['nullable', 'string', 'max:50'],
        ]);

        $plan = null;
        $customerId = $data['customer_id'] ?? null;
        $primaryCastId = null;

        if (! empty($data['visit_plan_id'])) {
            $plan = VisitPlan::findOrFail($data['visit_plan_id']);
            $customerId = $plan->customer_id;
            $primaryCastId = $plan->cast_id;
        } elseif (! empty($data['cast_id'])) {
            // 検索からの来店開始：指名キャストを引き継ぐ（在籍確認）
            $cast = \App\Models\Cast::where('id', $data['cast_id'])->where('store_id', $storeId)->first();
            $primaryCastId = $cast?->id;
        }

        abort_if(! $customerId, 422, '顧客が指定されていません。');
        $customer = Customer::findOrFail($customerId);

        // 既に来店中なら二重作成しない（冪等）
        $existing = Visit::present()->where('customer_id', $customer->id)->first();
        if ($existing) {
            return redirect()->route('staff.visits.show', $existing)
                ->with('status', 'この顧客はすでに来店中です。');
        }

        $visit = DB::transaction(function () use ($storeId, $customer, $plan, $primaryCastId, $data) {
            $visit = Visit::create([
                'store_id' => $storeId,
                'customer_id' => $customer->id,
                'visit_plan_id' => $plan?->id,
                'primary_cast_id' => $primaryCastId,
                'arrived_at' => now(),
                'status' => VisitStatus::Present->value,
                'seat' => $data['seat'] ?? null,
                'dohan' => $plan?->dohan ?? false,
            ]);

            if ($primaryCastId) {
                VisitCast::create([
                    'store_id' => $storeId, 'visit_id' => $visit->id,
                    'cast_id' => $primaryCastId, 'role' => 'nominated',
                ]);
            }

            if ($plan) {
                $plan->update(['status' => VisitPlanStatus::Arrived->value]);
            }

            return $visit;
        });

        return redirect()->route('staff.visits.show', $visit)->with('status', '来店を開始しました。');
    }

    /** 来店中の詳細（黒服が対応中に見る1画面） */
    public function show(Visit $visit)
    {
        $today = now()->toDateString();
        $visit->load(['customer.keptBottles', 'customer.alerts' => fn ($q) => $q->active(), 'primaryCast', 'bottles', 'visitCasts.cast']);
        $activeCasts = \App\Models\Cast::where('status', 'active')->orderBy('display_name')->get();

        $sharedNotes = collect();
        if ($visit->primary_cast_id) {
            $rel = CastCustomerRelationship::where('customer_id', $visit->customer_id)
                ->where('cast_id', $visit->primary_cast_id)->first();
            if ($rel) {
                $sharedNotes = $rel->sharedNotes()->latest()->get();
            }
        }
        $handovers = DailyHandover::where('customer_id', $visit->customer_id)
            ->whereDate('for_date', $today)->latest()->get();

        return view('staff.visits.show', [
            'visit' => $visit,
            'sharedNotes' => $sharedNotes,
            'handovers' => $handovers,
            'nominations' => NominationType::options(),
            'activeCasts' => $activeCasts,
        ]);
    }

    /** ヘルプ等で付いたキャストを追加（複数キャスト対応）。 */
    public function addCast(Request $request, Visit $visit): RedirectResponse
    {
        abort_unless($visit->store_id === CurrentStore::id(), 403);
        abort_unless($visit->status === VisitStatus::Present, 422, '来店中のみ追加できます。');

        $data = $request->validate([
            'cast_id' => ['required', 'integer'],
            'role' => ['nullable', 'string', 'max:20'],
        ]);
        $cast = \App\Models\Cast::where('id', $data['cast_id'])->where('store_id', $visit->store_id)->firstOrFail();

        VisitCast::firstOrCreate(
            ['visit_id' => $visit->id, 'cast_id' => $cast->id],
            ['store_id' => $visit->store_id, 'role' => $data['role'] ?? 'help']
        );

        return back()->with('status', "{$cast->display_name} をこの席に追加しました。");
    }

    /** 付いたキャストを外す（指名キャストは外さない）。 */
    public function removeCast(Visit $visit, VisitCast $visitCast): RedirectResponse
    {
        abort_unless($visit->store_id === CurrentStore::id() && $visitCast->visit_id === $visit->id, 403);
        abort_if($visit->primary_cast_id && $visitCast->cast_id === $visit->primary_cast_id, 422, '指名キャストは外せません。');

        $visitCast->delete();

        return back()->with('status', 'キャストを外しました。');
    }

    /** 席・注意・来店時メモ・ボトルの更新 */
    public function update(Request $request, Visit $visit): RedirectResponse
    {
        abort_unless($visit->status === VisitStatus::Present, 422, '来店中のみ編集できます。');

        $data = $request->validate([
            'seat' => ['nullable', 'string', 'max:50'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:50'],
            'caution' => ['nullable', 'string', 'max:255'],
            'arrival_note' => ['nullable', 'string', 'max:1000'],
            'bottle_name' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($visit, $data) {
            $visit->update([
                'seat' => $data['seat'] ?? $visit->seat,
                'party_size' => $data['party_size'] ?? $visit->party_size,
                'caution' => $data['caution'] ?? $visit->caution,
                'arrival_note' => $data['arrival_note'] ?? $visit->arrival_note,
            ]);

            if (! empty($data['bottle_name'])) {
                $visit->bottles()->create([
                    'store_id' => $visit->store_id,
                    'name' => $data['bottle_name'],
                    'action' => 'order',
                ]);
            }
        });

        return redirect()->route('staff.visits.show', $visit)->with('status', '更新しました。');
    }

    /** 退店処理（金額・指名区分・接客後メモ）。トランザクションで売上も記録。 */
    public function leave(Request $request, Visit $visit): RedirectResponse
    {
        abort_unless($visit->status === VisitStatus::Present, 422, 'すでに退店処理済みです。');

        $data = $request->validate([
            'amount' => ['nullable', 'integer', 'min:0'],
            'nomination_type' => ['nullable', Rule::in(array_keys(NominationType::options()))],
            'is_zainai' => ['nullable', 'boolean'],
            'is_honshimei' => ['nullable', 'boolean'],
            'after_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($visit, $data) {
            $visit->update([
                'left_at' => now(),
                'status' => VisitStatus::Left->value,
                'amount' => $data['amount'] ?? null,
                'nomination_type' => $data['nomination_type'] ?? null,
                'is_zainai' => (bool) ($data['is_zainai'] ?? false),
                'is_honshimei' => (bool) ($data['is_honshimei'] ?? false),
                'after_note' => $data['after_note'] ?? null,
            ]);

            if (! empty($data['amount'])) {
                SalesRecord::create([
                    'store_id' => $visit->store_id,
                    'visit_id' => $visit->id,
                    'customer_id' => $visit->customer_id,
                    'cast_id' => $visit->primary_cast_id,
                    'amount' => $data['amount'],
                    'recorded_at' => now(),
                ]);
            }
        });

        return redirect()->route('staff.work.index')->with('status', '退店処理を記録しました。');
    }

    /** 黒服が来店予定を確認済みにする */
    public function confirmPlan(VisitPlan $plan): RedirectResponse
    {
        $plan->update([
            'status' => VisitPlanStatus::Confirmed->value,
            'confirmed_by' => \Illuminate\Support\Facades\Auth::id(),
            'confirmed_at' => now(),
        ]);

        return back()->with('status', '来店予定を確認しました。');
    }

    /** 席だけを変更（来店中一覧からその場で）。他の項目は消さない。 */
    public function updateSeat(Request $request, Visit $visit): RedirectResponse
    {
        abort_unless($visit->store_id === CurrentStore::id(), 403);
        $data = $request->validate(['seat' => ['nullable', 'string', 'max:50']]);
        $visit->update(['seat' => $data['seat'] ?: null]);

        return back()->with('status', '席を変更しました。');
    }

    /** 来店取消（誤操作）。来店中のみ・論理削除で復元可能・監査記録。 */
    public function cancel(Visit $visit): RedirectResponse
    {
        abort_unless($visit->store_id === CurrentStore::id(), 403);
        abort_unless($visit->status === VisitStatus::Present, 403, '来店中のみ取り消せます。');

        DB::transaction(function () use ($visit) {
            if ($visit->visit_plan_id) {
                VisitPlan::where('id', $visit->visit_plan_id)->update(['status' => VisitPlanStatus::Confirmed->value]);
            }
            VisitCast::where('visit_id', $visit->id)->delete();
            \App\Services\AuditLogger::record('visit.cancel', $visit, '来店を取消（誤操作）', storeId: $visit->store_id);
            $visit->delete();
        });

        return redirect()->route('staff.work.index')->with('status', '来店を取り消しました（記録は保持されます）。');
    }
}
