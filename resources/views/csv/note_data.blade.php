<table>
    <thead>
        <tr>
            <th align="center">Account ID</th>
            <th align="center">Name</th>
            <th align="center">Address</th>
            <th align="center">Phone Number</th>
            <th align="center">Note</th>
            <th align="center">Note Time</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($note_data))
        @foreach($note_data as $note)
        <tr>
            <td>{{ $note['ext_id'] }} </td>
            <td>{{ $note['name'] }}</td>
            <td>{{ $note['address'] }}</td>
            <td>{{ $note['phoneNumb'] }}</td>
            <td>{{ $note['note'] }}</td>
            <td>{{ $note['note_time'] }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
