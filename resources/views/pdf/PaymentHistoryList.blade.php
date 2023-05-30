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

        .gray-shade {
            background-color: #ddd;
            margin-bottom: 10px;
            padding: 5px;
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
        }

        table {
            width: 100%;
            word-break: break-all;
        }

        table h4 {
            margin: 10px 0;
            padding-left: 10px;
        }

    </style>
</head>

<body class="pocomos-body">
    <div>
        <table width="100%" cellspacing="2" cellpadding="2" style="font-size: 16px;">
            <tr>
                <td width="70%" valign="top">
                    <div class="sender-address">
                        <div class="sender-wrapper">
                            <img class="logo"
                                src="https://images.g2crowd.com/uploads/product/image/social_landscape/social_landscape_83b637f3809dae6b2228dfbf421f6da6/pocomos.png"
                                title="Pocomos Logo" />
                            <div class="address">
                                <div>1570 N Main St,</div>
                                <div>Spanish Fork,</div>
                                <div>UT 84660678678 </div>
                                <div>(09) -099985584741</div>
                            </div>
                        </div>
                        <div class="estimate-extra-fields">

                        </div>
                    </div>
                </td>
                <td style="width: 30%">
                    <table cellpadding="0" cellspacing="0" border="1">
                        <tbody>
                            <tr>
                                <th>Date</th>
                            </tr>
                            <tr>
                                <td class="center" style="text-align: center;">{{ date('Y-m-d') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <table cellpadding="2" cellspacing="0" border="1" style="margin-top: 16px;">
                        <tbody>
                            <tr>
                                <th style="text-align: left; padding-left: 15px;">Details:</th>
                            </tr>
                            <tr>
                                <td style="text-align: left; padding-left: 15px;"><?= $office->name ?><br><?= $office->street ?>
                                    Drive<br><?= $office->suite ?></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <div class="responsive">
        <div class="responsive">
            <table class="table align-middle" border="1">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>External Id</th>
                        <th>Payment Initiator</th>
                        <th>Card Alias</th>
                        <th>Multiple Invoices</th>
                    </tr>


                </thead>
                <tbody>

                    @if (count($userTransactions))

                        @foreach ($userTransactions as $userTransaction)
                            <tr>
                                <td>
                                    <?= $userTransaction->invoice_id ?>
                                </td>
                                <td><?= $userTransaction->date_created ?></td>
                                <td><?= $userTransaction->network ?></td>
                                <td><?= $userTransaction->amount ?></td>
                                <td><?= $userTransaction->type ?></td>
                                <td><?= $userTransaction->status ?></td>
                                <td>
                                    <?= $userTransaction->description ?>
                                </td>
                                <td>
                                    {{ isset($userTransaction->external_account_id) ? $userTransaction->external_account_id : 'n/a' }}
                                </td>
                                <td>
                                    <?php
                                    if($userTransaction->invoice_id){
                                        if($userTransaction->first_name && $userTransaction->last_name){
                                            echo $userTransaction->first_name.' '.$userTransaction->last_name;
                                        }else{
                                            echo 'Autopay';
                                        }
                                    }else{
                                        echo $userTransaction->first_name.' '.$userTransaction->last_name;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?= $userTransaction->alias ?>
                                </td>
                                <td></td>
                            </tr>
                        @endforeach

                    @endif

                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
