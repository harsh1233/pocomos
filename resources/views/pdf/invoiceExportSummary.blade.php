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

    <div>
        <div class="gray-shade">
            <table width="100%" cellspacing="2" cellpadding="2" style="font-size: 16px;">
                <tr>
                    <td width="50%" valign="top">
                        <div class="sender-address">
                            <div class="sender-wrapper">
                                <img class="logo"
                                    src="https://images.g2crowd.com/uploads/product/image/social_landscape/social_landscape_83b637f3809dae6b2228dfbf421f6da6/pocomos.png"
                                    title="Pocomos Logo" />
                                <div class="address">
                                    <div>1570 N Main,</div>
                                    <div>Street</div>
                                    <div>Spanish Fork,</div>
                                    <div>UT 84660</div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td width="40%" valign="top" class="text-center">
                        <table cellpadding="0" cellspacing="0" border="1">
                            <tbody>
                                <tr>
                                    <th>Date</th>
                                </tr>
                                <tr>
                                    <td style="text-align: center;"><?php echo date('Y-m-d'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <table cellpadding="2" cellspacing="0" border="1" style="margin-top: 35px;">
                            <tbody>
                                <tr>
                                    <th align="left">Bill To</th>
                                </tr>
                                <tr>
                                    <td style="font-size: 7pt;">
                                        @if (isset($serviceCustomer['billing_name']))
                                            {{ $serviceCustomer['billing_name'] }}
                                        @endif
                                        <br>
                                        423 South Lumina Avenue<br>
                                        Wrightsville Beach, NC 28480
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        <h3> Invoice Report Summary</h3>
        <div class="responsive">
            <table>
                <tbody>
                    <tr>
                        <td>
                            <table cellpadding="2" cellspacing="0" border="1">
                                <tbody>
                                    <tr>
                                        <th style="width: 7%">#</th>
                                        <th style="width: 8%">P.O. #</th>
                                        <th style="width: 8%">Date</th>
                                        <th style="width: 17%">Name</th>
                                        <th style="width: 22%">Adress</th>
                                        <th style="width: 16%">Service</th>
                                        <th style="width: 8%">Total</th>
                                        <th style="width: 7%">Paid</th>
                                        <th style="width: 7%">Due</th>
                                    </tr>
                                    <tr style="font-size: 8pt;height:16pt">
                                        <td style="border-right: 1px solid black">#14205239</td>
                                        <td style="border-right: 1px solid black"></td>
                                        <td style="border-right: 1px solid black">04/21/22</td>
                                        <td style="border-right: 1px solid black">Alex Trebek</td>
                                        <td style="border-right: 1px solid black">423 South Lumina Avenue
                                            Wrightsville Beach North
                                            Carolina, 28480
                                        </td>
                                        <td style="border-right: 1px solid black">Misc:</td>
                                        <td style="border-right: 1px solid black">$ 46.00 </td>
                                        <td style="border-right: 1px solid black">$ 0.00</td>
                                        <td style="border-right: 1px solid black">$ 46.00</td>
                                    </tr>
                                    <tr style="font-size: 8pt;height:16pt">
                                        <td style="border-right: 1px solid black">#14205239</td>
                                        <td style="border-right: 1px solid black"></td>
                                        <td style="border-right: 1px solid black">04/21/22</td>
                                        <td style="border-right: 1px solid black">Alex Trebek</td>
                                        <td style="border-right: 1px solid black">423 South Lumina Avenue
                                            Wrightsville Beach North
                                            Carolina, 28480
                                        </td>
                                        <td style="border-right: 1px solid black">Misc:</td>
                                        <td style="border-right: 1px solid black">$ 46.00 </td>
                                        <td style="border-right: 1px solid black">$ 0.00</td>
                                        <td style="border-right: 1px solid black">$ 46.00</td>
                                    </tr>
                                    <tr style="font-size: 8pt;height:16pt">
                                        <td style="border-right: 1px solid black">#14205239</td>
                                        <td style="border-right: 1px solid black"></td>
                                        <td style="border-right: 1px solid black">04/21/22</td>
                                        <td style="border-right: 1px solid black">Alex Trebek</td>
                                        <td style="border-right: 1px solid black">423 South Lumina Avenue
                                            Wrightsville Beach North
                                            Carolina, 28480
                                        </td>
                                        <td style="border-right: 1px solid black">Misc:</td>
                                        <td style="border-right: 1px solid black">$ 46.00 </td>
                                        <td style="border-right: 1px solid black">$ 0.00</td>
                                        <td style="border-right: 1px solid black">$ 46.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table cellpadding="2" cellspacing="0" border="1">
                                <tbody>
                                    <tr>
                                        <td style="align-content: flex-end !important;"><strong>Total Due:</strong> $
                                            135.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
@foreach ($invoice as $data)

    <body class="pocomos-body">

        <div class="gray-shade">
            <table width="100%" cellspacing="2" cellpadding="2" style="font-size: 16px;">
                <tr>
                    <td width="50%" valign="top">
                        <div class="sender-address">
                            <div class="sender-wrapper">
                                <img class="logo"
                                    src="https://images.g2crowd.com/uploads/product/image/social_landscape/social_landscape_83b637f3809dae6b2228dfbf421f6da6/pocomos.png"
                                    title="Pocomos Logo" />
                                <div class="address">
                                    <div>
                                        {{ $data['office']['coontact_address']['street'] ?? '' . ' ' . trim($data['office']['coontact_address']['suite'] ?? '') }},
                                        {{ $data['office']['coontact_address']['city'] ?? '' }},
                                        {{ $data['office']['coontact_address']['region']['code'] ?? '' }}
                                        {{ $data['office']['coontact_address']['postal_code'] ?? '' }}</div>
                                    @if ($data['invoiceConfig']['show_office_phone'])
                                        <div>{{ $data['office']['coontact_address']['primaryPhone']['number'] ?? '' }}
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </td>
                    <td width="50%" valign="top" class="text-center"> Invoice #{{ $data['invoice']['id'] }}

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Account #</th>
                                    <th>Invoice #</th>
                                    @if (!in_array($data['invoice']['status'], ['Not sent', 'Sent']))
                                        <th>Status</th>
                                    @endif
                                    @if ($data['invoiceConfig']['show_purchase_order_number'])
                                        <th>Purchase Order Number</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    @if ($data['serviceCustomer']['external_account_id'])
                                        <td>{{ $data['serviceCustomer']['external_account_id'] }}</td>
                                    @endif
                                    <td>{{ $data['invoice']['id'] }}</td>
                                    @if (!in_array($data['invoice']['status'], ['Not sent', 'Sent']))
                                        <td>{{ $data['invoice']['status'] }}</td>
                                    @endif
                                    @if ($data['invoiceConfig']['show_purchase_order_number'])
                                        <td>{{ $data['invoice']['contract']['purchase_order_number'] }}</td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice Bal.</th>
                                    @if ($data['invoiceConfig']['show_outstanding_balance'])
                                        <th>Outstanding Bal</th>
                                    @endif
                                    <th>Printed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $data['invoice']['balance'] }}</td>
                                    @if ($data['invoiceConfig']['show_outstanding_balance'])
                                        <td>{{ $data['outstanding'] }}</td>
                                    @endif
                                    <td><?php echo date('Y-m-d'); ?></td>
                                </tr>

                            </tbody>
                        </table>
                        <div style="text-align: center; font-size: .9em">
                            {{ $data['invoiceIntro'] }} </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="responsive">
            <table width="100%" cellspacing="2" cellpadding="2">
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
            </table>
        </div>
        <div class="hr"></div>
        <div class="responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Service Address</th>
                        <th>Service Date</th>
                        @if ($data['invoiceConfig']['show_technician'])
                            <th>Technician</th>
                        @endif
                        @if ($data['invoiceConfig']['show_time_in'])
                            <th>Time In</th>
                        @endif
                        @if ($data['invoiceConfig']['show_time_out'])
                            <th>Time Out</th>
                        @endif
                        @if ($data['invoiceConfig']['show_due_date'])
                            <th>Due On</th>
                        @endif
                        @if ($data['invoiceConfig']['show_technician_license'])
                            <th>Tech. License</th>
                        @endif
                        @if ($data['invoiceConfig']['show_business_license'])
                            <th>Bus. License</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="service-address">
                            945 McKenzie St, Outlook, SK S0L 2N0
                        </td>
                        @if (!is_null($data['job']))
                            @if (!($data['job']['status'] && config('constants.COMPLETE') === $data['job']['status']))
                                <td>{{ date('D, m/d/Y', strtotime($data['job']['date_scheduled'])) }}
                                    @if ($data['invoiceConfig']['show_time_in'])
                                        - {{ date('h:iA', strtotime($data['job']['route_detail']['time_begin'])) }}
                                    @endif
                                </td>
                            @else
                                <td>{{ date('D, m/d/Y', strtotime($data['job']['date_completed'])) }}
                                    @if ($data['invoiceConfig']['show_appointment_time'])
                                        - {{ date('h:iA', strtotime($data['job']['time_scheduled'])) }}
                                    @endif
                                </td>
                            @endif
                        @endif
                        @if ($data['invoiceConfig']['show_technician'] && $data['technician'])
                            <td>
                                {{ $data['technician']['user_detail']['user_details']['first_name'] }}
                                @if ($data['invoiceConfig']['show_technician_photo'] and $data['technician_photo_src'])
                                    <br /><br />
                                    <img width="100px" src="{{ $data['technician_photo_src'] }}"
                                        alt="{{ $data['technician']['user_detail']['user_details']['first_name'] }}"
                                        title="{{ technician }}" />
                                @endif
                            </td>
                        @endif
                        @if ($data['invoiceConfig']['show_time_in'])
                            <td>{{ date('h:iA', strtotime($data['job']['time_begin'])) }}</td>
                        @endif
                        @if ($data['invoiceConfig']['show_time_out'])
                            <td>{{ date('h:iA', strtotime($data['job']['time_end'])) }}</td>
                        @endif
                        @if ($data['invoiceConfig']['show_due_date'])
                            <td>{{ date('m/d/y', strtotime($data['invoice']['date_due'])) }}</td>
                        @endif
                        @if ($data['invoiceConfig']['show_technician_license'])
                            <td>{{ $data['invoiceConfig']['show_technician_license'] }}</td>
                        @endif
                        @if ($data['invoiceConfig']['show_business_license'])
                            <td>{{ $data['office']['license_number'] }}</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
        @if ($data['invoiceConfig']['show_custom_fields'])
            <div class="responsive">
                <table class="table">
                    <thead>
                        <?php /* ?> ?> ?> ?> ?> ?> ?> ?> ?> ?> ?> ?> ?>
                        @foreach ($data['customFields'] as $customField)
                            <tr>
                                <th>{{ $customField . customFieldConfiguration . label }}</th>
                                <td class="service-address">{{ customField . value }}</td>
                            </tr>
                        @endforeach
                        <?php */ ?>
                    </thead>
                </table>
            </div>
        @endif
        <div class="responsive">
            <table class="table">
                <thead>
                    <tr>
                        @if ($data['invoiceConfig']['show_map_code'])
                            <th>Map Code</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
        <div class="responsive">
            @if ($data['job'] and !empty($data['job']['weather']) or $data['invoiceConfig']['show_targeted_pests'])
                <table class="table">
                    <thead>
                        <tr>
                            @if ($data['invoiceConfig']['show_targeted_pests'])
                                <th>{{ $data['office']['name'] }}</th>
                            @endif
                            @if ($data['invoiceConfig']['show_last_service_date'] and $data['lastJob'])
                                <th>Last Service Date</th>
                            @endif
                            @if ($data['invoiceConfig']['show_marketing_type'])
                                <th>Marketing Type</th>
                            @endif
                            <th>Other Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @if ($data['invoiceConfig']['show_targeted_pests'])
                                <td>{{ '' }}</td>
                            @endif
                            @if ($data['invoiceConfig']['show_last_service_date'] and $data['lastJob'])
                                <td>{{ date('m/d/Y', strtotime($data['lastJob']['date_completed'])) }}</td>
                            @endif
                            @if ($data['invoiceConfig']['show_marketing_type'])
                                <td>{{ $data['invoice']['contract']['marketing_type']['name'] }}</td>
                            @endif
                            @if ($data['invoiceConfig']['show_weather'])
                            <td>{{ $data['job']['weather'] }}</td>@else<td>&nbsp;</td>
                            @endif
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
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product used</th>
                                        <th>Areas applied</th>
                                    </tr>
                                </thead>
                                <tbody class="border">
                                    <tr>
                                        <td><input type="checkbox" name="RoachBait" id="RoachBait" /><label
                                                for="RoachBait">Roach Bait</label></td>
                                        <td><input type="checkbox" name="Roofing" id="Roofing" /><label
                                                for="Roofing">Roofing</label></td>
                                    </tr>
                                </tbody>
                                <tbody class="border">
                                    <tr>
                                        <td><input type="checkbox" name="Roachspray" id="Roachspray" /><label
                                                for="Roachspray">Roach spray</label></td>
                                        <td><input type="checkbox" name="Roofing1" id="Roofing1" /><label
                                                for="Roofing1">Roofing1</label></td>
                                    </tr>
                                </tbody>
                                <tbody class="border">
                                    <tr>
                                        <td><input type="checkbox" name="product" id="product" /><label
                                                for="product">product</label></td>
                                        <td><input type="checkbox" name="Roofing2" id="Roofing2" /><label
                                                for="Roofing2">Roofing2</label></td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                        <td width="15%"></td>
                        <td width="45%">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Application type</th>
                                        <th>Amount</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Weed</td>
                                        <td>100</td>
                                        <td>255</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="50%">Amount</th>
                        <th width="50%" class="right">Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td width="50%">108.5</td>
                        <td width="50%" class="right">Points</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="50%">Services/Fees</th>
                        <th width="50%" class="right">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td width="50%">Initial Residential Solar Service</td>
                        <td width="50%" class="right">100.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="responsive">
            <table width="100%">
                <tr>
                    <td align="right" width="60%"></td>
                    <td align="right" width="40%">
                        <table width="100%" border="0" cellpadding="4" cellspacing="0">
                            <tr>
                                <td width="50%" align="left"><b>Account Credit</b></td>
                                <td width="50%" class="right">
                                    191.50
                                </td>
                            </tr>
                            <tr>
                                <td width="50%" align="left"><b>Subtotal</b></td>
                                <td width="50%" class="right">
                                    100.00
                                </td>
                            </tr>
                            <tr>
                                <td width="50%" align="left"><b>Sales Tax - 1</b></td>
                                <td width="50%" class="right">
                                    8.50
                                </td>
                            </tr>
                            <tr>
                                <td width="50%" align="left"><b>Paid</b></td>
                                <td width="50%" class="right">
                                    108.50
                                </td>
                            </tr>
                            <tr>
                                <td width="50%" align="left"><b>TOTAL</b></td>
                                <td width="50%" class="right">
                                    0.00
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" width="50%" class="center">
                                    <span>Pay Through Customer Portal</span>
                                    <p>Make checks payable to: Souffiane Pest Control
                                        500 Saskatchewan Ave Outlook, Saskatchewan, S0L 2N0
                                        Account Number: 000000017</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </body>
@endforeach

</html>
