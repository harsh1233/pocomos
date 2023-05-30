<table>
    <thead>
        <tr>
            <th align="center">Invoice Id</th>
            <th align="center">Name</th>
            <th align="center">Total Due</th>
            <th align="center">Balance</th>
            <th align="center">Date Due</th>
            <th align="center">Status</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
            @foreach($data as $d)
                <tr>
                    <td> {{ $d->id }}</td>    
                    <td> {{ $d->name }}</td>    
                    <td> {{ $d->amount_due }}</td>    
                    <td> {{ $d->balance }}</td>    
                    <td> {{ $d->date_due }}</td>    
                    <td> {{ $d->status }}</td>    
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
