<table>
    <thead>
        <tr>
            <th align="center">Account ID</th>
            <th align="center">Account Balance</th>
            <th align="center">Invoice ID</th>
            <th align="center">Invoice Due Date</th>
            <th align="center">Date Scheduled</th>
            <th align="center">Date Completed</th>
            <th align="center">Time In</th>
            <th align="center">Time Out</th>
            <th align="center">Technician</th>
            <th align="center">First Name</th>
            <th align="center">Last Name</th>
            <th align="center">Email</th>
            <th align="center">Account Status</th>
            <th align="center">Contract Type</th>
            <th align="center">Service Type</th>
            <th align="center">Service Frequency</th>
            <th align="center">Service Price</th>
            <th align="center">Unpaid Balance</th>
            <th align="center">Service Phone</th>
            <th align="center">Service Address</th>
            <th align="center">Service City</th>
            <th align="center">Service State</th>
            <th align="center">Service Zip</th>
            <th align="center">Billing Phone</th>
            <th align="center">Billing Address</th>
            <th align="center">Billing City</th>
            <th align="center">Billing State</th>
            <th align="center">Billing Zip</th>
            <th align="center">First Year Value</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        <?php //dd($data); ?>
        @foreach($data as $d)
        <tr>
            <td>{{ $d['points_account_id'] }} </td>
            <td>{{ $d['balance_overall'] }} </td>
            <td>{{ $d['invoice_id'] }} </td>
            <td>{{ $d['date_due'] }} </td>
            <td>{{ $d['date_scheduled'] }} </td>
            <td>{{ $d['date_completed'] }} </td>
            <td>{{ $d['time_begin'] }} </td>
            <td>{{ $d['time_end'] }} </td>
            <td>{{ $d['technician_id'] }} </td>
            <td>{{ $d['first_name'] }} </td>
            <td>{{ $d['last_name'] }} </td>
            <td>{{ $d['email'] }} </td>
            <td>{{ $d['status'] }} </td>
            <td>{{ $d['agreement_name'] }} </td>
            <td>{{ $d['service_type'] }} </td>
            <td>{{ $d['service_frequency'] }} </td>
            <td>{{ $d['initial_price'] }} </td>
            <td>{{ $d['amount_due'] }} </td>
            <td>{{ $d['street'] }} </td>
            <td>{{ $d['city'] }} </td>
            <td>{{ $d['suite'] }} </td>
            <td>{{ $d['postal_code'] }} </td>
            <td>{{ $d['street'] }} </td>
            <td>{{ $d['city'] }} </td>
            <td>{{ $d['suite'] }} </td>
            <td>{{ $d['postal_code'] }} </td>
            <td>{{ $d['first_year_contract_value'] }} </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
