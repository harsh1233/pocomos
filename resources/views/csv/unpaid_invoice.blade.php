<table>
    <thead>
        <tr>
            <th align="center">Account ID</th>
            <th align="center">Account Balance</th>
            <th align="center">Invoice ID</th>
            <th align="center">Invoice Due Date</th>
            <th align="center">Date Scheduled</th>
            <th align="center">Date Completed</th>
            <th align="center">Time In</th>
            <th align="center">Time Out</th>
            <th align="center">Technician</th>
            <th align="center">First Name</th>
            <th align="center">Last Name</th>
            <th align="center">Email</th>
            <th align="center">Account Status</th>
            <th align="center">Contract Type</th>
            <th align="center">Service Type</th>
            <th align="center">Service Frequency</th>
            <th align="center">Service Price</th>
            <th align="center">Unpaid Balance</th>
            <th align="center">Service Phone</th>
            <th align="center">Service Address</th>
            <th align="center">Service City</th>
            <th align="center">Service State</th>
            <th align="center">Service Zip</th>
            <th align="center">Billing Phone</th>
            <th align="center">Billing Address</th>
            <th align="center">Billing City</th>
            <th align="center">Billing State</th>
            <th align="center">Billing Zip</th>
            <th align="center">First Year Value</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        @foreach($data as $d)
        <tr>
            
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
