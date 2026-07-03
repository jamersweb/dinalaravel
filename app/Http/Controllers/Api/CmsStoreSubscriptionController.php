<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CmsStoreSubscriptionController extends Controller
{
    private const PRODUCT_LABELS = [
        'fwd_basic_plan' => 'Basic',
        'fwd_premium' => 'Premium',
    ];

    public function summary()
    {
        $activeQuery = StoreSubscription::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        return response()->json([
            'status' => true,
            'data' => [
                'total' => StoreSubscription::count(),
                'active' => (clone $activeQuery)->count(),
                'expired' => StoreSubscription::where('status', 'expired')->count(),
                'ios' => StoreSubscription::where('platform', 'ios')->count(),
                'android' => StoreSubscription::where('platform', 'android')->count(),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $query = StoreSubscription::query()
            ->with(['user:id,email,name'])
            ->orderByDesc('verified_at')
            ->orderByDesc('id');

        $platform = $request->query('platform');
        if (in_array($platform, ['ios', 'android'], true)) {
            $query->where('platform', $platform);
        }

        $status = $request->query('status');
        if (in_array($status, ['active', 'expired', 'pending'], true)) {
            $query->where('status', $status);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('transaction_id', 'like', '%' . $search . '%')
                    ->orWhere('original_transaction_id', 'like', '%' . $search . '%')
                    ->orWhere('product_id', 'like', '%' . $search . '%')
                    ->orWhere('purchase_token', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $records = $query->paginate((int) $request->query('per_page', 25));

        $records->getCollection()->transform(function (StoreSubscription $record) {
            return $this->formatRecord($record);
        });

        return response()->json([
            'status' => true,
            'data' => $records->items(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function show($id)
    {
        $record = StoreSubscription::with(['user:id,email,name'])->find($id);
        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Subscription record not found.',
            ], 404);
        }

        $payload = $this->formatRecord($record, true);
        $payload['raw_payload'] = $record->raw_payload;

        return response()->json([
            'status' => true,
            'data' => $payload,
        ]);
    }

    private function formatRecord(StoreSubscription $record, bool $includeToken = false): array
    {
        $user = $record->user;
        $clientName = $user ? trim((string) $user->name) : '';
        if ($user && method_exists($user, 'fullName')) {
            $fullName = trim((string) $user->fullName());
            if ($fullName !== '') {
                $clientName = ucfirst($fullName);
            }
        }

        return [
            'id' => $record->id,
            'user_id' => $record->user_id,
            'client_name' => $clientName !== '' ? $clientName : '—',
            'client_email' => $user?->email ?? '—',
            'platform' => $record->platform,
            'platform_label' => $record->platform === 'ios' ? 'Apple' : 'Google',
            'product_id' => $record->product_id,
            'product_label' => self::PRODUCT_LABELS[$record->product_id] ?? $record->product_id,
            'base_plan_id' => $record->base_plan_id,
            'order_id' => $record->transaction_id,
            'transaction_id' => $record->transaction_id,
            'original_transaction_id' => $record->original_transaction_id,
            'purchase_token' => $includeToken ? $record->purchase_token : $this->maskToken($record->purchase_token),
            'status' => $record->status,
            'purchased_at' => $this->formatDate($record->purchased_at),
            'expires_at' => $this->formatDate($record->expires_at),
            'verified_at' => $this->formatDate($record->verified_at),
            'created_at' => $this->formatDate($record->created_at),
        ];
    }

    private function formatDate($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->format('d M Y, H:i');
    }

    private function maskToken(?string $token): ?string
    {
        if ($token === null || $token === '') {
            return null;
        }

        if (strlen($token) <= 12) {
            return $token;
        }

        return substr($token, 0, 6) . '…' . substr($token, -6);
    }
}
