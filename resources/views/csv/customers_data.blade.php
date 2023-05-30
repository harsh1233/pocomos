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
                    @if(in_array('name', $exported_columns))
                        <td>{{ $value->customer_name }}</td>
                    @endif
                    @if(in_array('office', $exported_columns))
                        <td>{{ $value->office_name }}</td>
                    @endif
                    @if(in_array('office_fax', $exported_columns))
                        <td>{{ $value->office_fax }}</td>
                    @endif
                    @if(in_array('email', $exported_columns))
                        <td>{{ $value->customer_email }}</td>
                    @endif
                    @if(in_array('company_name', $exported_columns))
                        <td>{{ $value->company_name }}</td>
                    @endif
                    @if(in_array('billing_name', $exported_columns))
                        <td>{{ $value->billing_name }}</td>
                    @endif
                    @if(in_array('secondary_emails', $exported_columns))
                        <td>{{ $value->secondary_emails }}</td>
                    @endif
                    @if(in_array('street', $exported_columns))
                        <td>{{ $value->street }}</td>
                    @endif
                    @if(in_array('city', $exported_columns))
                        <td>{{ $value->city }}</td>
                    @endif
                    @if(in_array('postal_code', $exported_columns))
                        <td>{{ $value->postal_code }}</td>
                    @endif
                    @if(in_array('address', $exported_columns))
                        <td>{{ $value->contact_address }}</td>
                    @endif
                    @if(in_array('phone', $exported_columns))
                        <td>{{ $value->phone }}</td>
                    @endif
                    @if(in_array('billing_street', $exported_columns))
                        <td>{{ $value->billing_street }}</td>
                    @endif
                    @if(in_array('billing_postal', $exported_columns))
                        <td>{{ $value->billing_postal }}</td>
                    @endif
                    @if(in_array('billing_city', $exported_columns))
                        <td>{{ $value->billing_city }}</td>
                    @endif
                    @if(in_array('sales_status', $exported_columns))
                        <td>{{ $value->sales_status }}</td>
                    @endif
                    @if(in_array('contract_start_date', $exported_columns))
                        <td>{{ $value->contract_start_date }}</td>
                    @endif
                    @if(in_array('salesperson', $exported_columns))
                        <td>{{ $value->salesperson }}</td>
                    @endif
                    @if(in_array('map_code', $exported_columns))
                        <td>{{ $value->map_code }}</td>
                    @endif
                    @if(in_array('service_type', $exported_columns))
                        <td>{{ $value->service_type }}</td>
                    @endif
                    @if(in_array('autopay', $exported_columns))
                        <td>{{ $value->autopay ? 'Yes' : 'No'  }}</td>
                    @endif
                    @if(in_array('service_frequency', $exported_columns))
                        <td>{{ $value->service_frequency }}</td>
                    @endif
                    @if(in_array('date_created', $exported_columns))
                        <td>{{ $value->date_created }}</td>
                    @endif
                    @if(in_array('initial_price', $exported_columns))
                        <td>{{ $value->initial_price }}</td> 
                    @endif
                    @if(in_array('recurring_price', $exported_columns))
                        <td>{{ $value->recurring_price }}</td>
                    @endif
                    @if(in_array('regular_initial_price', $exported_columns))
                        <td>{{ $value->regular_initial_price }}</td>
                    @endif
                    @if(in_array('last_service_date', $exported_columns))
                        <td>{{ $value->last_service_date }}</td>
                    @endif
                    @if(in_array('balance', $exported_columns))
                        <td>{{ $value->balance }}</td>
                    @endif
                    @if(in_array('first_name', $exported_columns))
                        <td>{{ $value->first_name }}</td>
                    @endif
                    @if(in_array('last_name', $exported_columns))
                        <td>{{ $value->last_name }}</td>
                    @endif
                    @if(in_array('account_type', $exported_columns))
                        <td>{{ $value->account_type }}</td>
                    @endif
                    @if(in_array('status', $exported_columns))
                        <td>{{ $value->status }}</td>
                    @endif
                    @if(in_array('next_service_date', $exported_columns))
                        <td>{{ $value->next_service_date }}</td>
                    @endif
                </tr>
            @endforeach
        @endif
    </tbody>
</table>