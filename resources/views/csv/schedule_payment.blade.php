<table>
    <thead>
        <tr>
            <th align="center">Customer ID</th>
            <th align="center">Customer Name</th>
            <th align="center">Payment Amount</th>
            <th align="center">Date Scheduled</th>
            <th align="center">Billing Address</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
            @foreach($data as $d)
                <tr>
                    <td> {{ $d->customer_id }}</td>    
                    <td> {{ $d->first_name }} {{ $d->last_name }}</td>    
                    <td> {{ $d->amount_in_cents/100 }}</td>    
                    <td> {{ $d->date_scheduled }}</td>    
                    <td> {{ $d->street.' '.$d->suite.' '.$d->city.' '.$d->postal_code }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
