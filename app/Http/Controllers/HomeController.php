<?php

namespace App\Http\Controllers;

use App\Models\CastCustomerRelationship;
use App\Models\NextAction;
use App\Support\CustomerAccess;
use Illuminate\Support\Facades\Auth;

/**
 * ロールに応じたトップ画面。キャストは「次に何をすべきか」が分かる表示にする（§5-2）。
 */
class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role();
        $data = ['user' => $user, 'role' => $role];

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
        }

        return view('home', $data);
    }
}
