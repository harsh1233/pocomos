<?php
    $maxServiceLineLength = 30;
    $maxProductLineLength = 45;
    $maxAreaAppliedLineLength = 62;
    $maxRows = 17;
    $actualHeight = 0;
?>

<style>
    th {
        font-weight: bold;
        text-align: center;
    }
​
    td.center {
        text-align: center;
    }
​
    td.small {
        font-size: 8pt;
    }
    table{
        width: 100%;
        margin-bottom: 20px;
    }
</style>
<table width="100%" cellspacing="2" cellpadding="2" style="font-size: 16px; margin-bottom: 20px;">
    <tr>
        <td width="70%" valign="top">
            <div class="sender-address">
                <div class="sender-wrapper">
                    <img class="logo" style="width: 100%;height: 90px;object-fit: cover;max-width: 380px;"
                        src="https://images.g2crowd.com/uploads/product/image/social_landscape/social_landscape_83b637f3809dae6b2228dfbf421f6da6/pocomos.png"
                        title="Pocomos Logo" />
                    <div class="address">
                        {{ $parameters['office']['coontact_address']['street']??'' . ' ' . trim($parameters['office']['coontact_address']['suite']??'') }},
                        {{ $parameters['office']['coontact_address']['city']??'' }},
                        {{ $parameters['office']['coontact_address']['region']['code']??'' }}
                        {{ $parameters['office']['coontact_address']['postal_code']??'' }}</div>
                        <div>
                            {{ $parameters['office']['coontact_address']['primaryPhone']['number']??'' }}
                        </div>
                    </div>
                </div>
                <div class="estimate-extra-fields">
                </div>
            </div>
        </td>
        <td style="width: 30%">
            <table width="100%" cellpadding="0" cellspacing="0" border="1">
                <tbody>
                    <tr>
                        <th>Date</th>
                    </tr>
                    <tr>
                        <td class="center" style="text-align: center;">{{ date('Y-m-d') }}</td>
                    </tr>
                </tbody>
            </table>
            <table width="100%" cellpadding="2" cellspacing="0" border="1" style="margin-top: 16px;">
                <tbody>
                    <tr>
                        <th style="text-align: left; padding-left: 15px;">Details:</th>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding-left: 15px;">
                            <?= $parameters['officeSettings']->company_details->name ?><br>
                            <?= $parameters['officeSettings']->company_details->coontact_address->street ?? '' ?>
                            <br>
                            <?= $parameters['officeSettings']->company_details->coontact_address->suite ?? '' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table  style="font-size: 16px;">
    <tr>
        <td>
            <table cellpadding="2" cellspacing="0" border="1" height="200">
                <tr>
                    <th style="width: 15%">Service Date</th>
                    <th style="width: 20%">Technician</th>
                    @if($parameters['officeSettings']->include_begin_end_in_invoice == 1)
                        <th style="width: 10%">Time in</th>
                        <th style="width: 10%">Time out</th>
                    @endif
                    <th style="width: 45%">{{ $parameters['officeSettings']->company_details->name }}</th>
                </tr>
                <tr>
                    <td class="center">{{ $parameters['job']->date_completed }}</td>
                    <td class="center">{{ isset($parameters['job']->technician->user_detail) ? 
                        $parameters['job']->technician->user_detail->user_details->first_name.' '.$parameters['job']->technician->user_detail->user_details->last_name : 
                        null}}</td>
                    @if($parameters['officeSettings']->include_begin_end_in_invoice == 1)
                        <td class="center">{{ $parameters['job'] ? ($parameters['job']['time_begin'] ? date('g:i A', strtotime($parameters['job']['time_begin'])) : 'n/a') : 'n/a' }} </td>
                        <td class="center">{{ $parameters['job'] ? ($parameters['job']['time_end'] ? date('g:i A', strtotime($parameters['job']['time_end'])) : 'n/a') : 'n/a' }}</td>
                    @endif

                    <td class="center">
                        @foreach ($parameters['pests'] as $pest)
                            {{ $pest->name }}
                        @endforeach
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<table  style="font-size: 16px;">
    <tr>
        <td>
            <table cellpadding="1" cellspacing="0" border="1" height="200">
                <tr>
                    <th style="width: 20%">Service</th>
                    <th style="width: 27%">Product used</th>
                    <th style="width: 37%">Area applied</th>
                    <th style="width: 8%">Amount</th>
                    <th style="width: 8%">Price</th>
                </tr>

                @foreach ($parameters['invoiceItems'] as $item)

                    @if($item)
                    <tr style="font-size: 8pt">
                        <td style="border-right: 1px solid black">{{ $item->description }}</td>
                        <td style="border-right: 1px solid black"></td>
                        <td style="border-right: 1px solid black"></td>
                        <td style="border-right: 1px solid black"></td>
                        <td style="border-right: 1px solid black; text-align: right;">{{ $item->price }}</td>
                    </tr>
                    @endif
                @endforeach

                <?php
                // dd(111);
                ?>

                @foreach ($parameters['jobProducts'] as $jp)
                <?php
                // dd($jp->areas[0]->area->name);
                ?>
                <tr style="font-size: 8pt">
                    <td style="border-right: 1px solid black">{{ $jp->service->name }}</td>
                    <td style="border-right: 1px solid black">{{ $jp->product->name }}</td>
                    <td style="border-right: 1px solid black">
                        @foreach ($jp->areas as $area)
                        {{ $area->area->name }}
                        @endforeach
                    </td>
                    <td style="border-right: 1px solid black">{{ $jp->amount }} {{ $jp->product->unit }}</td>
                    <td style="border-right: 1px solid black; text-align: right;">
                        @if($jp->invoice_item)
                            {{
                                $jp->invoice_item->price + ($jp->invoice_item->price * $jp->invoice_item->sales_tax)  
                            }}
                        @endif
                    </td>
                </tr>
                @endforeach

                <!-- {% for i in range(0, maxRows - actualHeight - 1) if actualHeight < maxRows %}
                <tr style="font-size: 8px">
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                </tr>
                {% endfor %} -->

            </table>
        </td>
    </tr>
</table>