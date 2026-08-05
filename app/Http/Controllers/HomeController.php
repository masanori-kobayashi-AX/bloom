<?php

namespace App\Http\Controllers;

use App\Models\CastCustomerRelationship;
use App\Models\NextAction;
use App\Models\Visit;
use App\Services\MetricsService;
use App\Support\CustomerAccess;
use Illuminate\Support\Facades\Auth;

/**
 * ロールに応じたトップ画面。キャストは「次に何をすべきか」が分かる表示にする（§5-2）。
 */
class HomeController extends Controller
{
    public function index(MetricsService $metrics)
    {
        $user = Auth::user();
        $role = $user->role();

        // 黒服の通常業務は来店中ページ。ホームは来店中一覧へ。
        if ($user->isStaff()) {
            return redirect()->route('staff.work.index');
        }

        $data = ['user' => $user, 'role' => $role];

        // 店長・オーナーのホームにも来店中一覧を出す（§来店時共有）
        if ($user->isManager() || $user->isAdmin()) {
            $data['presentVisits'] = Visit::with(['customer', 'primaryCast'])
                ->present()->orderBy('arrived_at')->get();
        }

        if ($user->isCast()) {
            $castId = CustomerAccess::currentCastId();
            $today = now()->toDateString();

            $data['cast'] = [
                'customerCount' => CastCustomerRelationship::where('cast_id', $castId)->count(),
                'openActions' => NextAction::where('cast_id', $castId)->where('completed', false)->count(),
                'overdueActions' => NextAction::where('cast_id', $castId)->where('completed', false)
                    ->whereNotNull('due_on')->whereDate('due_on', '<', $today)->count(),
                'dueTodayActions' => NextAction::with('relationship')
                    ->where('cast_id', $castId)->where('completed', false)
                    ->whereDate('due_on', '<=', $today)
                    ->orderByRaw('due_on is null, due_on asc')->limit(5)->get(),
                'recent' => CastCustomerRelationship::where('cast_id', $castId)
                    ->latest()->limit(5)->get(),
            ];
            // §5-2 今月メトリクス（新規LINE・来店・本指名・未来店の重要顧客・長期未来店）
            $data['cast'] = array_merge($data['cast'], $metrics->castHome($castId));
        }

        return view('home', $data);
    }
}
