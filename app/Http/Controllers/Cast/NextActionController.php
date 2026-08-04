<?php

namespace App\Http\Controllers\Cast;

use App\Enums\NextActionKind;
use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\NextAction;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 次のアクション（§5-8）。連絡漏れ防止。MVPでは自動送信しない。
 */
class NextActionController extends Controller
{
    /** アクションタブ：自分の未対応アクションを期限順に一覧。 */
    public function index()
    {
        $castId = CustomerAccess::currentCastId();
        abort_if($castId === null, 403);

        $open = NextAction::with('relationship')
            ->where('cast_id', $castId)->where('completed', false)
            ->orderByRaw('due_on is null, due_on asc')
            ->get();

        $doneRecently = NextAction::with('relationship')
            ->where('cast_id', $castId)->where('completed', true)
            ->orderByDesc('completed_at')->limit(20)->get();

        return view('cast.actions.index', compact('open', 'doneRecently'));
    }

    public function store(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:200'],
            'kind' => ['required', Rule::in(array_keys(NextActionKind::options()))],
            'due_on' => ['nullable', 'date'],
        ]);

        $customer->nextActions()->create([
            'store_id' => $customer->store_id,
            'cast_id' => $customer->cast_id,
            'content' => $data['content'],
            'kind' => $data['kind'],
            'due_on' => $data['due_on'] ?? null,
        ]);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '次のアクションを登録しました。')
            ->withFragment('actions');
    }

    public function complete(Request $request, NextAction $action): RedirectResponse
    {
        $castId = CustomerAccess::currentCastId();
        abort_if($castId === null || $action->cast_id !== $castId, 403);

        $action->update(['completed' => true, 'completed_at' => now()]);

        return back()->with('status', 'アクションを完了にしました。');
    }
}
