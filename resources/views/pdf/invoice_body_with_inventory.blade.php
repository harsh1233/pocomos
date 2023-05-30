<style>
    th {
        font-weight: bold;
        text-align: center;
    }

    td.center {
        text-align: center;
    }

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
                    <div>
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
<table>
    <tr>
        <td>
            <table cellpadding="2" cellspacing="0" border="1" height="200">
                <tr>
                    <th style="width: 15%">Service Date</th>
                    <th style="width: 20%">Technician</th>
                    <th style="width: 10%">Time in</th>
                    <th style="width: 10%">Time out</th>
                    <th style="width: 45%">{{ $parameters['officeSettings']->company_details->name }}</th>
                </tr>
                <tr>
                    <td class="center">{{ date('m/d/y', strtotime($parameters['job']['date_scheduled'])) }}</td>
                    <?php
                    // dd(11);
                    ?>
                    <td class="center">{{ $parameters['job']->technician ? 
                        $parameters['job']->technician->user_detail->user_details->first_name.' '.$parameters['job']->technician->user_detail->user_details->last_name 
                        :  'Unassigned' }}</td>
                    <td class="center"></td>
                    <td class="center"></td>
                    <td class="center"></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<table>
    <tr>
        <td>
            <table cellpadding="2" cellspacing="0" border="1" height="200">
                <tr>
                    <th style="width: 20%">Service</th>
                    <th style="width: 27%">Product used</th>
                    <th style="width: 37%">Area applied</th>
                    <th style="width: 8%">Amount</th>
                    <th style="width: 8%">Price</th>
                </tr>
                <tr style="font-size: 8pt;height:16pt">
                    <td style="border-right: 1px solid black">{{ $parameters['service_type'] }}</td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                </tr>
                @foreach ($parameters['products'] as $product)
                <tr style="font-size: 8pt;height:16pt">
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"> [ &nbsp; ] - {{ $product->name }} <span style="font-size: 6pt;">EPA Code: {{ $product->epa_code }}</span></td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $product->unit }}</td>
                    <td style="border-right: 1px solid black"></td>
                </tr>
                @endforeach

                <!-- @foreach($parameters['products'] as $product)
                <tr>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                    <td style="border-right: 1px solid black"></td>
                </tr>
                @endforeach -->
            </table>
        </td>
    </tr>
</table>