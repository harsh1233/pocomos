<?php 
// return 1111111;
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Route Summary</title>
        <style>
            body { background-color: white; color: black; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-size: 10pt; }
            .logo > img { width: 200px; max-width: 200px; }
            .logo { margin-bottom: 10px; }
            h1 { font-size: 14pt; }
            table.summary { width: 100%; border-collapse: collapse; }
            p { font-size: 12pt; }
            .summary td { vertical-align: top; padding: 5px; border-bottom: 1px solid black; }
        </style>
    </head>
    <body>
        <div class="logo">
        <img class="logo" src="https://images.g2crowd.com/uploads/product/image/social_landscape/social_landscape_83b637f3809dae6b2228dfbf421f6da6/pocomos.png"
                                title="Pocomos Logo" />
        </div>
        <h1>Route Confirmation</h1>
        <p>
            <strong>Date Scheduled:</strong> {{ $route['date_scheduled'] }}<br/>
            <strong>Technician:</strong> {{ isset($route['technician_detail']['user_detail']['user_details']) ? $route['technician_detail']['user_detail']['user_details']->first_name.' '.$route['technician_detail']['user_detail']['user_details']->last_name
                     : 'Unassigned'}}
</p>
        <table class="summary">
            <tbody>
                @foreach($slots as $slot)
            <!-- {% for slot in slots if slot.job %}
                {% set pestControlContract = slot.job.contract %} -->
                <tr>
                    <td style="width:12%">
                        @if($slot['anytime'] == 1)
                            Anytime
                        @else
                        {{ $slot['beginTime'].' - '.  $slot['endTime'] }}
                        @endif
                    </td>
                    <td style="width:15%">
                        {{ $slot['customer'] }}<br />
                        {{ $slot['street'] }} {{ $slot['suite'] }}<br />
                        {{ $slot['city'] }}, {{ $slot['region'] }} {{ $slot['postalCode'] }}
                    </td>
                    <td style="width:15%">
                        {{ $slot['phone'] }}
                    </td>
                    <td style="width:17%">
                        <strong>Inv. Amount:</strong> ${{ $slot['amountDue']|number_format(2) }}<br/>
                        <strong>Amount Due:</strong> ${{ $slot['invoice_balance']|number_format(2) }}<br/>
                        <strong>Balance:</strong> ${{ $slot['outstanding_balance']|number_format(2) }}
                    </td>
                    <td style="width:10%">
                        <strong>Sales Rep:</strong> {{ $slot['salesperson'] }}<br/>
                        <strong>Status:</strong> {{ $slot['jobStatus'] }}<br/>
                        <strong>Service Type:</strong> {{ $slot['serviceType'] }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </body>
</html>
