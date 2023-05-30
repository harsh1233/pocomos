<table>
    <thead>
        <tr>
            <th align="center">Account ID</th>
            <th align="center">Customer Name</th>
            <th align="center">Contract ID</th>
            <th align="center">Agreement</th>
            <th align="center">Status</th>
            <th align="center">Service Frequency</th>
            <th align="center">Billing Frequency</th>
            <th align="center">Initial Price</th>
            <th align="center">Recurring Price</th>
            <th align="center">Regular Intial Price</th>
            <th align="center">Initial Discount</th>
            <th align="center">Pref. Week of the Month</th>
            <th align="center">Pref. Day of the Week</th>
            <th align="center">Preferred time</th>
            <th align="center">Map Code</th>
            <th align="center">Service Type</th>
            <th align="center">County</th>
            <th align="center">Preferred Technician</th>
            <th align="center">Salesperson</th>
            <th align="center">Auto Renew</th>
            <th align="center">Marketing Type</th>
            <th align="center">Sales Status</th>
            <th align="center">Sales Status Modified</th>
            <th align="center">Contract Start Date</th>
            <th align="center">Sales Tax</th>
            <th align="center">Custom Contract Fields</th>
            <th align="center">Initial Service Date</th>
            <th align="center">Last Service Date</th>
            <th align="center">Next Service Date</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($contract_data))
        @foreach($contract_data as $contract)
        <tr>
            <td>{{ $contract['Customer ID'] }} </td>
            <td>{{ $contract['Customer Name'] }}</td>
            <td>{{ $contract['Contract ID'] }}</td>
            <td>{{ $contract['agreement'] }}</td>
            <td>{{ $contract['status'] }}</td>
            <td>{{ $contract['Service Frequency'] }} </td>
            <td>{{ $contract['Billing Frequency'] }}</td>
            <td>{{ $contract['Initial Price'] }}</td>
            <td>{{ $contract['Recurring Price'] }}</td>
            <td>{{ $contract['Regular Intial Price'] }} </td>
            <td>{{ $contract['Initial Discount'] }}</td>
            <td>{{ $contract['Pref. Week of the Month'] }}</td>
            <td>{{ $contract['Pref. Day of the Week'] }}</td>
            <td>{{ $contract['Preferred time'] }}</td>
            <td>{{ $contract['Map Code'] }} </td>
            <td>{{ $contract['Service Type'] }}</td>
            <td>{{ $contract['County'] }}</td>
            <td>{{ $contract['Preferred Technician'] }}</td>
            <td>{{ $contract['Salesperson'] }} </td>
            <td>{{ $contract['Auto Renew'] }}</td>
            <td>{{ $contract['Marketing Type'] }}</td>
            <td>{{ $contract['Sales Status'] }}</td>
            <td>{{ $contract['Sales Status Modified'] }}</td>
            <td>{{ $contract['Contract Start Date'] }}</td>
            <td>{{ $contract['Sales Tax'] }}</td>
            <td>{{ $contract['Custom Contract Fields'] }} </td>
            <td>{{ $contract['Initial Service Date'] }}</td>
            <td>{{ $contract['Last Service Date'] }}</td>
            <td>{{ $contract['Next Service Date'] }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
