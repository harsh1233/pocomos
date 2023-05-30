<table>
    <thead>
        <tr>
            <th align="center">Rank</th>
            <th align="center">Sales Rep</th>
            <th align="center">Yesterday Sales</th>
            <th align="center">Sales WTD</th>
            <th align="center">Sales MTD</th>
            <th align="center">Sales YTD</th>
            <th align="center">Services MTD</th>
            <th align="center">Services YTD</th>
            <th align="center">APAY %</th>
            <th align="center">Serviced %</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
            @foreach($data as $d)
                <tr>
                    <td> {{ $d->rank }}</td>    
                    <td> {{ $d->name }}</td>    
                    <td> {{ $d->yesterday_sales }}</td>    
                    <td> {{ $d->week_sales }}</td>    
                    <td> {{ $d->month_sales }}</td>    
                    <td> {{ $d->year_sales }}</td>    
                    <td> {{ $d->month_services }}</td>    
                    <td> {{ $d->year_services }}</td>    
                    <td> {{ $d->autopay_ratio }}</td>    
                    <td> {{ $d->service_ratio }}</td>    
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
