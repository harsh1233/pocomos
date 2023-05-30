<table>
    <thead>
        <tr>
            <th align="center">Name</th>
            <th align="center">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php 

        // dd($data['data']);
        ?>
        @if(isset($data['data']))
            @foreach($data['data'] as $d)
                <tr>
                    <td> {{ $d->name }}</td>
                    <td> {{ $d->total }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
