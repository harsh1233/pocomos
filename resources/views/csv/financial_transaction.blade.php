<table>
    <thead>
        <tr>
            <th align="center">Cust. Id</th>
            <th align="center">Cust. Name</th>
            <th align="center">Trans. Date</th>
            <th align="center">Network</th>
            <th align="center">Type</th>
            <th align="center">Amount</th>
            <th align="center">Last four</th>
            <th align="center">Trans. Status</th>
            <th align="center">Initiator</th>
            <th align="center">Ref. Number</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        @foreach($data as $d)
        <tr>
            <td>{{ $d->customer_id }} </td>
            <td>{{ $d->customer_name }} </td>
            <td>{{ $d->transaction_date_created }} </td>
            <td>{{ $d->network }} </td>
            <td>{{ $d->type }} </td>
            <td>{{ $d->amount }} </td>
            <td>{{ $d->last_four }} </td>
            <td>{{ $d->transaction_status }} </td>
            <td>{{ $d->initiator }} </td>
            <td>{{ $d->result_external_id }} </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
