<table>
    <thead>
        <tr>
            <th align="center">Recipient</th>
            <th align="center">Sent by</th>
            <th align="center">Customer/User</th>
            <th align="center">Type</th>
            <th align="center">Sent Date</th>
            <th align="center">Status</th>
            <th align="center">Status Date</th>
        </tr>
    </thead>
    <?php
        // dd($data);die;
    ?>
    <tbody>
        @if(isset($data))
            @foreach($data as $d)
            <tr>
                <!-- <td>{{ $d['recipient'] }} </td>
                <td>{{ $d['sending_user'] }} </td>
                <td>{{ $d['recipient_name'] }} </td>
                <td>{{ $d['email_type'] }} </td>
                <td>{{ $d['date_created'] }} </td>
                <td>{{ $d['status'] }} </td>
                <td>{{ $d['date_created'] }} </td> -->
            </tr>
            @endforeach
        @endif
    </tbody>
</table>
