<?php

namespace App\Http\Controllers\Cast;

use App\Enums\CustomerImportance;
use App\Enums\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Models\CastCustomerRelationship;
use App\Models\Customer;
use App\Models\CustomerAlias;
use App\Models\CustomerStatusHistory;
use App\Support\CurrentStore;
use App\Support\CustomerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * キャストの顧客管理（スマホ最優先）。キャストは自分の顧客のみ、オーナーは全件閲覧可。
 */
class CustomerController extends Controller
{
    /** キャスト本人であることを要求（オーナーは閲覧のみ別途許可）。 */
    private function requireCast(): int
    {
        $castId = CustomerAccess::currentCastId();
        abort_if($castId === null, 403, 'キャストのみ登録・編集できます。');

        return $castId;
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $castId = CustomerAccess::currentCastId();

        $query = CastCustomerRelationship::with('customer')
            ->when($castId !== null, fn ($qq) => $qq->where('cast_id', $castId));

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('customer_name', 'like', $like)
                    ->orWhere('line_display_name', 'like', $like)
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('kana', 'like', $like)
                            ->orWhere('customer_code', 'like', $like)
                            ->orWhereHas('aliases', fn ($a) => $a->where('value', 'like', $like));
                    });
            });
        }

        $relationships = $query->orderByRaw('updated_at desc')->paginate(30)->withQueryString();

        return view('cast.customers.index', [
            'relationships' => $relationships,
            'q' => $q,
            'statuses' => CustomerStatus::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->requireCast();

        // 名寄せ警告からの戻り：自分の類似顧客を提示（他キャスト分は件数のみ）
        $dup = $request->session()->get('dup_check');
        $dupOwn = collect();
        if ($dup && ! empty($dup['ownIds'])) {
            $dupOwn = CastCustomerRelationship::whereIn('id', $dup['ownIds'])->get();
        }

        return view('cast.customers.create', [
            'statuses' => CustomerStatus::options(),
            'dup' => $dup,
            'dupOwn' => $dupOwn,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $castId = $this->requireCast();
        $storeId = CurrentStore::id();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'line_display_name' => ['nullable', 'string', 'max:100'],
            'line_exchanged_on' => ['nullable', 'date'],
            'first_met_on' => ['nullable', 'date'],
            'met_context' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(array_keys(CustomerStatus::options()))],
            'kana' => ['nullable', 'string', 'max:100'],
            'first_note' => ['nullable', 'string', 'max:1000'],
            // 名寄せ確認・既存顧客への追加
            'confirmed' => ['nullable', 'boolean'],
            'attach_customer_id' => ['nullable', 'integer'],
        ]);

        // 既存顧客へ自分の関係を追加（本人が確認画面で選択したケース）
        if (! empty($data['attach_customer_id'])) {
            $customer = Customer::where('id', $data['attach_customer_id'])->first();
            abort_if($customer === null, 404);
            // 同一顧客への自分の関係が既にある場合は重複作成しない
            $exists = CastCustomerRelationship::where('cast_id', $castId)
                ->where('customer_id', $customer->id)->exists();
            if ($exists) {
                return redirect()->route('cast.customers.index')
                    ->with('status', 'この顧客とは既に関係が登録されています。');
            }
            $rel = $this->createRelationship($castId, $storeId, $customer, $data);

            return redirect()->route('cast.customers.show', $rel)
                ->with('status', '既存顧客にあなたの関係を追加しました。');
        }

        // 名寄せ候補チェック（未確認のときのみ）
        if (empty($data['confirmed'])) {
            $candidates = $this->findDuplicateCandidates($castId, $storeId, $data);
            if ($candidates['own']->isNotEmpty() || $candidates['otherCount'] > 0) {
                return redirect()->route('cast.customers.create')
                    ->withInput()
                    ->with('dup_check', [
                        'ownIds' => $candidates['own']->pluck('id')->all(),
                        'otherCount' => $candidates['otherCount'],
                    ]);
            }
        }

        // 新規顧客本体＋関係＋LINE別名を作成
        $rel = DB::transaction(function () use ($castId, $storeId, $data) {
            $customer = Customer::create([
                'store_id' => $storeId,
                'kana' => $data['kana'] ?? null,
            ]);

            return $this->createRelationship($castId, $storeId, $customer, $data);
        });

        return redirect()->route('cast.customers.show', $rel)
            ->with('status', '顧客を登録しました。次のアクションも登録できます。');
    }

    /** 関係・LINE別名・初期ステータス履歴・任意の初回メモをまとめて作成。 */
    private function createRelationship(int $castId, int $storeId, Customer $customer, array $data): CastCustomerRelationship
    {
        return DB::transaction(function () use ($castId, $storeId, $customer, $data) {
            $rel = CastCustomerRelationship::create([
                'store_id' => $storeId,
                'customer_id' => $customer->id,
                'cast_id' => $castId,
                'customer_name' => $data['customer_name'],
                'line_display_name' => $data['line_display_name'] ?? null,
                'status' => $data['status'],
                'line_exchanged_on' => $data['line_exchanged_on'] ?? null,
                'first_met_on' => $data['first_met_on'] ?? null,
                'met_context' => $data['met_context'] ?? null,
            ]);

            if (! empty($data['line_display_name'])) {
                CustomerAlias::create([
                    'store_id' => $storeId,
                    'customer_id' => $customer->id,
                    'cast_id' => $castId,
                    'type' => 'line_current',
                    'value' => $data['line_display_name'],
                ]);
            }

            CustomerStatusHistory::create([
                'store_id' => $storeId,
                'relationship_id' => $rel->id,
                'from_status' => null,
                'to_status' => $data['status'],
                'changed_by' => Auth::id(),
                'note' => '新規登録',
                'created_at' => now(),
            ]);

            if (! empty($data['first_note'])) {
                $rel->notes()->create([
                    'store_id' => $storeId,
                    'cast_id' => $castId,
                    'body' => $data['first_note'],
                ]);
            }

            return $rel;
        });
    }

    /**
     * 名寄せ候補。自分の関係は詳細を返し、他キャストの類似は件数のみ（プライバシー保持）。
     * @return array{own:\Illuminate\Support\Collection,otherCount:int}
     */
    private function findDuplicateCandidates(int $castId, int $storeId, array $data): array
    {
        $line = $data['line_display_name'] ?? null;
        $name = $data['customer_name'];
        $kana = $data['kana'] ?? null;

        $matchCustomerIds = Customer::query()
            ->where('store_id', $storeId)
            ->where(function ($q) use ($line, $kana) {
                if ($line) {
                    $q->orWhereHas('aliases', fn ($a) => $a->where('value', $line));
                }
                if ($kana) {
                    $q->orWhere('kana', $kana);
                }
            })
            ->pluck('id');

        $own = CastCustomerRelationship::where('cast_id', $castId)
            ->where(function ($q) use ($line, $name, $matchCustomerIds) {
                if ($line) {
                    $q->orWhere('line_display_name', $line);
                }
                $q->orWhere('customer_name', $name);
                if ($matchCustomerIds->isNotEmpty()) {
                    $q->orWhereIn('customer_id', $matchCustomerIds);
                }
            })
            ->with('customer')
            ->get();

        // 他キャストの類似候補（詳細は出さず件数のみ）
        $otherCount = 0;
        if ($matchCustomerIds->isNotEmpty()) {
            $otherCount = CastCustomerRelationship::whereIn('customer_id', $matchCustomerIds)
                ->where('cast_id', '!=', $castId)
                ->count();
        }

        return ['own' => $own, 'otherCount' => $otherCount];
    }

    public function show(CastCustomerRelationship $customer)
    {
        abort_unless(CustomerAccess::canView($customer), 403);
        $rel = $customer;
        $rel->load([
            'customer.alerts',
            'customer.keptBottles',
            'sharedNotes',
            'nextActions' => fn ($q) => $q->orderBy('completed')->orderBy('due_on'),
            'statusHistories',
        ]);

        $today = now()->toDateString();
        $upcomingPlans = $rel->customer->visitPlans()
            ->whereDate('planned_date', '>=', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('planned_date')->get();
        $todayHandovers = $rel->customer->handovers()->whereDate('for_date', $today)->latest()->get();
        $pastVisits = $rel->customer->visits()->where('status', 'left')->orderByDesc('arrived_at')->limit(10)->get();
        $presentVisit = $rel->customer->visits()->where('status', 'present')->latest('arrived_at')->first();

        $canViewPrivate = CustomerAccess::canViewPrivateNotes($rel);
        $privateNotes = $canViewPrivate ? $rel->notes()->get() : collect();

        return view('cast.customers.show', [
            'rel' => $rel,
            'canEdit' => CustomerAccess::canEdit($rel),
            'canViewPrivate' => $canViewPrivate,
            'privateNotes' => $privateNotes,
            'statuses' => CustomerStatus::options(),
            'importances' => CustomerImportance::options(),
            'upcomingPlans' => $upcomingPlans,
            'todayHandovers' => $todayHandovers,
            'pastVisits' => $pastVisits,
            'presentVisit' => $presentVisit,
        ]);
    }

    public function edit(CastCustomerRelationship $customer)
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        return view('cast.customers.edit', [
            'rel' => $customer,
            'statuses' => CustomerStatus::options(),
            'importances' => CustomerImportance::options(),
        ]);
    }

    public function update(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'line_display_name' => ['nullable', 'string', 'max:100'],
            'importance' => ['nullable', Rule::in(array_keys(CustomerImportance::options()))],
            'favorite_drink' => ['nullable', 'string', 'max:100'],
            'hobby' => ['nullable', 'string', 'max:100'],
            'usual_weekday' => ['nullable', 'string', 'max:50'],
            'visit_expectation' => ['nullable', 'string', 'max:100'],
            'next_talk' => ['nullable', 'string', 'max:1000'],
            'avatar_emoji' => ['nullable', 'string', 'max:8'],
            'kana' => ['nullable', 'string', 'max:100'],
            'age_range' => ['nullable', 'string', 'max:20'],
            'occupation' => ['nullable', 'string', 'max:50'],
            'area' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($customer, $data) {
            $oldLine = $customer->line_display_name;
            $customer->update([
                'customer_name' => $data['customer_name'],
                'line_display_name' => $data['line_display_name'] ?? null,
                'importance' => $data['importance'] ?? null,
                'favorite_drink' => $data['favorite_drink'] ?? null,
                'hobby' => $data['hobby'] ?? null,
                'usual_weekday' => $data['usual_weekday'] ?? null,
                'visit_expectation' => $data['visit_expectation'] ?? null,
                'next_talk' => $data['next_talk'] ?? null,
                'avatar_emoji' => $data['avatar_emoji'] ?? null,
            ]);
            $customer->customer->update([
                'kana' => $data['kana'] ?? null,
                'age_range' => $data['age_range'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'area' => $data['area'] ?? null,
                'birthday' => $data['birthday'] ?? null,
            ]);

            // LINE表示名が変わったら旧名をエイリアスに残す（名寄せ・検索用）
            if ($oldLine && $oldLine !== ($data['line_display_name'] ?? null)) {
                CustomerAlias::create([
                    'store_id' => $customer->store_id,
                    'customer_id' => $customer->customer_id,
                    'cast_id' => $customer->cast_id,
                    'type' => 'line_old',
                    'value' => $oldLine,
                ]);
            }
        });

        return redirect()->route('cast.customers.show', $customer)->with('status', '顧客情報を更新しました。');
    }

    public function updateStatus(Request $request, CastCustomerRelationship $customer): RedirectResponse
    {
        abort_unless(CustomerAccess::canEdit($customer), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(CustomerStatus::options()))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $from = $customer->status?->value;
        if ($from !== $data['status']) {
            DB::transaction(function () use ($customer, $from, $data) {
                $customer->update(['status' => $data['status']]);
                CustomerStatusHistory::create([
                    'store_id' => $customer->store_id,
                    'relationship_id' => $customer->id,
                    'from_status' => $from,
                    'to_status' => $data['status'],
                    'changed_by' => Auth::id(),
                    'note' => $data['note'] ?? null,
                    'created_at' => now(),
                ]);
            });
        }

        return redirect()->route('cast.customers.show', $customer)->with('status', 'ステータスを変更しました。');
    }
}
