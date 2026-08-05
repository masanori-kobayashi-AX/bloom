<?php

namespace App\Http\Controllers\Cast;

use App\Enums\CastCondition;
use App\Enums\SupportAudience;
use App\Http\Controllers\Controller;
use App\Models\CastSupportRequest;
use App\Models\StaffRequest;
use App\Support\CurrentStore;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * キャストのコンディション・相談（§5-12）。公開先を本人が選ぶ。
 * ※ここで得た情報を人事評価に流用しない（運用ルールで担保）。
 */
class SupportController extends Controller
{
    private function requireCast(): int
    {
        $castId = CustomerAccess::currentCastId();
        abort_if($castId === null, 403);

        return $castId;
    }

    public function index()
    {
        $castId = $this->requireCast();

        $supports = CastSupportRequest::where('cast_id', $castId)->latest()->limit(30)->get();
        $requests = StaffRequest::with('customer')->where('cast_id', $castId)->latest()->limit(30)->get();

        return view('cast.support.index', [
            'supports' => $supports,
            'requests' => $requests,
            'conditions' => CastCondition::options(),
            'audiences' => SupportAudience::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $castId = $this->requireCast();

        $data = $request->validate([
            'audience' => ['required', Rule::in(array_keys(SupportAudience::options()))],
            'condition' => ['nullable', Rule::in(array_keys(CastCondition::options()))],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        if (empty($data['condition']) && empty($data['body'])) {
            return back()->withErrors(['body' => 'コンディションを選ぶか、内容を入力してください。']);
        }

        CastSupportRequest::create([
            'store_id' => CurrentStore::id(),
            'cast_id' => $castId,
            'audience' => $data['audience'],
            'condition' => $data['condition'] ?? null,
            'body' => $data['body'] ?? null,
            'status' => 'open',
        ]);

        $to = SupportAudience::from($data['audience'])->label();

        return redirect()->route('cast.support.index')->with('status', "{$to}に共有しました。");
    }
}
