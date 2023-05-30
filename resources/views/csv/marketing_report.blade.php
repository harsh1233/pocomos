<table>
    <thead>
        <tr>
            <th align="center">Marketing Type</th>
            <th align="center">Sales</th>
            <th align="center">% Sales</th>
            <th align="center">Avg Initial</th>
            <th align="center">Avg Recurring</th>
            <th align="center">Avg Contract Value</th>
            <th align="center">Value</th>
            <th align="center">% of Value</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        <?php //dd($data); ?>
        @foreach($data['results'] as $d)
        <tr>
            <td>{{ $d->name }}</td>
            <td>{{ $d->sales }}</td>
            <td>{{ $d->perc_sale }}%</td>
            <td>{{ $d->initial_price }}</td>
            <td>{{ $d->recurring_price }}</td>
            <td>{{ $d->avg_contract_value }}</td>
            <td>{{ $d->value }}</td>
            <td>{{ $d->perc_value }}%</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
