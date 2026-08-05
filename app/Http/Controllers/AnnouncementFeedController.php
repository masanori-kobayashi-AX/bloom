<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * お知らせ閲覧（キャスト・黒服・責任者 共通）。既読を記録する。
 */
class AnnouncementFeedController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $readIds = AnnouncementRead::where('user_id', $userId)->pluck('announcement_id')->all();

        $announcements = Announcement::active()
            ->orderByRaw("field(importance,'high','normal','low')")
            ->latest('published_at')
            ->get();

        return view('announcements.index', compact('announcements', 'readIds'));
    }

    public function read(Announcement $announcement): RedirectResponse
    {
        AnnouncementRead::firstOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => Auth::id()],
            ['store_id' => CurrentStore::id(), 'read_at' => now()]
        );

        return back()->with('status', '既読にしました。');
    }
}
