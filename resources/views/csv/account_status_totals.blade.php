<table>
    <thead>
        <tr>
            <th align="center">Rank</th>
            <th align="center">Name</th>

            @foreach($data['sales_status'] as $q)
                    <td> {{ $q->name }}</td>
            @endforeach

            <th align="center">APAY</th>
            
        </tr>
    </thead>
    <tbody>
        <?php //dd($data['data']['data'][0]); 

            foreach($data['sales_status'] as $q){
                $status[] = 'stat_'.$q->id;
            }
        ?>

        @if(isset($data))
            @foreach($data['data']['data'] as $q)
                 <?php //dd($data); ?>

                <tr>
                    <td> {{ $q->rank }}</td>
                    <td> {{ $q->name }}</td>
                    @foreach($status as $name)
                        <td> {{ $q->$name }}</td>
                    @endforeach
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
