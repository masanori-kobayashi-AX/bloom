<?php

namespace App\Http\Controllers\Cast;

use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\CastStaffAssignment;
use App\Models\StaffRequest;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * キャスト→担当黒服の業務連絡（§5-11）。顧客カードから登録する。
 */
class StaffRequestController extends Controller
{
    public function store(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        // 担当黒服（現在有効な紐付け）を宛先にする
        $staffId = CastStaffAssignment::where('cast_id', $customer->cast_id)
            ->whereNull('released_at')->value('staff_id');

        StaffRequest::create([
            'store_id' => $customer->store_id,
            'cast_id' => $customer->cast_id,
            'staff_id' => $staffId,
            'customer_id' => $customer->customer_id,
            'relationship_id' => $customer->id,
            'type' => $data['type'],
            'body' => $data['body'],
            'status' => 'pending',
        ]);

        return redirect()->route('cast.customers.show', $customer)
            ->with('status', '担当黒服への連絡を登録しました。')
            ->withFragment('staff-request');
    }
}
