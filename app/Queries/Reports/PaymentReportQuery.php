<?php

namespace App\Queries\Reports;

use App\Data\Reports\ReportPeriod;
use App\Enums\PaymentStatus;
use App\Models\Payment;

class PaymentReportQuery
{
    public function overview(ReportPeriod $period): array
    {
        $attempts = Payment::query()
            ->when($period->since, fn ($query, $since) => $query->where('created_at', '>=', $since))
            ->where('created_at', $period->endInclusive ? '<=' : '<', $period->until);

        $groups = [
            'succeeded' => [PaymentStatus::Succeeded->value],
            'failed' => [PaymentStatus::Failed->value],
            'in_progress' => [PaymentStatus::Pending->value, PaymentStatus::Processing->value, PaymentStatus::Delayed->value],
            'cancelled' => [PaymentStatus::Cancelled->value],
            'refund_related' => [PaymentStatus::Refunded->value, PaymentStatus::RefundRequired->value],
        ];
        $providers = (clone $attempts)->select('provider')
            ->selectRaw('COUNT(*) as attempts, COUNT(DISTINCT order_id) as orders');
        foreach ($groups as $name => $statuses) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $providers->selectRaw("SUM(CASE WHEN status IN ({$placeholders}) THEN 1 ELSE 0 END) as {$name}", $statuses);
        }

        $rows = $providers->groupBy('provider')->orderByDesc('attempts')->orderBy('provider')->get()
            ->map(function ($row) use ($groups) {
                $counts = [];
                foreach (array_keys($groups) as $name) {
                    $counts[$name] = (int) $row->$name;
                }

                return [
                    'provider' => $row->provider,
                    'label' => config("payments.providers.{$row->provider}.label", str($row->provider)->headline()->toString()),
                    'simulated' => (bool) config("payments.providers.{$row->provider}.simulated", false),
                    'attempts' => (int) $row->attempts,
                    'orders' => (int) $row->orders,
                    ...$counts,
                    'other' => (int) $row->attempts - array_sum($counts),
                    'success_rate' => round($counts['succeeded'] / $row->attempts * 100, 1),
                ];
            })->all();

        $total = array_sum(array_column($rows, 'attempts'));

        return [
            'total_attempts' => $total,
            'unique_orders' => (clone $attempts)->distinct()->count('order_id'),
            'success_rate' => $total > 0 ? round(array_sum(array_column($rows, 'succeeded')) / $total * 100, 1) : 0,
            'providers' => $rows,
        ];
    }
}
