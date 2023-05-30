<table>
    <thead>
        <tr>
            <th align="center">First Name</th>
            <th align="center">Last Name</th>
            <th align="center">Phone No.</th>
            <th align="center">Address</th>
            <th align="center">Customer Status</th>
            <th align="center">Contract Creation Date</th>
            <th align="center">Account Start Date</th>
            <th align="center">Initial Date</th>
            <th align="center">Sales Status</th>
            <th align="center">Initial Pricing</th>
            <th align="center">Recurring Price</th>
            <th align="center">Contract Value</th>
            <th align="center">Balance</th>
            <th align="center">Credit Balance</th>
            <th align="center">Days Past Due</th>
            <th align="center">Card on File</th>
            <th align="center">Autopay</th>
            <th align="center">PL</th>
            <th align="center">Sales Rep</th>
            <th align="center">Branch Name</th>
            <th align="center">Ext. Account Number</th>
            <th align="center">Contract Name</th>
            <th align="center">Primary Service Type</th>
            <th align="center">Service Frequency</th>
            <th align="center">First Year Contract Value</th>
            <th align="center">Sales External Id</th>
            <th align="center">Marketing Type</th>
            
        </tr>
    </thead>
    <tbody>
        <?php //dd($data); ?>
        @if(isset($data))
            @foreach($data as $q)
                 <?php //dd($data); ?>

                <tr>
                    <td> {{ $q->customer_first_name }}</td>
                    <td> {{ $q->customer_last_name }}</td>
                    <td> {{ $q->customer_phone }}</td>
                    <td> {{ $q->customer_contact_address }}</td>
                    <td> {{ $q->customer_status }}</td>
                    <td> {{ $q->contract_date }}</td>
                    <td> {{ $q->account_sign_up_start_date }}</td>
                    <td> {{ $q->initial_date }}</td>
                    <td> {{ $q->sales_status }}</td>
                    <td> {{ $q->initial_price }}</td>
                    <td> {{ $q->recurring_price }}</td>
                    <td> {{ $q->original_contract_value }}</td>
                    <td> {{ $q->balance }}</td>
                    <td> {{ $q->balance_credit }}</td>
                    <td> {{ $q->days_past_due }}</td>
                    <td> {{ $q->card_on_file }}</td>
                    <td> {{ $q->autopay }}</td>
                    <td> {{ $q->pay_level }}</td>
                    <td> {{ $q->salesperson_name }}</td>
                    <td> {{ $q->branch_name }}</td>
                    <td> {{ $q->customer_external_account_id }}</td>
                    <td> {{ $q->contract_name }}</td>
                    <td> {{ $q->service_type }}</td>
                    <td> {{ $q->first_year_contract_value }}</td>
                    <td> {{ $q->customer_external_account_id }}</td>
                    <td> {{ $q->marketing_type }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
