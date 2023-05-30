<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <title>Pocomos</title>
    <!--     <link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro' rel='stylesheet' type='text/css'>
 -->
    <style type="text/css">
        body.pocomos-body {
            padding: 10px;
            margin: 0;
        }

        .gray-shade .sender-address .address {
            font-size: 16px;
            text-align: center;
        }

        .address-adjustment {
            width: 4.5in;
            margin-left: 60px;
        }

        .estimate-line {
            width: 99%;
            border: 4px solid #F89406;
            margin-bottom: 5px;
        }

        .estimate-line-bottom {
            width: 99%;
            border: 4px solid #F89406;
            margin-bottom: 5px;
            margin-top: 30px;
        }

        .recipient-address-estimate {
            border-top: 1px solid #000;
            margin-bottom: 5px;
            padding-top: 10px;
            font-size: 16px;
        }

        .est-address-heading {
            font-size: 16px;
            font-weight: bold;
            padding-bottom: 5px;
        }

        .sender-address .logo {
            width: 100%;
            max-width: 400px;
            height: 60px;
            object-fit: cover;
        }

        .sender-address {
            padding: 10px;
            margin-top: 10px;
            text-align: left;
        }

        .estimate-extra-fields {}

        .estimate-title {
            font-size: 20px;
            font-weight: bold;
        }

        .est-line {
            border-bottom: 1px solid #000;
        }

        .estimate-total {
            border: 1px solid #000;
            padding-right: 10px;
        }

        .text-center {
            text-align: center;
        }

        .line-bottom {
            border-bottom: 2px solid #000;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }

        .line-bottom span {
            display: block;
        }

        thead tr {
            text-align: left;
            color: #000;
        }

        td.bg-gray {
            text-align: justify;
            background-color: #ddd;
            padding: 10px;
            margin-bottom: 8px
        }

        td b {
            padding-top: 5px;
            display: block;
        }

        .responsive {
            overflow: auto;
            width: 100%;
            margin-bottom: 15px
        }

        table {
            width: 100%;
            border-spacing: 0;
            vertical-align: top;
        }

        td {
            text-align: left;
            padding: 2px 4px;
            min-width: 50px;
            vertical-align: top;
        }

        th {
            border-bottom: 1px solid #ddd;
            padding: 2px 4px;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .center {
            padding-top: 20px;
            text-align: center;
        }

        table h4 {
            margin: 10px 0;
            padding-left: 10px;
        }

        td span {
            color: #147ec9;
        }

        .hr {
            border-bottom: 2px dotted #000;
            margin: 15px 0;
        }

        tbody.border td {
            border-bottom: 1px solid #ddd;
        }

    </style>
</head>

<body class="pocomos-body">

    <div class="gray-shade">
        <table width="100%" cellspacing="2" cellpadding="2" style="font-size: 16px;">
            <tbody>
                <tr>
                    <td width="50%" valign="top">
                        <div class="sender-address">
                            <div class="sender-wrapper">
                                <img class="logo"
                                    src="https://images.g2crowd.com/uploads/product/image/social_landscape/social_landscape_83b637f3809dae6b2228dfbf421f6da6/pocomos.png"
                                    title="Pocomos Logo">
                                <div class="address">
                                    <div>
                                        {{ $parameters['serviceCustomer']['coontact_address']['street'] . ' ' . trim($parameters['serviceCustomer']['coontact_address']['suite']) }},
                                        {{ $parameters['serviceCustomer']['coontact_address']['city'] }},
                                        @if ($parameters['serviceCustomer']['coontact_address']['region'])
                                            {{ $parameters['serviceCustomer']['coontact_address']['region']['code'] }}
                                        @endif
                                        {{ $parameters['serviceCustomer']['coontact_address']['postal_code'] }}
                                    </div>
                                    @if ($parameters['invoiceConfig']['show_office_phone'])
                                        <div>
                                            {{ $parameters['serviceCustomer']['coontact_address']['primaryPhone']['number'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td width="50%" valign="top" class="text-center">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Account #</th>
                                    <th>Invoice #</th>
                                    <th>Printed</th>

                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $parameters['serviceCustomer']['external_account_id'] }}</td>
                                    <td>{{ $parameters['invoice']['id'] }}</td>
                                    <td><?php echo date('Y-m-d'); ?></td>

                                </tr>
                            </tbody>
                        </table>
                        <div style="text-align: center; font-size: .9em">
                            {{ $parameters['invoiceIntro'] }} </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="responsive">
        <table width="100%" cellspacing="2" cellpadding="2">
            <tbody>
                <tr>
                    <td width="33%">
                        <div class="address">
                            <div>Attn: James O' Brien</div>
                            <div>945 McKenzie St</div>
                            <div>Outlook, SK S0L 2N0</div>
                            <div>(123) 123-1231</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="hr"></div>
    <div class="responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Service Address</th>
                    <th>Service Date</th>
                    @if ($parameters['invoiceConfig']['show_technician'])
                        <th>Technician</th>
                    @endif

                    @if ($parameters['invoiceConfig']['show_time_in'])
                        <th>Time In</th>
                    @endif

                    @if ($parameters['invoiceConfig']['show_time_out'])
                        <th>Time Out</th>
                    @endif


                    @if ($parameters['invoiceConfig']['show_technician_license'])
                        <th>Tech. License</th>
                    @endif

                    @if ($parameters['invoiceConfig']['show_business_license'])
                        <th>Bus. License</th>
                    @endif

                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="service-address">
                        945 McKenzie St, Outlook, SK S0L 2N0
                    </td>
                    @if (!is_null($parameters['job']))
                        @if (!($parameters['job']['status'] && config('constants.COMPLETE') === $parameters['job']['status']))
                            <td>{{ date('D, m/d/Y', strtotime($parameters['job']['date_scheduled'])) }}
                                @if ($parameters['invoiceConfig']['show_time_in'])
                                    - {{ date('h:iA', strtotime($parameters['job']['route_detail']['time_begin'])) }}
                                @endif
                            </td>
                        @else
                            <td>{{ date('D, m/d/Y', strtotime($parameters['job']['date_completed'])) }}
                                @if ($parameters['invoiceConfig']['show_appointment_time'])
                                    - {{ date('h:iA', strtotime($parameters['job']['time_scheduled'])) }}
                                @endif
                            </td>
                        @endif
                    @endif

                    @if ($parameters['invoiceConfig']['show_technician'])
                        <td>
                            {{ $parameters['technician']['user_detail']['user_details']['first_name'] }}
                            @if ($parameters['invoiceConfig']['show_technician_photo'] and $parameters['technician_photo_src'])
                                <br /><br />
                                <img width="100px" src="{{ $parameters['technician_photo_src'] }}"
                                    alt="{{ $parameters['technician']['user_detail']['user_details']['first_name'] }}"
                                    title="{{ technician }}" />
                            @endif
                        </td>
                    @endif

                    @if ($parameters['invoiceConfig']['show_time_in'])
                        <td>{{ date('h:iA', strtotime($parameters['job']['time_begin'])) }}</td>
                    @endif
                    @if ($parameters['invoiceConfig']['show_time_out'])
                        <td>{{ date('h:iA', strtotime($parameters['job']['time_end'])) }}</td>
                    @endif

                    @if ($parameters['invoiceConfig']['show_technician_license'])
                        <td>{{ $parameters['invoiceConfig']['show_technician_license'] }}</td>
                    @endif
                    @if ($parameters['invoiceConfig']['show_business_license'])
                        <td>{{ $parameters['office']['license_number'] }}</td>
                    @endif
                </tr>
            </tbody>
        </table>
    </div>

    <div class="responsive">
        @if ($parameters['job'] and !empty($parameters['job']['weather']) or $parameters['invoiceConfig']['show_targeted_pests'])
            <table class="table">
                <thead>
                    <tr>
                        @if ($parameters['invoiceConfig']['show_targeted_pests'])
                            <th>{{ $parameters['office']['name'] }}</th>
                        @endif
                        @if ($parameters['invoiceConfig']['show_targeted_pests'])
                            <th>Other Info</th>
                        @endif

                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @if ($parameters['invoiceConfig']['show_targeted_pests'])
                            <td>{{ '' }}</td>
                            @endif @if ($parameters['invoiceConfig']['show_weather'])
                            <td>{{ $parameters['job']['weather'] }}</td>@else<td>&nbsp;</td>
                            @endif
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    <div class="responsive">
        @if ($parameters['invoiceConfig']['show_custom_fields'])
            <table class="table">
                <thead>
                    <tr>
                        <th>Product used</th>
                        <th>Areas applied</th>
                        <th>Application type </th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Product used</td>
                        <td>Areas applied</td>
                        <td>Application type </td>
                        <td>Amount</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    <div class="responsive">
        <table class="table">
            <tbody>
                <tr>
                    <td width="40%">
                        @if ($parameters['job'] and ($parameters['invoiceConfig']['show_technician_note'] and $parameters['job']['technician_note'] or $parameters['job']['treatmentNote']))

                            <table class="table">
                                <thead>
                                    <tr>
                                        @if ($parameters['invoiceConfig']['show_technician_note'] and $parameters['job']['technician_note'])
                                            <th>Technician Note</th>
                                        @endif
                                        @if ($parameters['job']['treatmentNote'])
                                            <th>Treatment Note</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @if ($parameters['invoiceConfig']['show_technician_note'] and $parameters['job']['technician_note'])
                                            <td>{{ $parameters['job']['technician_note'] }}</td>
                                        @endif
                                        @if ($parameters['job']['treatmentNote'])
                                            <td>{{ $parameters['job']['treatmentNote'] }}</td>
                                        @endif
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                    </td>
                    <td width="15%"></td>
                    <td width="45%">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="responsive">
        <table class="table">
            <tbody>
                <tr>
                    <td width="40%">
                        @if ($parameters['job'] and $parameters['job']['signature_detail'])
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Accepted by</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td> <img class="signature"
                                                src="file://{{ $parameters['job']['signature_detail']['path'] }}"
                                                alt="Customer signature" title="Customer signature">
                                        </td>

                                    </tr>
                                </tbody>
                            </table>
                        @endif
                    </td>
                    <td width="15%"></td>
                    <td width="45%">

                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
