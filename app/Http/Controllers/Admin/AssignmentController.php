<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cast;
use App\Models\CastStaffAssignment;
use App\Models\StaffProfile;
use App\Services\AuditLogger;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * キャストと担当黒服の紐付け（責任者・管理者）。現在店舗内で完結。
 */
class AssignmentController extends Controller
{
    public function index()
    {
        $casts = Cast::with(['activeAssignments.staff'])->where('status', 'active')->orderBy('display_name')->get();
        // 黒服ごとの担当キャストも取得（誰が何人担当しているか把握）
        $staff = StaffProfile::with(['assignedCasts' => fn ($q) => $q->orderBy('display_name')])
            ->where('status', 'active')->orderBy('display_name')->get();

        return view('admin.assignments.index', compact('casts', 'staff'));
    }

    public function store(Request $request): RedirectResponse
    {
        $storeId = CurrentStore::id();

        $data = $request->validate([
            'cast_id' => ['required', Rule::exists('casts', 'id')->where('store_id', $storeId)],
            'staff_id' => ['required', Rule::exists('staff_profiles', 'id')->where('store_id', $storeId)],
        ]);

        // 既存の有効な担当があれば入れ替え（1キャスト=1担当を基本とする）
        DB::transaction(function () use ($data, $storeId) {
            CastStaffAssignment::where('store_id', $storeId)
                ->where('cast_id', $data['cast_id'])
                ->whereNull('released_at')
                ->update(['released_at' => now()]);

            $assignment = CastStaffAssignment::create([
                'store_id' => $storeId,
                'cast_id' => $data['cast_id'],
                'staff_id' => $data['staff_id'],
                'assigned_at' => now(),
            ]);

            AuditLogger::record('assignment.create', $assignment, 'キャストと担当黒服を紐付け', after: [
                'cast_id' => $data['cast_id'], 'staff_id' => $data['staff_id'],
            ], storeId: $storeId);
        });

        return redirect()->route('admin.assignments.index')->with('status', '担当を設定しました。');
    }

    public function release(CastStaffAssignment $assignment): RedirectResponse
    {
        $storeId = CurrentStore::id();
        abort_unless($assignment->store_id === $storeId, 403);

        $assignment->update(['released_at' => now()]);
        AuditLogger::record('assignment.release', $assignment, '担当を解除', storeId: $storeId);

        return redirect()->route('admin.assignments.index')->with('status', '担当を解除しました。');
    }
}
