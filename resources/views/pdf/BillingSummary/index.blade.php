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
            <tr>
                <td width="50%" valign="top">
                    <div class="sender-address">
                        <div class="sender-wrapper">
                            <!-- <img class="logo"
                                src="
                            @if ($parameters['office']['logo']) {{ $parameters['office']['logo']['path'] }}
                            @else
                                {{ absolute_url(asset('img/pocomos_logo.png')) }} @endif"
                                alt="{{ $parameters['office']['name'] }}"
                                title="{{ $parameters['office']['name'] }} Logo" /> -->

                            <img class="logo" src="https://images.g2crowd.com/uploads/product/image/social_landscape/social_landscape_83b637f3809dae6b2228dfbf421f6da6/pocomos.png"
                                alt="{{ $parameters['office']['name'] }}"
                                title="{{ $parameters['office']['name'] }} Logo" />
                            <div class="address">
                                {{ $parameters['office']['coontact_address']['street'] . ' ' . trim($parameters['office']['coontact_address']['suite']) }},
                                {{ $parameters['office']['coontact_address']['city'] }},
                                {{ $parameters['office']['coontact_address']['region']['code'] }}
                                {{ $parameters['office']['coontact_address']['postal_code'] }}
                            </div>
                        </div>
                    </div>
                </td>
                <td width="50%" valign="top" class="text-center">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Printed</th>
                                <th>Summary Of</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo date('Y-m-d'); ?></td>
                                <td>Invoices</td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="text-align: center; font-size: .9em">
                        <?php echo $parameters['invoiceIntro'] ?> </div>
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
                    <th>Invoice #</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Company Name</th>
                    <th>Total</th>
                    <th>Balance</th>
                    <th>Check No.</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>
                @if ($parameters['invoices'])

                    @foreach ($parameters['invoices'] as $userTransaction)
                        <tr>
                            <td> {{ $userTransaction->id }}</td>
                            <td>{{ $userTransaction->date_due }}</td>
                            <td>{{ $userTransaction->status }}</td>
                            <td>Misc.</td>
                            <td> {{ $parameters['serviceCustomer']['coontact_address']['street'] . ' ' . trim($parameters['serviceCustomer']['coontact_address']['suite']) }},
                                {{ $parameters['serviceCustomer']['coontact_address']['city'] }},
                                @if ($parameters['serviceCustomer']['coontact_address']['region'])
                                    {{ $parameters['serviceCustomer']['coontact_address']['region']['code'] }}
                                @endif
                                {{ $parameters['serviceCustomer']['coontact_address']['postal_code'] }}
                            </td>
                            <td>{{ $parameters['serviceCustomer']['company_name'] }}</td>
                            <td>{{ $userTransaction->amount_due }}</td>
                            <td>{{ $userTransaction->balance }}</td>
                            <td>{{ $userTransaction->amount_due }}</td>
                            <td>{{ $userTransaction->job->date_completed ?? 'n/a' }} </td>
                        </tr>
                    @endforeach

                @endif
            </tbody>
        </table>
    </div>
    <div class="responsive">
        <table width="100%">
            <tr>
                <td align="right" width="60%"></td>
                <td align="right" width="40%">
                    <table width="100%" border="0" cellpadding="4" cellspacing="0">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="right">Outstanding</th>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="right">{{ $parameters['outstanding'] }}</td>
                            </tr>
                        </thead>
                        <tr>

                            <td width="50%" align="left"><b>Account Credit</b></td>
                            <td width="50%" class="right">
                                {{ $parameters['billingCustomer']->state_details->balance_credit }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" width="50%" class="center">
                                @if ($parameters['portalLink'])
                                    @if ($parameters['invoiceConfig']['show_portal_or_quick_link'] == 1)
                                        @if ($parameters['office']['customer_portal_link'])
                                            <a href="{{ $parameters['office']['customer_portal_link'] }}">Pay Through
                                                Customer Portal</a>
                                        @elseif($parameters['hash'])
                                            <a href="{{ url($parameters['hash']) }}">Pay Through Customer Portal</a>
                                        @else
                                            <a href="{{ url('login') }}">Pay Through Customer Portal</a>
                                        @endif
                                    @endif
                                    @if ($parameters['invoiceConfig']['show_portal_or_quick_link'] == 2)
                                        <a href="{{ portalLink }}">Quick Pay Now</a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    </table>

                    <div class="foot-note">
                        @if($parameters['contract']['profile_details']['autopay_account'] and $parameters['invoiceConfig']['include_do_not_pay_in_invoice'] and $parameters['autoPayAccountExpired'] == false)
                            <p style="font-weight: bold; font-size: 120%; margin-bottom: 10px">AutoPay - Do Not Pay</p><br>
                        @else
                            Make checks payable to:<br>
                        @endif
                        <strong>{{ $parameters['office']['name'] }}</strong><br>
                        {{ $parameters['office']['coontact_address']['street'] }} {{ $parameters['office']['coontact_address']['suite'] }}<br>
                        {{ $parameters['office']['coontact_address']['city'] }}, {{ $parameters['office']['coontact_address']['region']['code'] }}, {{ $parameters['office']['coontact_address']['postal_code'] }}<br>
                        <strong>Account Number: {{ ($parameters['serviceCustomer']['external_account_id']) }}</strong>
                </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
