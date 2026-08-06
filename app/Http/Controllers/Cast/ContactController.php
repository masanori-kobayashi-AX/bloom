<?php

namespace App\Http\Controllers\Cast;

use App\Enums\ContactChannel;
use App\Enums\VisitPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\ContactLog;
use App\Models\VisitPlan;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * 顧客とのやり取り（電話・LINE）の記録と、来店（今きた）の登録。
 */
class ContactController extends Controller
{
    /** 電話・LINE・その他のやり取りをワンタップ記録。 */
    public function store(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'channel' => ['required', Rule::in(['phone', 'line', 'other'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->log($customer, $data['channel'], $data['note'] ?? null);

        $label = ContactChannel::from($data['channel'])->label();

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', "{$label}の履歴を記録しました。")
            ->withFragment('history');
    }

    /** 「今きた」＝本日の来店を登録（黒服の予定に出す）＋履歴に来店を記録。 */
    public function arrived(CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        DB::transaction(function () use ($customer) {
            // 同日に未消化の予定が無ければ作成（重複防止）
            $exists = VisitPlan::where('customer_id', $customer->customer_id)
                ->whereDate('planned_date', now()->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])->exists();

            if (! $exists) {
                VisitPlan::create([
                    'store_id' => $customer->store_id,
                    'customer_id' => $customer->customer_id,
                    'cast_id' => $customer->cast_id,
                    'planned_date' => now()->toDateString(),
                    'planned_time' => now()->format('H:i'),
                    'status' => VisitPlanStatus::Confirmed->value,
                    'note' => '本日来店（キャストが「今きた」を登録）',
                ]);
            }

            $this->log($customer, 'visit', '来店（今きた）');
        });

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '本日の来店を登録しました。黒服の「今日の予定」に表示されます。')
            ->withFragment('history');
    }

    private function log(CastCustomerRelationship $customer, string $channel, ?string $note): void
    {
        ContactLog::create([
            'store_id' => $customer->store_id,
            'relationship_id' => $customer->id,
            'cast_id' => $customer->cast_id,
            'customer_id' => $customer->customer_id,
            'channel' => $channel,
            'note' => $note,
            'contacted_at' => now(),
        ]);
    }
}
