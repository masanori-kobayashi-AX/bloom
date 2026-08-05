<?php

namespace App\Services;

use App\Models\Cast;
use App\Models\CastCustomerRelationship;
use App\Models\CastGoal;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 集計ロジックの単一窓口（Phase 4）。全クエリは store_id グローバルスコープ下で動く前提。
 * 数値は「評価」でなく「支援」のための材料。単純比較で断定しない（§6-2）。
 */
class MetricsService
{
    private Carbon $monthStart;
    private Carbon $monthEnd;

    public function __construct()
    {
        $this->monthStart = now()->startOfMonth();
        $this->monthEnd = now()->endOfMonth();
    }

    /** 店舗全体の今月サマリ（§6-1）。 */
    public function storeSummary(): array
    {
        $visitsThisMonth = Visit::whereBetween('arrived_at', [$this->monthStart, $this->monthEnd]);

        return [
            'activeCasts' => Cast::where('status', 'active')->count(),
            'workingCasts' => (clone $visitsThisMonth)->whereNotNull('primary_cast_id')->distinct()->count('primary_cast_id'),
            'newCustomers' => CastCustomerRelationship::whereBetween('created_at', [$this->monthStart, $this->monthEnd])->count(),
            'lineExchanges' => CastCustomerRelationship::whereBetween('line_exchanged_on', [$this->monthStart->toDateString(), $this->monthEnd->toDateString()])->count(),
            'visitedCustomers' => (clone $visitsThisMonth)->distinct()->count('customer_id'),
            'visitCount' => (clone $visitsThisMonth)->count(),
            'zainaiVisits' => (clone $visitsThisMonth)->where('is_zainai', true)->count(),
            'honshimeiVisits' => (clone $visitsThisMonth)->where('is_honshimei', true)->count(),
            'nominatedCustomers' => CastCustomerRelationship::whereIn('status', ['zainai', 'honshimei', 'continuing', 'important'])->distinct()->count('customer_id'),
            'dormantCustomers' => CastCustomerRelationship::where('status', 'dormant')->count(),
            // 売上は来店(visits)の金額を正とする（退店処理で必ず入る単一の源）
            'totalSales' => (int) (clone $visitsThisMonth)->sum('amount'),
        ];
    }

    /** キャスト別の今月指標（§6-2）。 */
    public function castBreakdown(): Collection
    {
        $casts = Cast::where('status', 'active')->with('user')->orderBy('display_name')->get();

        return $casts->map(function (Cast $cast) {
            $rels = CastCustomerRelationship::where('cast_id', $cast->id)->get();
            $total = $rels->count();
            $honshimei = $rels->where('status', 'honshimei')->count();
            $zainai = $rels->where('status', 'zainai')->count();

            $visitsQ = Visit::where('primary_cast_id', $cast->id)
                ->whereBetween('arrived_at', [$this->monthStart, $this->monthEnd]);
            $sales = (int) (clone $visitsQ)->sum('amount');
            $visits = (clone $visitsQ)->count();

            return [
                'cast' => $cast,
                'customers' => $total,
                'newLine' => $rels->filter(fn ($r) => $r->line_exchanged_on
                    && $r->line_exchanged_on->betweenIncluded($this->monthStart, $this->monthEnd))->count(),
                'zainai' => $zainai,
                'honshimei' => $honshimei,
                'conversionRate' => $total > 0 ? round($honshimei / $total * 100) : 0,
                'core' => $rels->where('importance', 'core')->count(),
                'continuing' => $rels->where('importance', 'continuing')->count(),
                'nurturing' => $rels->where('importance', 'nurturing')->count(),
                'dormant' => $rels->where('status', 'dormant')->count(),
                'visits' => $visits,
                'sales' => $sales,
                'lastRegisteredAt' => $rels->max('created_at'),
                'lastLoginAt' => $cast->user?->last_login_at,
            ];
        });
    }

    /**
     * 店舗の顧客資産（§6-3）。※顧客を機械的に「店舗所有」に切り替える運用はしない。
     */
    public function customerAssets(): array
    {
        // 一人の顧客が複数キャストを指名している（横断利用）
        $multiCast = CastCustomerRelationship::query()
            ->selectRaw('customer_id, count(*) as cast_count')
            ->groupBy('customer_id')
            ->havingRaw('count(*) > 1')
            ->get();

        // 長期未来店（前回来店から60日以上、または来店なし）
        $sixtyDaysAgo = now()->subDays(60);
        $lastVisits = Visit::query()
            ->selectRaw('customer_id, max(arrived_at) as last_at')
            ->groupBy('customer_id')
            ->pluck('last_at', 'customer_id');

        $dormant = CastCustomerRelationship::query()
            ->with('cast')
            ->whereIn('status', ['continuing', 'important', 'honshimei'])
            ->get()
            ->filter(function ($rel) use ($lastVisits, $sixtyDaysAgo) {
                $last = $lastVisits[$rel->customer_id] ?? null;

                return $last === null || Carbon::parse($last)->lt($sixtyDaysAgo);
            })
            ->take(20);

        // 高売上顧客（累計・来店金額ベース）
        $topCustomers = Visit::query()
            ->whereNotNull('amount')
            ->selectRaw('customer_id, sum(amount) as total')
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'multiCastCount' => $multiCast->count(),
            'dormant' => $dormant,
            'topCustomers' => $topCustomers,
        ];
    }

    /**
     * キャストの当月目標と進捗（支援用・順位付けしない）。
     * @return array{target:int,actual:int,rate:?int,hasGoal:bool}
     */
    public function castProgress(int $castId): array
    {
        $goal = CastGoal::where('cast_id', $castId)->where('period', CastGoal::currentPeriod())->first();
        $target = (int) ($goal?->target_amount ?? 0);
        $actual = (int) Visit::where('primary_cast_id', $castId)
            ->whereBetween('arrived_at', [$this->monthStart, $this->monthEnd])->sum('amount');

        return [
            'target' => $target,
            'actual' => $actual,
            'rate' => $target > 0 ? min(100, (int) round($actual / $target * 100)) : null,
            'hasGoal' => $goal !== null && $target > 0,
        ];
    }

    /** キャスト用ホームの今月メトリクス（§5-2）。責める表現・順位付けはしない。 */
    public function castHome(int $castId): array
    {
        $rels = CastCustomerRelationship::where('cast_id', $castId)->get();
        $customerIds = $rels->pluck('customer_id');

        $visitedThisMonth = Visit::whereIn('customer_id', $customerIds)
            ->whereBetween('arrived_at', [$this->monthStart, $this->monthEnd])
            ->distinct()->pluck('customer_id');

        $honshimeiThisMonth = Visit::whereIn('customer_id', $customerIds)
            ->whereBetween('arrived_at', [$this->monthStart, $this->monthEnd])
            ->where('is_honshimei', true)->distinct()->count('customer_id');

        // 今月まだ来店していない重要顧客（core / status=important）
        $importantRels = $rels->filter(fn ($r) => $r->importance?->value === 'core' || $r->status?->value === 'important');
        $importantNotVisited = $importantRels->reject(fn ($r) => $visitedThisMonth->contains($r->customer_id));

        // しばらく来店していない（前回来店から30日以上、休眠/対応終了は除く）
        $thirtyAgo = now()->subDays(30);
        $lastVisits = Visit::whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, max(arrived_at) as last_at')->groupBy('customer_id')->pluck('last_at', 'customer_id');
        $longAbsent = $rels->reject(fn ($r) => in_array($r->status?->value, ['dormant', 'closed'], true))
            ->filter(function ($r) use ($lastVisits, $thirtyAgo) {
                $last = $lastVisits[$r->customer_id] ?? null;

                return $last !== null && Carbon::parse($last)->lt($thirtyAgo);
            });

        return [
            'newLineThisMonth' => $rels->filter(fn ($r) => $r->line_exchanged_on
                && $r->line_exchanged_on->betweenIncluded($this->monthStart, $this->monthEnd))->count(),
            'visitedThisMonth' => $visitedThisMonth->count(),
            'honshimeiThisMonth' => $honshimeiThisMonth,
            'importantNotVisited' => $importantNotVisited->values(),
            'longAbsent' => $longAbsent->values(),
        ];
    }
}
