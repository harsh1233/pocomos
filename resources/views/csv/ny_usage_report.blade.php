<table>
    <thead>
        <tr>
            <th align="center">EPA Code</th>
            <th align="center">Product Name</th>
            <th align="center">Amount Used</th>
            <th align="center">Unit</th>
            <th align="center">Date of Appl.</th>
            <th align="center">Technician</th>
            <th align="center">County Code</th>
            <th align="center">Address</th>
            <th align="center">Municipality</th>
            <th align="center">Zip Code</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
            @foreach($data as $d)
                <tr>
                    
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
