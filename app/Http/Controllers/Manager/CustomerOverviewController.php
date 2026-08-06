<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Services\AuditLogger;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 全キャストの顧客状況を横スクロールで一覧（エクセル的）。
 * ・オーナー(admin)：「私だけのメモ（本人コメント）」列を含む全項目
 * ・店長(manager)：本人コメントを外した同じ表
 */
class CustomerOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $showPrivate = $user->isAdmin(); // 本人コメント（私だけのメモ）はオーナーのみ
        $storeId = CurrentStore::id();

        $castId = $request->input('cast_id'); // キャストで絞り込み（任意）

        // 各顧客の来店集計をサブクエリで付与
        $base = 'from visits where visits.customer_id = cast_customer_relationships.customer_id and visits.store_id = cast_customer_relationships.store_id and visits.deleted_at is null';

        $eager = ['cast', 'customer.keptBottles', 'customer.alerts' => fn ($q) => $q->active(), 'sharedNotes', 'nextActions'];
        if ($showPrivate) {
            $eager[] = 'notes';
        }

        $rows = CastCustomerRelationship::query()
            ->with($eager)
            ->select('cast_customer_relationships.*')
            ->selectRaw("(select max(arrived_at) $base) as last_visit_at")
            ->selectRaw("(select count(*) $base and visits.status='left') as visit_count")
            ->selectRaw("(select coalesce(sum(amount),0) $base) as total_sales")
            ->when($castId, fn ($q) => $q->where('cast_id', $castId))
            // 未選択（全体）では在籍キャストのみ。退店者の顧客は「退店者」で指名したときだけ表示。
            ->when(! $castId, fn ($q) => $q->whereHas('cast', fn ($c) => $c->where('status', 'active')))
            ->orderBy('cast_id')
            ->orderByRaw('updated_at desc')
            ->get();

        // オーナーが本人コメント込みで一覧閲覧したことを監査記録（§3-2）
        if ($showPrivate) {
            AuditLogger::record('view.private_overview', null, 'オーナーが全キャストの顧客一覧（本人コメント含む）を閲覧', storeId: $storeId);
        }

        $casts = \App\Models\Cast::where('status', 'active')->orderBy('display_name')->get();
        // 退店者（クローズ済み）＝店長・管理者だけが、退店者名から顧客情報をたどれる
        $formerCasts = \App\Models\Cast::where('status', 'left')->orderBy('display_name')->get();
        $selectedCast = $castId ? \App\Models\Cast::find($castId) : null;

        return view('manager.customers.index', [
            'rows' => $rows,
            'showPrivate' => $showPrivate,
            'canEdit' => $user->isAdmin() && ! ($selectedCast && $selectedCast->status === 'left'), // 退店者の顧客は閲覧のみ
            'casts' => $casts,
            'formerCasts' => $formerCasts,
            'selectedCast' => $selectedCast,
            'castId' => $castId,
            'statuses' => \App\Enums\CustomerStatus::options(),
            'importances' => \App\Enums\CustomerImportance::options(),
        ]);
    }

    /**
     * セル編集（オーナーのみ）。状況・区分・次に話したいこと等をその場で更新する。
     */
    public function updateCell(Request $request, CastCustomerRelationship $relationship)
    {
        abort_unless($request->user()->isAdmin(), 403); // 編集はオーナーのみ

        $data = $request->validate([
            'field' => ['required', 'in:status,importance,next_talk,visit_expectation'],
            'value' => ['nullable', 'string', 'max:1000'],
        ]);

        // 選択式フィールドは許可値のみ
        if ($data['field'] === 'status' && ! array_key_exists($data['value'], \App\Enums\CustomerStatus::options())) {
            abort(422);
        }
        if ($data['field'] === 'importance' && $data['value'] !== '' && ! array_key_exists($data['value'], \App\Enums\CustomerImportance::options())) {
            abort(422);
        }

        $before = $relationship->{$data['field']};
        $beforeVal = $before instanceof \BackedEnum ? $before->value : $before;
        $relationship->update([$data['field'] => $data['value'] ?: null]);

        AuditLogger::record('overview.edit', $relationship, "オーナーが{$data['field']}を編集", before: [$data['field'] => $beforeVal], after: [$data['field'] => $data['value']], storeId: $relationship->store_id);

        return back()->with('status', '更新しました。');
    }
}
