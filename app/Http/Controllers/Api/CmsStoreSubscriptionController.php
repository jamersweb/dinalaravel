<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreSubscription;
use App\Services\GooglePlayClient;
use App\Services\StoreSubscriptionPricing;
use App\Support\StoreSubscriptionProducts;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CmsStoreSubscriptionController extends Controller
{
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

    public function legacySummary(Request $request)
    {
        $duration = (string) $request->query('duration', 'month');
        [$startDate, $endDate, $bucketCount, $bucketUnit, $labelFormat] = $this->resolveLegacyDuration($duration);

        $records = StoreSubscription::query()
            ->whereBetween('purchased_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get();

        $labels = [];
        $grossDataset = [];
        $netDataset = [];
        $salesDataset = [];
        $activeDataset = [];

        for ($index = 0; $index < $bucketCount; $index++) {
            $bucketStart = $startDate->copy()->add($index, $bucketUnit)->startOfDay();
            $bucketEnd = $bucketStart->copy()->endOf($bucketUnit);

            if ($bucketEnd->greaterThan($endDate)) {
                $bucketEnd = $endDate->copy()->endOfDay();
            }

            $bucketRecords = $records->filter(function (StoreSubscription $record) use ($bucketStart, $bucketEnd) {
                return $record->purchased_at !== null
                    && $record->purchased_at->between($bucketStart, $bucketEnd);
            });

            $gross = round($bucketRecords->sum(fn (StoreSubscription $record) => $this->extractAmount($record)), 2);
            $net = round($gross * 0.85, 2);
            $sales = $bucketRecords->count();
            $active = $bucketRecords->filter(fn (StoreSubscription $record) => $this->isActiveForMoment($record, $bucketEnd))->count();

            $labels[] = $bucketStart->format($labelFormat);
            $grossDataset[] = $gross;
            $netDataset[] = $net;
            $salesDataset[] = $sales;
            $activeDataset[] = $active;
        }

        return response()->json([
            'status' => true,
            'start_date' => $startDate->format('d M Y'),
            'end_date' => $endDate->format('d M Y'),
            'labels' => $labels,
            'gross_dataset' => $grossDataset,
            'net_dataset' => $netDataset,
            'sales_dataset' => $salesDataset,
            'active_dataset' => $activeDataset,
            'gross_revenue' => '$' . number_format(array_sum($grossDataset), 2),
            'net_revenue' => '$' . number_format(array_sum($netDataset), 2),
            'total_sales' => array_sum($salesDataset),
            'total_active' => !empty($activeDataset) ? end($activeDataset) : 0,
        ]);
    }

    public function legacySalesData()
    {
        $records = StoreSubscription::query()
            ->with(['user:id,email,name'])
            ->orderByDesc('purchased_at')
            ->orderByDesc('id')
            ->get();

        $data = $records->map(function (StoreSubscription $record) {
            $user = $record->user;
            $price = $this->resolvePricing($record, false);

            return [
                'id' => $record->id,
                'product' => StoreSubscriptionProducts::label($record->product_id),
                'client' => trim((string) ($user?->name ?? 'Unknown Client')) ?: 'Unknown Client',
                'status' => ucfirst((string) $record->status),
                'date_added' => $this->formatDate($record->created_at),
                'date_start' => $this->formatDate($record->purchased_at),
                'date_end' => $this->formatDate($record->expires_at),
                'next_inv' => $record->status === 'active' ? ($this->formatDate($record->expires_at) ?? '-') : '-',
                'platform' => $record->platform,
                'amount' => $price['formatted'] ?? '-',
                'transaction_id' => $record->transaction_id,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data' => $data,
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
        $payload['price'] = $this->resolvePricing($record, true);

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
            'client_name' => $clientName !== '' ? $clientName : '-',
            'client_email' => $user?->email ?? '-',
            'platform' => $record->platform,
            'platform_label' => $record->platform === 'ios' ? 'Apple' : 'Google',
            'product_id' => $record->product_id,
            'product_label' => StoreSubscriptionProducts::label($record->product_id),
            'subscription_tier' => StoreSubscriptionProducts::tier($record->product_id),
            'has_nutrition_access' => StoreSubscriptionProducts::hasNutritionAccess($record->product_id),
            'has_private_coaching' => StoreSubscriptionProducts::hasPrivateCoaching($record->product_id),
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
            'price' => $this->resolvePricing($record, false),
        ];
    }

    private function resolvePricing(StoreSubscription $record, bool $fetchOrder = false): ?array
    {
        $price = StoreSubscriptionPricing::fromPayload(
            $record->raw_payload,
            $record->platform,
            $record->product_id
        );

        if ($price || !$fetchOrder || $record->platform !== 'android' || !$record->transaction_id) {
            return $price;
        }

        $googlePlay = app(GooglePlayClient::class);
        $order = $googlePlay->fetchOrder($record->transaction_id);

        return $googlePlay->pricingFromOrder($order, $record->product_id);
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

        return substr($token, 0, 6) . '...' . substr($token, -6);
    }

    private function resolveLegacyDuration(string $duration): array
    {
        $endDate = now();

        return match ($duration) {
            '3months' => [$endDate->copy()->subMonths(2)->startOfMonth(), $endDate, 3, 'month', 'M Y'],
            '6months' => [$endDate->copy()->subMonths(5)->startOfMonth(), $endDate, 6, 'month', 'M Y'],
            'year' => [$endDate->copy()->subMonths(11)->startOfMonth(), $endDate, 12, 'month', 'M Y'],
            default => [$endDate->copy()->subWeeks(3)->startOfWeek(), $endDate, 4, 'week', 'd M'],
        };
    }

    private function extractAmount(StoreSubscription $record): float
    {
        $price = $this->resolvePricing($record, false);

        return isset($price['amount']) ? (float) $price['amount'] : 0.0;
    }

    private function isActiveForMoment(StoreSubscription $record, Carbon $moment): bool
    {
        if ($record->status !== 'active' || $record->purchased_at === null) {
            return false;
        }

        if ($record->expires_at === null) {
            return $record->purchased_at->lessThanOrEqualTo($moment);
        }

        return $record->purchased_at->lessThanOrEqualTo($moment)
            && $record->expires_at->greaterThan($moment);
    }
}
