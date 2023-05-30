<table>
    <thead>
        <tr>
            <th align="center">Customer Name</th>
            <th align="center">Customer Address</th>
            <th align="center">Invoice #</th>
            <th align="center">Invoice Due Date</th>
            <th align="center">Initial Service Date</th>
            <th align="center">Contract Creation Date</th>
            <th align="center">Payment Date</th>
            <th align="center">Payment Method</th>
            <th align="center">Payment Amount</th>
            <th align="center">Pre Tax Amount</th>
            <th align="center">Tax Rate</th>
            <th align="center">Payment Status</th>
            <th align="center">Reference Number</th>
            <th align="center">Account Id</th>
            <th align="center">Service type</th>
            <th align="center">First Year Contract Value</th>
            <th align="center">Autopay</th>
            <th align="center">Job Type</th>
            <th align="center">Agreement Name</th>
            <th align="center">Contract Start Date</th>
        </tr>
    </thead>
    <tbody>
    <?php
            // dd($data);
            ?>
        @if(isset($data))
            <?php
            // dd($data);
            ?>
            @foreach($data as $d)
                <tr>
                    <td> {{ $d->name }}</td>    
                    <td> {{ $d->address }}</td>    
                    <td> {{ $d->id }}</td>    
                    <td> {{ $d->dateDue }}</td>    
                    <td> {{ $d->initialServiceDate }}</td>    
                    <td> {{ $d->contractCreationDate }}</td>    
                    <td> {{ $d->paymentDate }}</td>    
                    <td> {{ $d->paymentType }}</td>    
                    <td> {{ $d->paymentAmount }}</td>    
                    <td> {{ $d->preTaxAmount }}</td>    
                    <td> {{ $d->taxRate }}</td>    
                    <td> {{ $d->paymentStatus }}</td>    
                    <td> {{ $d->refNumber }}</td>    
                    <td> {{ $d->custAcctId }}</td>    
                    <td> {{ $d->service_type_name }}</td>    
                    <td> {{ $d->first_year_contract_value }}</td>    
                    <td> {{ $d->autopay }}</td>    
                    <td> {{ $d->jobtype }}</td>    
                    <td> {{ $d->agreement_name }}</td>    
                    <td> {{ $d->initialdate }}</td>    
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
