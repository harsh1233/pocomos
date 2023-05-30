<table>
    <thead>
        <tr>
            <th align="center">Profile Picture</th>
            <th align="center">First Name</th>
            <th align="center">Last Name</th>
            <th align="center">Legal Name</th>
            <th align="center">Date of Birth</th>
            <th align="center">Email</th>
            <th align="center">Username</th>
            <th align="center">Phone</th>
            <th align="center">Alt. Phone</th>
            <th align="center">Recruiting Office</th>
            <th align="center">Recruiter</th>
            <th align="center">Region</th>
            <th align="center">Current Address</th>
            <th align="center">Primary Address</th>
            <th align="center">Contract Name</th>
            <th align="center">Contract Start Date</th>
            <th align="center">Contract End Date</th>
            <th align="center">Contract Status</th>
            <th align="center">Agreement Creation</th>
            <th align="center">Note</th>
            <th align="center">Addendum</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        @foreach($data as $value)
        <tr>
            <td>{{ '' }} </td>
            <td>{{ $value->first_name ?? null }}</td>
            <td>{{ $value->last_name ?? null }}</td>
            <td>{{ $value->legal_name ?? null }}</td>
            <td>{{ $value->date_of_birth ?? null }} </td>
            <td>{{ $value->email ?? null }}</td>
            <td>{{ $value->desired_username ?? null }}</td>
            <td>{{ $value->primary_phone->number ?? null }}</td>
            <td>{{ $value->alt_phone->number ?? null }}</td>
            <td>{{ $value->office_detail->name ?? null }} </td>
            <td>{{ $value->recruiter_detail->user->user_details->full_name ?? null }}</td>
            <td>{{ $value->current_address->region->name ?? null }}</td>
            <td>{{ $value->current_address->street ?? null }} </td>
            <td>{{ $value->primary_address->street ?? null }} </td>
            <td>{{ $value->contract_detail->agreement->name ?? null }}</td>
            <td>{{ date('Y-m-d', strtotime($value->date_start)) ?? null }}</td>
            <td>{{ date('Y-m-d', strtotime($value->date_end)) ?? null }}</td>
            <td>{{ $value->status ?? null }} </td>
            <td>{{ date('Y-m-d', strtotime($value->contract_detail->agreement->date_created)) ?? null }}</td>
            <td>{{ $value->note_details[0]->note->summary ?? null }}</td>
            <td>{{ $value->contract_detail->addendum ?? null }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>