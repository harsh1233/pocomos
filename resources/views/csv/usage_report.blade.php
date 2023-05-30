<table>
    <thead>
        <tr>
            <th align="center">Product Name</th>
            <th align="center">EPA Code</th>
            <th align="center">Amount Used</th>
            <th align="center">Unit</th>
            <th align="center">Applications</th>
        </tr>
    </thead>
    <?php 
        // dd($data);die;
    ?>
    <tbody>
        @if(isset($data))
            @foreach($data as $d)
            <tr>
                <td>{{ $d['product_name'] }} </td>
                <td>{{ $d['epa_code'] }} </td>
                <td>{{ $d['amount'] }} </td>
                <td>{{ $d['unit'] }} </td>
                <td>{{ $d['applications'] }} </td>
            </tr>
            @endforeach
        @endif
    </tbody>
</table>
