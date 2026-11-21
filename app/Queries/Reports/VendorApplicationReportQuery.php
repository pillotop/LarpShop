<?php

namespace App\Queries\Reports;

use App\Data\Reports\ReportPeriod;
use App\Enums\Vendors\VendorApplicationStatus;
use App\Models\Vendor;
use App\Models\VendorApplication;

class VendorApplicationReportQuery
{
    public function overview(ReportPeriod $period): array
    {
        $submitted = VendorApplication::query()->whereNotNull('submitted_at')
            ->where('submitted_at', '<=', $period->until)
            ->whereIn('status', [
                VendorApplicationStatus::Submitted->value,
                VendorApplicationStatus::Approved->value,
                VendorApplicationStatus::Rejected->value,
            ]);
        $cohort = (clone $submitted)
            ->when($period->since, fn ($query, $since) => $query->where('submitted_at', '>=', $since));
        $counts = (clone $cohort)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $reviewTimes = (clone $cohort)->whereIn('status', [VendorApplicationStatus::Approved->value, VendorApplicationStatus::Rejected->value])
            ->whereNotNull('reviewed_at')
            ->whereColumn('reviewed_at', '>=', 'submitted_at')
            ->where('reviewed_at', '<=', $period->until)
            ->selectRaw('COUNT(*) as samples, AVG(TIMESTAMPDIFF(SECOND, submitted_at, reviewed_at)) / 3600 as hours')
            ->first();
        $pending = (clone $submitted)->where('status', VendorApplicationStatus::Submitted->value);

        return [
            'submitted' => (int) $counts->sum(),
            'statuses' => [
                'submitted' => (int) ($counts['submitted'] ?? 0),
                'approved' => (int) ($counts['approved'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
            ],
            'review_samples' => (int) $reviewTimes->samples,
            'average_review_hours' => $reviewTimes->hours === null ? null : round((float) $reviewTimes->hours, 1),
            'pending_now' => (clone $pending)->count(),
            'oldest_pending' => $pending->orderBy('submitted_at')->orderBy('vendor_application_id')->limit(5)
                ->get(['vendor_application_id', 'vendor_id', 'store_name', 'submitted_at']),
            'legacy_pending' => Vendor::query()->where('status', 'pending')->whereDoesntHave('application')
                ->where('created_at', '<=', $period->until)->count(),
        ];
    }
}
