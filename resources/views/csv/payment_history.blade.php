<table>
    <thead>
        <tr>
            <th align="center">Invoice</th>
            <th align="center">Date</th>
            <th align="center">Network</th>
            <th align="center">Amount</th>
            <th align="center">Type</th>
            <th align="center">Status</th>
            <th align="center">Description</th>
            <th align="center">External ID</th>
            <th align="center">Payment Initiator</th>
            <th align="center">Card Alias</th>
            <th align="center">Multiple Invoices</th>
        </tr>
    </thead>
    <tbody>

        @if (isset($payment_history))

            @foreach ($payment_history as $value)
                <tr>
                    <td>{{ $value->invoice_id ?? null }}</td>
                    <td>{{ $value->date_created ?? null }}</td>
                    <td>{{ $value->network ?? null }}</td>
                    <td>{{ $value->amount ?? null }} </td>
                    <td>{{ $value->type ?? null }}</td>
                    <td>{{ $value->status ?? null }}</td>
                    <td>{{ $value->description ?? null }}</td>
                    <td>{{ $value->external_account_id ?? null }}</td>
                    <td>{{ $value->first_name ?? null }}</td>
                    <td>{{ $value->alias ?? null }}</td>
                    <td>{{ $value->alt_phone ?? null }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
