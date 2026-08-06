<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\CastSupportRequest;
use App\Models\StaffProfile;
use App\Models\StaffRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * 黒服・責任者の受信箱。
 * ・業務連絡(staff_requests)：黒服は自分宛、店長/オーナーは全件（未対応把握）
 * ・相談(cast_support_requests)：公開先(audience)を厳密に尊重（黒服=staff宛のみ／責任者=manager宛のみ）
 */
class InboxController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 業務連絡
        $requestQuery = StaffRequest::with(['cast', 'customer'])->open()->latest();
        if ($user->isStaff()) {
            $staffId = StaffProfile::where('user_id', $user->id)->value('id');
            $requestQuery->where('staff_id', $staffId);
        }
        $requests = $requestQuery->get();

        // 相談（公開先で厳密に振り分け）
        $supports = collect();
        if ($user->isStaff()) {
            $staff = StaffProfile::where('user_id', $user->id)->first();
            $castIds = $staff ? $staff->assignedCasts()->pluck('casts.id') : collect();
            $supports = CastSupportRequest::with('cast')->open()
                ->where('audience', 'staff')->whereIn('cast_id', $castIds)->latest()->get();
        } elseif ($user->isManager() || $user->isAdmin()) {
            $supports = CastSupportRequest::with('cast')->open()
                ->where('audience', 'manager')->latest()->get();
        }

        // お知らせも同じ画面に集約（黒服の下部ナビを「お知らせ」1つにまとめるため）
        $readIds = \App\Models\AnnouncementRead::where('user_id', $user->id)->pluck('announcement_id')->all();
        $announcements = \App\Models\Announcement::active()
            ->orderByRaw("field(importance,'high','normal','low')")
            ->latest('published_at')->get();

        return view('inbox.index', [
            'requests' => $requests,
            'supports' => $supports,
            'statuses' => RequestStatus::options(),
            'announcements' => $announcements,
            'readIds' => $readIds,
        ]);
    }

    public function updateRequest(Request $request, StaffRequest $staffRequest): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isStaff()) {
            $staffId = StaffProfile::where('user_id', $user->id)->value('id');
            abort_unless($staffRequest->staff_id === $staffId, 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(RequestStatus::options()))],
        ]);

        $attrs = ['status' => $data['status']];
        if ($data['status'] !== 'pending' && $staffRequest->confirmed_at === null) {
            $attrs['confirmed_at'] = now();
            $attrs['confirmed_by'] = $user->id;
        }
        $staffRequest->update($attrs);

        return back()->with('status', '対応状況を更新しました。');
    }

    /** 相談への返信（キャストに表示される）。 */
    public function replySupport(Request $request, CastSupportRequest $support): RedirectResponse
    {
        $this->authorizeSupport($support);
        $data = $request->validate(['reply' => ['required', 'string', 'max:2000']]);
        $support->update([
            'reply' => $data['reply'],
            'replied_at' => now(),
            'replied_by' => Auth::id(),
            'status' => $support->status === 'open' ? 'acknowledged' : $support->status,
        ]);

        return back()->with('status', '返信しました。キャストに表示されます。');
    }

    /** 業務連絡への返信。 */
    public function replyRequest(Request $request, StaffRequest $staffRequest): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isStaff()) {
            $staffId = StaffProfile::where('user_id', $user->id)->value('id');
            abort_unless($staffRequest->staff_id === $staffId, 403);
        }
        $data = $request->validate(['reply' => ['required', 'string', 'max:2000']]);
        $staffRequest->update(['reply' => $data['reply'], 'replied_at' => now(), 'replied_by' => $user->id]);

        return back()->with('status', '返信しました。');
    }

    public function acknowledgeSupport(CastSupportRequest $support): RedirectResponse
    {
        $this->authorizeSupport($support);

        $support->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => Auth::id(),
        ]);

        return back()->with('status', '相談を確認済みにしました。');
    }

    public function resolveSupport(CastSupportRequest $support): RedirectResponse
    {
        $this->authorizeSupport($support);
        $support->update(['status' => 'resolved']);

        return back()->with('status', '相談を対応済みにしました。');
    }

    /** 公開先に合致する受信者だけが操作できる。 */
    private function authorizeSupport(CastSupportRequest $support): void
    {
        $user = Auth::user();
        $audience = $support->audience->value;

        if ($audience === 'manager') {
            abort_unless($user->isManager() || $user->isAdmin(), 403);

            return;
        }
        // audience = staff：担当黒服のみ
        abort_unless($user->isStaff(), 403);
        $staff = StaffProfile::where('user_id', $user->id)->first();
        $ok = $staff && $staff->assignedCasts()->where('casts.id', $support->cast_id)->exists();
        abort_unless($ok, 403);
    }
}
