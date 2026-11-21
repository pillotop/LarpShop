@if ($change !== null)
    <div class="small fg-2 mt-2">
        <div>
            {{ __('management.report_copy.previous_value', [
                'value' => number_format($change['previous'], $isMoney ? 2 : 0).($isMoney ? ' '.$reportCurrency : ''),
            ]) }}
        </div>
        <div>
            @if ($change['percent'] === null)
                {{ __('management.report_copy.no_baseline') }}
            @else
                {{ __('management.report_copy.change_percent', [
                    'percent' => ($change['percent'] > 0 ? '+' : '').number_format($change['percent'], 1),
                ]) }}
            @endif
        </div>
    </div>
@endif
