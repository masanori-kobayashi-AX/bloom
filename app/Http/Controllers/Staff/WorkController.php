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
    public function index()
    {
        $storeId = CurrentStore::id();
        $today = now()->toDateString();

        $visits = Visit::with(['customer.keptBottles', 'customer.alerts' => fn ($q) => $q->where('resolved', false), 'primaryCast'])
            ->present()
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
        $q = trim((string) $request->input('q', ''));
        $results = collect();

        if ($q !== '') {
            $like = '%' . $q . '%';
            $results = CastCustomerRelationship::with(['customer.keptBottles', 'cast'])
                ->where(function ($sub) use ($like) {
                    $sub->where('customer_name', 'like', $like)
                        ->orWhere('line_display_name', 'like', $like)
                        ->orWhereHas('cast', fn ($c) => $c->where('display_name', 'like', $like))
                        ->orWhereHas('customer', function ($c) use ($like) {
                            $c->where('kana', 'like', $like)
                                ->orWhereHas('aliases', fn ($a) => $a->where('value', 'like', $like))
                                ->orWhereHas('bottles', fn ($b) => $b->where('name', 'like', $like));
                        });
                })
                ->limit(50)->get();
        }

        return view('staff.work.search', compact('q', 'results'));
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

        // 各キャストの当月ざっくり集計
        $monthStart = now()->startOfMonth();
        $rows = $casts->map(function ($cast) use ($monthStart) {
            return [
                'cast' => $cast,
                'customers' => CastCustomerRelationship::where('cast_id', $cast->id)->count(),
                'openShared' => CustomerSharedNote::where('cast_id', $cast->id)->where('created_at', '>=', $monthStart)->count(),
            ];
        });

        return view('staff.work.casts', compact('rows'));
    }

    /** アフター管理（誰がどの客とアフターに行くか／行ったか）を店側が把握する。 */
    public function after(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $visits = Visit::with(['customer', 'primaryCast'])
            ->whereNotNull('after_status')
            ->whereDate('arrived_at', $date)
            ->orderByDesc('arrived_at')
            ->get();

        // 直近7日で「行けそう/確定」の履歴も表示
        $recent = Visit::with(['customer', 'primaryCast'])
            ->whereIn('after_status', ['likely', 'going'])
            ->whereDate('arrived_at', '>=', now()->subDays(7)->toDateString())
            ->whereDate('arrived_at', '<', $date)
            ->orderByDesc('arrived_at')
            ->limit(50)->get();

        return view('staff.work.after', compact('visits', 'recent', 'date'));
    }
}
