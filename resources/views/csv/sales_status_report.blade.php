<table>
    <thead>
        <tr>
            <th align="center">First Name</th>
            <th align="center">Last Name</th>
            <th align="center">Address</th>
            <th align="center">Signup Date</th>
            <th align="center">Initial Date</th>
            <th align="center">Initial Job Status</th>
            <th align="center">Status</th>
            <th align="center">Initial Pricing</th>
            <th align="center">Recurring Pricing</th>
            <th align="center">Contract</th>
            <th align="center">Original Contract</th>
            <th align="center">Autopay</th>
            <th align="center">PL</th>
            <th align="center">Sales Rep</th>
            <th align="center">Branch Name</th>
            <th align="center">Ext. Account Number</th>
            <th align="center">Contract Name</th>
            <th align="center">Primary Service type</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
            @foreach($data as $d)
                <tr>
                    <td> {{ $d->customer_first_name }}</td>
                    <td> {{ $d->customer_last_name }}</td>
                    <td> {{ $d->customer_contact_address }}</td>
                    <td> {{ $d->date_start }}</td>
                    <td> {{ $d->initial_date }}</td>
                    <td> {{ $d->job_status }}</td>
                    <td> {{ $d->sales_status }}</td>
                    <td> {{ $d->contract_value }}</td>
                    <td> {{ $d->recurring_price }}</td>
                    <td> {{ $d->contract_value }}</td>
                    <td> {{ $d->original_value }}</td>
                    <td> {{ $d->autopay }}</td>
                    <td> {{ $d->pay_level }}</td>
                    <td> {{ $d->salesperson_name }}</td>
                    <td> {{ $d->branch_name }}</td>
                    <td> {{ $d->customer_external_account_id }}</td>
                    <td> {{ $d->contract_id }}</td>
                    <td> {{ $d->service_type }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
