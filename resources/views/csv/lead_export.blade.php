<table>
    <thead>
        <tr>
            <th align="center">First name</th>
            <th align="center">Last name</th>
            <th align="center">Date Added</th>
            <th align="center">Email address</th>
            <th align="center">Phone</th>
            <th align="center">Initial Job Note</th>
            <th align="center">Permanent Notes</th>
            <th align="center">Status</th>
            <th align="center">Salesperson</th>
            <th align="center">Street</th>
            <th align="center">Suite/Apt</th>
            <th align="center">City</th>
            <th align="center">State/Province</th>
            <th align="center">Zip</th>
            <th align="center">Map code</th>
            <th align="center">Service Type</th>
            <th align="center">Service Frequency</th>
            <th align="center">Contract Type</th>
            <th align="center">Normal Initial</th>
            <th align="center">Initial Discount</th>
            <th align="center">Initial Price</th>
            <th align="center">Recurring Price</th>
            <th align="center">Technician</th>
            <th align="center">Pests</th>
            <th align="center">Specialty pests</th>
            <th align="center">Tags</th>
            <th align="center">Note</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
            @foreach($data as $value)
            <tr>
                <td>{{ $value->first_name ?? '' }}</td>
                <td>{{ $value->last_name ?? '' }}</td>
                <td>{{ date('Y-m-d H:i:s', strtotime($value->date_created)) }}</td>
                <td>{{ $value->email ?? '' }}</td>
                <td>{{ $value->primary_phone ?? '' }}</td>
                <td>{{ $value->intial_job_note ?? '' }}</td>
                <td>{{ $value->intial_job_note ?? '' }}</td>
                <td>{{ $value->status ?? '' }}</td>
                <td>{{ $value->sales_people ?? '' }}</td>
                <td>{{ $value->street ?? '' }}</td>
                <td>{{ $value->suite ?? '' }}</td>
                <td>{{ $value->city ?? '' }}</td>
                <td>{{ $value->city ?? '' }}</td>
                <td>{{ $value->postal_code ?? '' }}</td>
                <td>{{ $value->map_code ?? '' }}</td>
                <td>{{ $value->service_type ?? '' }}</td>
                <td>{{ $value->service_frequency ?? '' }}</td>
                <td>{{ $value->contract_type ?? '' }}</td>
                <td>{{ $value->normal_initial ?? '' }}</td>
                <td>{{ $value->initial_discount ?? '' }}</td>
                <td>{{ $value->initial_price ?? '' }}</td>
                <td>{{ $value->recurring_price ?? '' }}</td>
                <td>{{ $value->technician ?? '' }}</td>
                <td>{{ $value->pests ?? '' }}</td>
                <td>{{ $value->special_pests ?? '' }}</td>
                <td>{{ $value->tags ?? '' }}</td>
                <td>{{ $value->permanent_note ?? '' }}</td>
            </tr>
            @endforeach
        @endif
    </tbody>
</table>
