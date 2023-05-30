<table>
    <thead>
        <tr>
            <th align="center">Technician Name
</th>
            <th align="center">Total Value
</th>
            <th align="center">Total Count
</th>
            <th align="center">Initial Value
</th>
            <th align="center">Initial Count
</th>
            <th align="center">Regular Value
</th>
            <th align="center">Regular Count
</th>
            <th align="center">Re-service Value
</th>
            <th align="center">Re-service Count
</th>
            <th align="center">Pickup Service Value
</th>
            <th align="center">Pickup Service Count
</th>
            <th align="center">Complete Value
</th>
            <th align="center">Complete Count
</th>
            <th align="center">Cancelled Value
</th>
            <th align="center">Cancelled Count
</th>
            <th align="center">Re-scheduled Value
</th>
            <th align="center">Re-scheduled Count
</th>
            <th align="center">Pending Value
</th>
            <th align="center">Pending Count
</th>
            <th align="center">At-Fault Value
</th>
            <th align="center">At-Fault Count
</th>
            <th align="center">Total Commission
</th>
        </tr>
    </thead>
    <tbody>
        <?php //dd($data); ?> 
        @if(isset($data))
        @foreach($data as $d)
        <tr>
            <td>{{ $d->name }} </td>
            <td>{{ $d->Total_Value }} </td>
            <td>{{ $d->Total_Count }} </td>
            <td>{{ $d->Initial_Value }} </td>
            <td>{{ $d->Initial_Count }} </td>
            <td>{{ $d->Regular_Value }} </td>
            <td>{{ $d->Regular_Count }} </td>
            <td>{{ $d->Re_service_Value }} </td>
            <td>{{ $d->Re_service_Count }} </td>
            <td>{{ $d->Pickup_Service_Value }} </td>
            <td>{{ $d->Pickup_Service_Count }} </td>
            <td>{{ $d->Complete_Value }} </td>
            <td>{{ $d->Complete_Count }} </td>
            <td>{{ $d->Cancelled_Value }} </td>
            <td>{{ $d->Cancelled_Count }} </td>
            <td>{{ $d->Re_scheduled_Value }} </td>
            <td>{{ $d->Re_scheduled_Count }} </td>
            <td>{{ $d->Pending_Value }} </td>
            <td>{{ $d->Pending_Count }} </td>
            <td>{{ $d->At_Fault_Value }} </td>
            <td>{{ $d->At_Fault_Count }} </td>
            <td>{{ $d->Total_Commission }} </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
