<table>
    <thead>
        <tr>
            <th align="center">Name</th>
            <th align="center">Phone</th>
            <th align="center">Email</th>
            <th align="center">Credit</th>
            <th align="center">Balance</th>
            <th align="center">Unpaid Invoices</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        <?php //dd($data); ?>
        @foreach($data as $d)
        <tr>
            <td>{{ $d->first_name.' '.$d->last_name }}</td>
            <td>{{ $d->phoneNumber }}</td>
            <td>{{ $d->email }}</td>
            <td>{{ $d->balance }}</td>
            <td>{{ $d->balance }}</td>
            <td>0</td>
            
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
