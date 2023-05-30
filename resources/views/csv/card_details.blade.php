<table>
    <thead>
        <tr>
            <th align="center">Customer ID</th>
            <th align="center">Name</th>
            <th align="center">Billing Address</th>
            <th align="center">Account Type</th>
            <th align="center">Last Four</th>
            <th align="center">Expiry Month</th>
            <th align="center">Expiry Year</th>
        </tr>
    </thead>
    <tbody>
        @if (isset($data))
            <?php //dd($data);
            ?>
            @foreach ($data as $d)
                <tr>
                    <td>{{ $d['customer_id'] }}</td>
                    <td>{{ $d['first_name'] . ' ' . $d['last_name'] }}</td>
                    <td>{{ $d['street'] . ' ' . $d['suite'] }}</td>
                    <td>{{ $d['account_type'] }}</td>
                    <td>{{ $d['last_four'] }}</td>
                    <td>{{ $d['card_exp_month'] }}</td>
                    <td>{{ $d['card_exp_year'] }}</td>

                </tr>
            @endforeach
        @endif
    </tbody>
</table>
