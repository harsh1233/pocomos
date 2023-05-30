<table>
    <thead>
        <tr>
            <th align="center">Customer Id </th>
            <th align="center">Customer </th>
            <th align="center">Invoice #</th>
            <th align="center">Address</th>
            <th align="center">Postal Code</th>
            <th align="center">Job Type</th>
            <th align="center">Service Type</th>
            <th align="center">Marketing Type</th>                                    
            <th align="center">Date Completed</th>
            <th align="center">Time In</th>
            <th align="center">Time Out</th>                                    
            <th align="center">Total Time</th>
            <th align="center">Total Job Value</th>
            <th align="center">Total Outstanding</th>
            <th align="center">Monthly Billing Price</th>
            <th align="center">Commission Type</th>
            <th align="center">Commission Value</th>
            <th align="center">Technician</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        @foreach($data as $val)
            <?php $val = (array)$val; ?>

            @if ($val['status'] == 'Complete')
                @if ($val['commission_type'] == 'Flat')
                    {{ $commission_rate =  $val['commission_value'] }}
                @elseif ($val['commission_type'] == 'Rate')
                    {{ $commission_rate =  $val['commission_value'] * 100 }}
                @else
                    {{ $commission_rate =  0 }}
                @endif
            @else
                    {{ $commission_rate =  0 }}
            @endif

            @if ($val['billing_frequency'] == "Monthly")
                {{ $job_value = $val['recurring_price'] }}
            @endif
            @if (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Quarterly"))
                {{ $job_value = $val['recurring_price'] * 3 }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Weekly"))
                {{ $job_value = $val['recurring_price'] * 12 / 52 }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Bi-weekly"))
                {{ $job_value = $val['recurring_price'] * 12 / (52/2) }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Tri-weekly"))
                {{ $job_value = $val['recurring_price'] * 12 / (52/3) }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Monthly"))
                {{ $job_value = $val['recurring_price'] }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Bi-monthly"))
                {{ $job_value = $val['recurring_price'] * 2 }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Twice Per Month"))
                {{ $job_value = $val['recurring_price'] / 2 }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Every Six Weeks"))
                {{ $job_value = $val['recurring_price'] * 12 / (52/6) }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Semi-annually"))
                {{ $job_value = $val['recurring_price'] * 6 }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Annually"))
                {{ $job_value = $val['recurring_price'] * 12 }}
            @elseif (($val['billing_frequency'] == "Monthly") && ($val['service_frequency'] == "Tri-Annually"))
                {{ $job_value = $val['recurring_price'] * 4 }}
            @else
                {{ $job_value = $val['agreement_initial_price'] }}
            @endif

            <tr>
                <td>{{ $val['customer_id'] }} </td>
                <td>{{ $val['customer_name'] }}</td>
                <td>{{ $val['invoice_id'] }}</td>
                <td>{{ $val['street'] }}</td>
                <td>{{ $val['postal_code'] }}</td>
                <td>{{ $val['type'] }}</td>
                <td>{{ $val['service_type'] }}</td>
                <td>{{ $val['marketing_type'] }}</td>
                <td>{{ $val['date_completed'] }}</td>
                @if ($val['time_begin'] == "Never")
                    <td>{{ $val['time_begin'] }}</td>
                @else
                    <td>{{ date("H:i", strtotime($val['time_begin'])) }}</td>
                @endif
                @if ($val['time_end'] == "Never")
                    <td>{{ $val['time_end'] }}</td>
                @else
                    <td>{{ date("H:i", strtotime($val['time_end'])) }}</td>
                @endif

                @if($val['time_begin'] == "Never" || $val['time_end'] == "Never")
                    <td>0</td>
                @else
                    <td>{{ round( (date('U',  strtotime($val['time_end'])) - date('U',  strtotime($val['time_begin'])))/ 60 ) }} mins.</td>
                @endif
                <td>{{ $job_value }}</td>
                <td>
                    @if ($val['billing_frequency'] == "Monthly")
                        ${{ $val['recurring_price'] }}
                    @else
                        ${{ 0.00 }} 
                    @endif
                </td>
                <td>{{ $val['balance'] }}</td>
                <td>{{ $val['commission_type'] }}</td>
                @if ($val['commission_type'] == 'Rate' && $val['status'] == 'Complete' )
                    <td> ${{ ($val['total_commission'] * $val['agreement_initial_price']) }}</td>
                @elseif ($val['commission_type'] == 'Flat' && $val['status'] == 'Complete')
                    <td>${{ $commission_rate }}</td>
                @else
                    <td>$0</td>
                @endif
                <td>{{ $val['technician_name'] }}</td>
            </tr>
        @endforeach
        @endif
    </tbody>
</table>
