<?php

namespace App\Http\Controllers\Staff;

use App\Enums\VisitPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\CustomerSharedNote;
use App\Models\DailyHandover;
use App\Models\StaffProfile;
use App\Models\Visit;
use App\Models\VisitPlan;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 黒服・店長の通常業務ページ。営業中に一目で把握できることを最優先（§5-10）。
 * 「過去の顧客整理(顧客カード)」とは用途が違うため画面を分ける。
 */
class WorkController extends Controller
{
    /** 来店中一覧（黒服の通常業務ページ／店長も閲覧） */
    public function index(Request $request)
    {
        $storeId = CurrentStore::id();
        $today = now()->toDateString();

        // 席単位を主軸に並べる（席番号→到着順）。営業中は「どの席で何が起きているか」が先。
        $visits = Visit::with(['customer.keptBottles', 'customer.alerts' => fn ($q) => $q->active(), 'primaryCast'])
            ->present()
            ->orderByRaw('seat is null, seat asc')
            ->orderBy('arrived_at')
            ->get();

        // 各来店中客について、担当キャストの「大元の共有情報」と「今日の申し送り」を付与
        $cards = $visits->map(function (Visit $v) use ($today) {
            $sharedNotes = collect();
            $rel = null;
            if ($v->primary_cast_id) {
                $rel = CastCustomerRelationship::where('customer_id', $v->customer_id)
                    ->where('cast_id', $v->primary_cast_id)->first();
            }
            $rel = $rel ?? CastCustomerRelationship::where('customer_id', $v->customer_id)->first();
            if ($rel) {
                $sharedNotes = $rel->sharedNotes()->latest()->limit(5)->get();
            }

            $handovers = DailyHandover::where('customer_id', $v->customer_id)
                ->whereDate('for_date', $today)->latest()->get();

            $pastVisits = Visit::where('customer_id', $v->customer_id)
                ->where('id', '!=', $v->id)->where('status', 'left')->count();
            $lastVisit = Visit::where('customer_id', $v->customer_id)
                ->where('id', '!=', $v->id)->where('status', 'left')
                ->orderByDesc('arrived_at')->value('arrived_at');

            return [
                'visit' => $v,
                'name' => $rel?->customer_name ?? ('顧客#' . $v->customer_id),
                'relId' => $rel?->id,
                'sharedNotes' => $sharedNotes,
                'handovers' => $handovers,
                'pastVisits' => $pastVisits,
                'lastVisit' => $lastVisit,
            ];
        });

        $todayPlanCount = VisitPlan::whereDate('planned_date', $today)
            ->whereIn('status', [VisitPlanStatus::Pending->value, VisitPlanStatus::Confirmed->value])
            ->count();

        return view('staff.work.index', [
            'cards' => $cards,
            'todayPlanCount' => $todayPlanCount,
            'big' => $request->boolean('big'),
            'seats' => \App\Models\Seat::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name'),
        ]);
    }

    /** 今日の来店予定 */
    public function plans()
    {
        $today = now()->toDateString();
        $plans = VisitPlan::with(['customer.keptBottles', 'cast'])
            ->whereDate('planned_date', $today)
            ->where('status', '!=', VisitPlanStatus::Cancelled->value)
            ->orderByRaw('planned_time is null, planned_time asc')
            ->get();

        return view('staff.work.plans', compact('plans'));
    }

    /** 顧客検索（名前・LINE名・旧LINE名・ボトル名・指名キャスト） */
    public function search(Request $request)
    {
        $user = Auth::user();
        $q = trim((string) $request->input('q', ''));
        $results = collect();
        // 黒服は個人情報保護のため、検索範囲を「本日来店・来店予定・担当キャスト関連」に限定。
        // 全顧客検索は店長・オーナーのみ。
        $limited = $user->isStaff();

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query = CastCustomerRelationship::with(['customer.keptBottles', 'cast'])
                ->where(function ($sub) use ($like) {
                    $sub->where('customer_name', 'like', $like)
                        ->orWhere('line_display_name', 'like', $like)
                        ->orWhereHas('cast', fn ($c) => $c->where('display_name', 'like', $like))
                        ->orWhereHas('customer', function ($c) use ($like) {
                            $c->where('kana', 'like', $like)
                                ->orWhereHas('aliases', fn ($a) => $a->where('value', 'like', $like))
                                ->orWhereHas('bottles', fn ($b) => $b->where('name', 'like', $like));
                        });
                });

            if ($limited) {
                $query->whereIn('customer_id', $this->staffScopedCustomerIds($user));
            }

            $results = $query->limit(50)->get();
        }

        return view('staff.work.search', compact('q', 'results', 'limited'));
    }

    /** 黒服が閲覧してよい顧客ID（本日来店・来店予定・担当キャストの顧客）。 */
    private function staffScopedCustomerIds($user): \Illuminate\Support\Collection
    {
        $today = now()->toDateString();

        $todayVisits = Visit::where(fn ($q) => $q->whereDate('arrived_at', $today)->orWhere('status', 'present'))->pluck('customer_id');
        $upcoming = VisitPlan::whereDate('planned_date', '>=', $today)->where('status', '!=', 'cancelled')->pluck('customer_id');

        $staff = StaffProfile::where('user_id', $user->id)->first();
        $assignedCustomers = collect();
        if ($staff) {
            $castIds = $staff->assignedCasts()->pluck('casts.id');
            $assignedCustomers = CastCustomerRelationship::whereIn('cast_id', $castIds)->pluck('customer_id');
        }

        return $todayVisits->merge($upcoming)->merge($assignedCustomers)->unique()->values();
    }

    /** 担当キャスト一覧（黒服＝自分の担当／店長＝全キャスト） */
    public function casts()
    {
        $user = Auth::user();
        $storeId = CurrentStore::id();

        if ($user->isStaff()) {
            $staff = StaffProfile::where('user_id', $user->id)->first();
            $casts = $staff ? $staff->assignedCasts()->get() : collect();
        } else {
            $casts = \App\Models\Cast::where('status', 'active')->orderBy('display_name')->get();
        }

        // 各キャストの当月ざっくり集計（§5-10-D）＋目標進捗（応援のため）
        $monthStart = now()->startOfMonth();
        $metrics = app(\App\Services\MetricsService::class);
        $rows = $casts->map(function ($cast) use ($monthStart, $metrics) {
            return [
                'cast' => $cast,
                'customers' => CastCustomerRelationship::where('cast_id', $cast->id)->count(),
                'visits' => Visit::where('primary_cast_id', $cast->id)->where('arrived_at', '>=', $monthStart)->count(),
                'honshimei' => Visit::where('primary_cast_id', $cast->id)->where('arrived_at', '>=', $monthStart)->where('is_honshimei', true)->count(),
                'openShared' => CustomerSharedNote::where('cast_id', $cast->id)->where('created_at', '>=', $monthStart)->count(),
                'progress' => $metrics->castProgress($cast->id),
            ];
        });

        return view('staff.work.casts', compact('rows'));
    }

    /** アフター見守り（誰が・どの店へ・何時から・帰宅連絡）を黒服が管理する。 */
    public function after(Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date', now()->toDateString());
        $isToday = $date === now()->toDateString();

        // 見守り対象のキャスト（黒服＝担当のみ／店長・オーナー＝全キャスト）
        if ($user->isStaff()) {
            $staff = StaffProfile::where('user_id', $user->id)->first();
            $casts = $staff ? $staff->assignedCasts()->get() : collect();
        } else {
            $casts = \App\Models\Cast::where('status', 'active')->orderBy('display_name')->get();
        }
        $castIds = $casts->pluck('id');

        // まだ帰宅連絡がないアフター（＝見守り継続中）は日付に関わらず常に上へ
        $watching = \App\Models\AfterLog::with(['cast', 'customer'])
            ->out()->whereIn('cast_id', $castIds)
            ->orderBy('departed_at')->get();

        // 指定日のアフター記録（帰宅済みも含む・確認用）
        $logs = \App\Models\AfterLog::with(['cast', 'customer'])
            ->whereIn('cast_id', $castIds)
            ->whereDate('departed_at', $date)
            ->orderByDesc('departed_at')->get();

        return view('staff.work.after', compact('casts', 'watching', 'logs', 'date', 'isToday'));
    }
}
