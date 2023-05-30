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

        thead tr {
            background-color: #F89406;
            color: #fff;
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

        table h4 {
            margin: 10px 0;
            padding-left: 10px;
        }

    </style>
</head>

<body class="pocomos-body">
    <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div class="estimate-line"></div>
            </td>
        </tr>
    </table>
    <div class="gray-shade">
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
                <td width="30%" valign="top" class="text-center">
                    <div class="line-bottom">
                        <b>Work Estimate</b>
                        <span>Date</span>
                    </div>
                    <div class="line-bottom">
                        <span><?php echo date('Y-m-d'); ?></span>
                        <span>Estimate #</span>
                    </div>
                    <div class="line-bottom">
                        <span><?php echo $estimate['id']; ?></span>
                        @if (isset($estimate['po_number']))
                            <span>Purchase Order #</span>
                        @endif
                    </div>
                    <div>
                        @if (isset($estimate['po_number']))
                            <span><?php echo $estimate['po_number']; ?></span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="responsive">
        <table width="100%" cellspacing="2" cellpadding="2">
            <tr>
                <td width="33%">
                    <span class="est-address-heading">Shipping Address</span>
                    <div class="recipient-address-estimate">
                        <div class="address">
                            <div>Ignore This</div>
                            <div>324</div>
                            <div>asd, AL 88998</div>
                            <div>(123) 123-1231</div>
                            <div>thunter.1st@gmail.com</div>
                        </div>
                    </div>
                </td>
                <td width="33%"></td>
                <td width="33%">
                    <span class="est-address-heading">Billing Address</span>
                    <div class="recipient-address-estimate">
                        <div>Ignore This</div>
                        <div>324</div>
                        <div>asd, AL 88998</div>
                        <div>(123) 123-1231</div>
                        <div>thunter.1st@gmail.com</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="responsive">
        <table border="0" width="100%" cellpadding="4" cellspacing="0" class="table table-striped">
            <thead>
                <tr>
                    <td width="25%"><b>Items</b></td>
                    <td width="30%"><b>Description</b></td>
                    <td width="15%" align="center"><b>Cost</b></td>
                    <td width="5%" align="center"><b>Qty</b></td>
                    <td width="10%" align="center"><b>Tax</b></td>
                    <td width="15%" align="center"><b>Total</b></td>
                </tr>
            </thead>
            <tbody>
                @if (count($products))

                    @foreach ($products as $product)
                        <tr>
                            <td><?= $product['name']['name'] ?></td>
                            <td><?= $product['name']['description'] ?></td>
                            <td align="center"><?= $product['cost'] ?></td>
                            <td align="center"><?= $product['quantity'] ?></td>
                            <td align="right">
                                <?= $product['tax'] * 100 . ' %' ?>
                            </td>
                            <td align="right"><?= $product['amount'] ?></td>
                        </tr>
                    @endforeach

                @endif

            </tbody>
        </table>
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td width="40%">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td><b>Terms </b></td>
                    </tr>
                    <tr>
                        <td class="bg-gray"><?= $estimate['terms'] ?></td>
                    </tr>
                    <tr>
                        <td><b>Notes</b></td>
                    </tr>
                    <tr>
                        <td class="bg-gray"><?= $estimate['note'] ?></td>
                    </tr>
                </table>
            </td>
            <td width="50%" valign="bottom">
                <table width="100%" cellpadding="4" cellspacing="4" border="0">
                    <tr>
                        <td align="right"><b>SubTotal</b></td>
                        <td align="right"><?= $estimate['subtotal'] ?></td>
                    </tr>
                    <tr>
                        <td align="right"><b>Discount</b></td>
                        <td align="right"><?= $estimate['discount'] ?></td>
                    </tr>

                    <tr>
                        <td align="right" width="80%"><b>Subtotal Less Discount</b></td>
                        <td align="right"><?= $estimate['subtotal'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right"><b>Tax Rate</b></td>
                        <td align="right"> <?= $product['tax'] * 100 . ' %' ?></td>
                    </tr>
                    <tr>
                        <td align="right"><b>Total Tax</b></td>
                        <td align="right"><?= ($product['subtotal'] * ($product['tax'] * 100)) / 100 . " $" ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table width="100%">
        <tr>
            <td align="right" width="40%"><b></b></td>
            <td align="right" width="60%">
                <table width="100%" border="0" cellpadding="4" cellspacing="0">
                    <tr>
                        <td align="right"><b>Estimate Total </b></td>
                        <td align="right">
                            <div class="estimate-total">
                                <h4> <?= $estimate['total'] ?></h4>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div class="estimate-line-bottom">
    </div>
</body>

</html>
