<table>
    <thead>
        <tr>
            <th align="center">Account ID</th>
            <th align="center">Payment Account ID</th>
            <th align="center">Name</th>
            <th align="center">Account Number</th>
            <th align="center">Routing Number</th>
            <th align="center">Expiry Month</th>
            <th align="center">Expiry Year</th>
            <th align="center">Billing Street</th>
            <th align="center">Billing City</th>
            <th align="center">Billing State</th>
            <th align="center">Billing Zip</th>
            <th align="center">Type</th>
            <th align="center">Account Token</th>

        </tr>
    </thead>
    <tbody>
        @if(isset($accounts_data))
        @foreach($accounts_data as $account)
        <tr>
            <td>{{ $account['external_id'] }} </td>
            <td>{{ $account['account_id'] }}</td>
            <td>{{ $account['name'] }}</td>
            <td>{{ $account['account_number'] }}</td>
            <td>{{ $account['ach_routing_number'] }} </td>
            <td>{{ $account['card_exp_month'] }}</td>
            <td>{{ $account['card_exp_year'] }}</td>
            <td>{{ $account['address'] }}</td>
            <td>{{ $account['city'] }} </td>
            <td>{{ $account['region'] }}</td>
            <td>{{ $account['postal_code'] }}</td>
            <td>{{ $account['type'] }}</td>
            <td>{{ $account['account_token'] }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
