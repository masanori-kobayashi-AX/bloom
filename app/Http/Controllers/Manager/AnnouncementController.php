<?php

namespace App\Http\Controllers\Manager;

use App\Enums\AnnouncementCategory;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Cast;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * 責任者による お知らせ・営業ヒント の配信管理（§5-13）。既読状況も確認できる。
 */
class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::withCount('reads')->latest('published_at')->latest()->paginate(20);
        $castCount = Cast::where('status', 'active')->count();

        return view('manager.announcements.index', compact('announcements', 'castCount'));
    }

    public function create()
    {
        return view('manager.announcements.create', ['categories' => AnnouncementCategory::options()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(AnnouncementCategory::options()))],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:4000'],
            'importance' => ['required', Rule::in(['low', 'normal', 'high'])],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        Announcement::create([
            'store_id' => CurrentStore::id(),
            'author_id' => Auth::id(),
            'category' => $data['category'],
            'title' => $data['title'],
            'body' => $data['body'],
            'importance' => $data['importance'],
            'expires_on' => $data['expires_on'] ?? null,
            'published_at' => now(),
        ]);

        return redirect()->route('manager.announcements.index')->with('status', 'お知らせを配信しました。');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete(); // 論理削除

        return redirect()->route('manager.announcements.index')->with('status', 'お知らせを取り下げました。');
    }
}
