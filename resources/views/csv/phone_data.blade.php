<table>
    <thead>
        <tr>
            <th align="center">Account ID</th>
            <th align="center">Phone Alias</th>
            <th align="center">Phone type</th>
            <th align="center">Phone Number</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($phone_data))
        @foreach($phone_data as $phone)
        <?php $phone = (array)$phone; ?>
        <tr>
            <td>{{ $phone['id'] }} </td>
            <td>{{ $phone['alias'] }}</td>
            <td>{{ $phone['type'] }}</td>
            <td>{{ $phone['number'] }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
