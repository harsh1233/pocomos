<table>
    <thead>
        <tr>
            <th align="center">Group Name</th>
            <th align="center">Last Scan</th>
            <th align="center">User</th>
            <th align="center">Note</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        @foreach($data as $val)
        <tr>
            <td>{{ $val['group_name'] }} </td>
            <td>{{ $val['last_scan'] }}</td>
            <td>{{ $val['username'] }}</td>
            <td>{{ $val['note'] }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>