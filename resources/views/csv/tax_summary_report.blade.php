<table>
    <thead>
        <tr>
            <th align="center">Tax Code</th>
            <th align="center">Total Revenue</th>
            <th align="center">Tax Rate</th>
            <th align="center">Tax Payable</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data))
        @foreach($data as $d)
        <tr>
            <td>{{ $d->code }} </td>
            <td>{{ $d->revenue }} </td>
            <td>{{ $d->salesTax }} </td>
            <td>{{ $d->taxPayable }} </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
