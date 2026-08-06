<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use App\Models\Visit;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 席マスタの管理と席割り（今どの席に誰がいるか）。店長・管理者。
 */
class SeatController extends Controller
{
    public function index()
    {
        $seats = Seat::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        // 今の席割り（来店中の席→顧客名）
        $present = Visit::with(['customer', 'primaryCast'])->present()->get();
        $occupancy = [];
        foreach ($present as $v) {
            $rel = $v->customer->relationships->firstWhere('cast_id', $v->primary_cast_id)
                ?? $v->customer->relationships->first();
            $occupancy[(string) $v->seat][] = [
                'visit' => $v,
                'rel' => $rel,
                'name' => $rel?->customer_name ?? ('顧客#' . $v->customer_id),
            ];
        }

        return view('manager.seats.index', compact('seats', 'occupancy', 'present'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        Seat::create([
            'store_id' => CurrentStore::id(),
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('admin.seats.index')->with('status', "席「{$data['name']}」を追加しました。");
    }

    public function destroy(Seat $seat): RedirectResponse
    {
        abort_unless($seat->store_id === CurrentStore::id(), 403);
        $seat->delete();

        return redirect()->route('admin.seats.index')->with('status', '席を削除しました（来店中の記録には影響しません）。');
    }
}
