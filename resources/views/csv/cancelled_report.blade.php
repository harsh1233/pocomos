<table>
    <thead>
        <tr>
            <th align="center">Customer Name</th>
            <th align="center">Contract Address</th>
            <th align="center">Contract Type</th>
            <th align="center">Service Type</th>
            <th align="center">Status</th>
            <th align="center">Date</th>
            <th align="center">Recurring Price</th>
            <th align="center">Technician</th>
            <th align="center">Sales Representative</th>
            <th align="center">Sales Status</th>
            <th align="center">Cancel Reason</th>
            <th align="center">Last Note</th>
        </tr>
    </thead>
    <?php
    // dd($data['first_name']);
    ?>
    <tbody>
        @if(isset($data))
            @foreach($data as $d)
                <tr>
                    <td>{{ $d['first_name'] }}</td>
                    <td>{{ $d['contact_address'] }}</td>
                    <td>{{ $d['contract_type'] }}</td>
                    <td>{{ $d['service_type'] }}</td>
                    <td>{{ $d['status'] }}</td>
                    <td>{{ $d['date'] }}</td>
                    <td>{{ $d['recurring_price'] }}</td>
                    <td>{{ $d['technician'] }}</td>
                    <td>{{ $d['salesperson'] }}</td>
                    <td>{{ $d['sales_status'] }}</td>
                    <td>{{ $d['reason'] }}</td>
                    <td>{{ $d['last_note'] }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
