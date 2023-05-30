<table>
    <thead>
        <tr>
            @if(isset($heading))
            @foreach($heading as $head)
                <th align="center">{{ $head }}</th>
            @endforeach
            @endif
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
            @foreach($data as $value)
                <tr>
                    @if(in_array('lead_id', $exported_columns))
                        <td>{{ $value->lead_id ?? '' }}</td>
                    @endif
                    @if(in_array('lead_name', $exported_columns))
                        <td>{{ $value->lead_name ?? '' }}</td>
                    @endif
                    @if(in_array('email', $exported_columns))
                        <td>{{ $value->email ?? '' }}</td>
                    @endif
                    @if(in_array('phone', $exported_columns))
                        <td>{{ $value->phone ?? '' }}</td>
                    @endif
                    @if(in_array('email', $exported_columns))
                        <td>{{ $value->email ?? '' }}</td>
                    @endif
                    @if(in_array('contact_address', $exported_columns))
                        <td>{{ $value->contact_address ?? '' }}</td>
                    @endif
                    @if(in_array('postal_code', $exported_columns))
                        <td>{{ $value->postal_code ?? '' }}</td>
                    @endif
                    @if(in_array('status', $exported_columns))
                        <td>{{ $value->status ?? '' }}</td>
                    @endif
                    @if(in_array('date_created', $exported_columns))
                        <td>{{ $value->date_created ?? '' }}</td>
                    @endif
                    @if(in_array('first_name', $exported_columns))
                        <td>{{ $value->first_name ?? '' }}</td>
                    @endif
                    @if(in_array('last_name', $exported_columns))
                        <td>{{ $value->last_name ?? '' }}</td>
                    @endif
                    @if(in_array('office_name', $exported_columns))
                        <td>{{ $value->office_name ?? '' }}</td>
                    @endif
                    @if(in_array('company_name', $exported_columns))
                        <td>{{ $value->company_name ?? '' }}</td>
                    @endif
                    @if(in_array('street', $exported_columns))
                        <td>{{ $value->street ?? '' }}</td>
                    @endif
                    @if(in_array('city', $exported_columns))
                        <td>{{ $value->city ?? '' }}</td>
                    @endif
                    @if(in_array('region_name', $exported_columns))
                        <td>{{ $value->region_name ?? '' }}</td>
                    @endif
                    @if(in_array('lead_status', $exported_columns))
                        <td>{{ $value->lead_status ?? '' }}</td>
                    @endif
                    @if(in_array('agreement_name', $exported_columns))
                        <td>{{ $value->agreement_name ?? '' }}</td>
                    @endif
                    @if(in_array('service_type', $exported_columns))
                        <td>{{ $value->service_type ?? '' }}</td>
                    @endif
                    @if(in_array('service_frequencies', $exported_columns))
                        <td>{{ $value->service_frequencies ?? '' }}</td>
                    @endif
                    @if(in_array('salesperson', $exported_columns))
                        <td>{{ $value->salesperson ?? '' }}</td>
                    @endif
                    @if(in_array('map_code', $exported_columns))
                        <td>{{ $value->map_code ?? '' }}</td>
                    @endif
                    @if(in_array('autopay', $exported_columns))
                        <td>{{ $value->autopay ?? '' ? 'Yes' : 'No' }}</td>
                    @endif
                    @if(in_array('initial_price', $exported_columns))
                        <td>{{ $value->initial_price ?? '' }}</td> 
                    @endif
                    @if(in_array('recurring_price', $exported_columns))
                        <td>{{ $value->recurring_price ?? '' }}</td>
                    @endif
                    @if(in_array('regular_initial_price', $exported_columns))
                        <td>{{ $value->regular_initial_price ?? '' }}</td>
                    @endif
                    @if(in_array('technician_name', $exported_columns))
                        <td>{{ $value->technician_name ?? '' }}</td>
                    @endif
                    @if(in_array('pests', $exported_columns))
                        <td>{{ $value->pests ?? '' }}</td>
                    @endif
                    @if(in_array('specialty_pests', $exported_columns))
                        <td>{{ $value->specialty_pests ?? '' }}</td>
                    @endif
                    @if(in_array('tags', $exported_columns))
                        <td>{{ $value->tags ?? '' }}</td>
                    @endif
                </tr>
            @endforeach
        @endif
    </tbody>
</table>