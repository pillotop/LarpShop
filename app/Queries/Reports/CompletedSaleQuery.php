<?php

namespace App\Queries\Reports;

use App\Data\Reports\ReportPeriod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CompletedSaleQuery
{
    public function summary(ReportPeriod $period, string $salesSource): array
    {
        $totals = $this->forPeriod($period, $salesSource)
            ->selectRaw('COUNT(*) as paid_orders, COALESCE(SUM(orders.total_amount), 0) as revenue')
            ->first();
        $revenue = (float) $totals->revenue;
        $count = (int) $totals->paid_orders;

        return [
            'gross_revenue' => $revenue,
            'paid_orders' => $count,
            'average_order_value' => $count > 0 ? $revenue / $count : 0,
        ];
    }

    public function forPeriod(ReportPeriod $period, string $salesSource): EloquentBuilder
    {
        $simulatedProviders = collect(config('payments.providers'))
            ->filter(fn (array $provider) => $provider['simulated'] ?? false)
            ->keys()->all();

        return Order::query()
            ->joinSub($this->firstSuccessfulPayments(), 'first_payment', 'first_payment.order_id', '=', 'orders.order_id')
            ->where('orders.status', OrderStatus::Paid->value)
            ->when($period->since, fn ($query, $since) => $query->where('first_payment.completed_at', '>=', $since))
            ->where('first_payment.completed_at', $period->endInclusive ? '<=' : '<', $period->until)
            ->when($salesSource === 'simulated', fn ($query) => $query->whereIn('first_payment.provider', $simulatedProviders))
            ->when($salesSource === 'provider', fn ($query) => $query->whereNotIn('first_payment.provider', $simulatedProviders));
    }

    private function firstSuccessfulPayments(): Builder
    {
        // Choose one payment per order before applying period or source filters.
        // A duplicate success must not count the same sale in another reporting period.
        return DB::table('payments')
            ->select('order_id', 'provider', 'completed_at')
            ->where('status', PaymentStatus::Succeeded->value)
            ->whereNotNull('completed_at')
            ->whereNotExists(function (Builder $earlier) {
                $earlier->selectRaw('1')->from('payments as earlier')
                    ->whereColumn('earlier.order_id', 'payments.order_id')
                    ->where('earlier.status', PaymentStatus::Succeeded->value)
                    ->whereNotNull('earlier.completed_at')
                    ->where(function (Builder $query) {
                        $query->whereColumn('earlier.completed_at', '<', 'payments.completed_at')
                            ->orWhere(function (Builder $tie) {
                                $tie->whereColumn('earlier.completed_at', 'payments.completed_at')
                                    ->whereColumn('earlier.payment_id', '<', 'payments.payment_id');
                            });
                    });
            });
    }
}
