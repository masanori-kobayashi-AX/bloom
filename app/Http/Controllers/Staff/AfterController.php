<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AfterLog;
use App\Models\Cast;
use App\Models\StaffProfile;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * アフターの見守り記録。黒服がキャストの安全（帰宅連絡まで）を管理する。
 */
class AfterController extends Controller
{
    /** アフター開始を記録（どのキャストが・どの店へ・何時から）。 */
    public function store(Request $request): RedirectResponse
    {
        $castIds = $this->manageableCastIds();

        $data = $request->validate([
            'cast_id' => ['required', Rule::in($castIds->all())],
            'destination' => ['nullable', 'string', 'max:100'],
            'companion' => ['nullable', 'string', 'max:100'],
            'departed_at' => ['nullable', 'date'],
            'expected_home_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        AfterLog::create([
            'store_id' => CurrentStore::id(),
            'cast_id' => $data['cast_id'],
            'destination' => $data['destination'] ?? null,
            'companion' => $data['companion'] ?? null,
            'departed_at' => ! empty($data['departed_at']) ? Carbon::parse($data['departed_at']) : now(),
            'expected_home_at' => ! empty($data['expected_home_at']) ? Carbon::parse($data['expected_home_at']) : null,
            'note' => $data['note'] ?? null,
            'status' => 'out',
        ]);

        return back()->with('status', 'アフターを記録しました。帰宅連絡を待ちます。');
    }

    /** 帰宅連絡が来たら押す。見守り終了。 */
    public function home(AfterLog $afterLog): RedirectResponse
    {
        $this->authorizeLog($afterLog);
        $afterLog->update(['status' => 'home', 'home_reported_at' => now()]);

        return back()->with('status', '帰宅連絡を記録しました。おつかれさまでした。');
    }

    /** 帰宅を取り消して見守りに戻す（誤操作対応）。 */
    public function reopen(AfterLog $afterLog): RedirectResponse
    {
        $this->authorizeLog($afterLog);
        $afterLog->update(['status' => 'out', 'home_reported_at' => null]);

        return back()->with('status', '見守り中に戻しました。');
    }

    /** 記録を取り消す（誤登録）。 */
    public function destroy(AfterLog $afterLog): RedirectResponse
    {
        $this->authorizeLog($afterLog);
        $afterLog->delete();

        return back()->with('status', 'アフター記録を取り消しました。');
    }

    /** 黒服＝自分の担当キャストのみ／店長・オーナー＝全キャスト。 */
    private function manageableCastIds(): \Illuminate\Support\Collection
    {
        $user = Auth::user();
        if ($user->isStaff()) {
            $staff = StaffProfile::where('user_id', $user->id)->first();

            return $staff ? $staff->assignedCasts()->pluck('casts.id') : collect();
        }

        return Cast::where('status', 'active')->pluck('id');
    }

    private function authorizeLog(AfterLog $log): void
    {
        abort_unless($this->manageableCastIds()->contains($log->cast_id), 403);
    }
}
