<table>
    <thead>
        <tr>
            <th align="center">Name</th>
            <th align="center">Sold Day</th>
            <th align="center">Serviced Day</th>
            <th align="center">Sold Week</th>
            <th align="center">Serviced Week</th>
            <th align="center">Sold Month</th>
            <th align="center">Serviced Month</th>
            <th align="center">Sold Year</th>
            <th align="center">Serviced Year</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        <?php //dd($data); ?>
        @foreach($data as $d)
        <tr>
            <td>{{ $d['name'] }} </td>
            <td>{{ $d['Day']['sold'] }} </td>
            <td>{{ $d['Day']['serviced'] }} </td>
            <td>{{ $d['Week']['sold'] }} </td>
            <td>{{ $d['Week']['serviced'] }} </td>
            <td>{{ $d['Month']['sold'] }} </td>
            <td>{{ $d['Month']['serviced'] }} </td>
            <td>{{ $d['Year']['sold'] }} </td>
            <td>{{ $d['Year']['serviced'] }} </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
