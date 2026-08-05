<?php

namespace App\Http\Controllers\Cast;

use App\Http\Controllers\Controller;
use App\Models\CastGoal;
use App\Support\CurrentStore;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * キャスト本人による月次目標の設定・変更（いつでも変更可）。
 * 支援用の指標。人事・報酬評価には使わない。
 */
class GoalController extends Controller
{
    public function edit()
    {
        $castId = $this->requireCast();
        $goal = CastGoal::where('cast_id', $castId)->where('period', CastGoal::currentPeriod())->first();

        return view('cast.goal.edit', ['goal' => $goal, 'period' => CastGoal::currentPeriod()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $castId = $this->requireCast();

        $data = $request->validate([
            'target_amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        CastGoal::updateOrCreate(
            ['cast_id' => $castId, 'period' => CastGoal::currentPeriod()],
            ['store_id' => CurrentStore::id(), 'target_amount' => $data['target_amount'], 'note' => $data['note'] ?? null]
        );

        return redirect()->route('home')->with('status', '今月の目標を設定しました。');
    }

    private function requireCast(): int
    {
        $castId = CustomerAccess::currentCastId();
        abort_if($castId === null, 403);

        return $castId;
    }
}
