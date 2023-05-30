<!DOCTYPE html>
<html>

<head>
    <link href="http://netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.no-icons.min.css"
        rel="stylesheet">
    <style>
        .table th,
        .table td {
            border: 0;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <table class="table table-borderless table-condensed">
                <tbody>
                    <tr>
                        <td>{{ $parameters['office']['name'] ?? '' }}
                            <br>
                            {{ $parameters['office']['coontact_address']['street'] ?? '' . ' ' . trim($parameters['office']['coontact_address']['suite'] ?? '') }},
                            {{ $parameters['office']['coontact_address']['city'] ?? '' }},
                            {{ $parameters['office']['coontact_address']['region']['code'] ?? '' }}
                            {{ $parameters['office']['coontact_address']['postal_code'] ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 20px;">
                            {{ $parameters['serviceCustomer']['first_name'] }}
                            {{ $parameters['serviceCustomer']['last_name'] }}<br>
                            {{ $parameters['serviceCustomer']['coontact_address']['street'] ?? '' . ' ' . trim($parameters['serviceCustomer']['coontact_address']['suite'] ?? '') }},
                            {{ $parameters['serviceCustomer']['coontact_address']['city'] ?? '' }},
                            {{ $parameters['serviceCustomer']['coontact_address']['region']['code'] ?? '' }}
                            {{ $parameters['serviceCustomer']['coontact_address']['postal_code'] ?? '' }} </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="wrapper" style="page-break-before: always">
        <div class="container bottom-pad-lg">
            <div class="row">
                <div class="text-right">
                    {{ $parameters['bill_id'] ?? '' }}
                </div>
            </div>

            <table class="table table-borderless table-condensed bill-service-table">
                <thead>
                    <tr>
                        <th style="width: 55%"></th>
                        <th style="width: 15%">Service Amount</th>
                        <th style="width: 15%">Tax</th>
                        <th style="width: 15%">Amount Due</th>
                    </tr>
                </thead>
                <colgroup>
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <td colspan="4"><strong>Jobs</strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="indent-sm" style="
    padding-left: 10px;
">David Moreno</td>
                    </tr>
                    <tr>
                        <td class="indent-md" style="
    padding-left: 15px;
"><strong>2002-07-25 - Pending -
                                $2.00</strong></td>
                        <td>$2.00</td>
                        <td>%0.000</td>
                        <td>$2.00</td>
                    </tr>
                    <tr>
                        <td class="indent-lg" style="
    padding-left: 20px;
">Initial EWE Service</td>
                        <td>$2.00</td>
                        <td>%0.000</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="indent-sm" style="
    padding-left: 10px;
">Akela Pest Kent Garrett
                        </td>
                    </tr>
                    <tr>
                        <td class="indent-md" style="
    padding-left: 15px;
"><strong>2019-11-04 - Cancelled -
                                $500.00</strong></td>
                        <td>$500.00</td>
                        <td>%0.000</td>
                        <td>$500.00</td>
                    </tr>
                    <tr>
                        <td class="indent-lg" style="
    padding-left: 20px;
">Regular Subscription Service</td>
                        <td>$315.00</td>
                        <td>%0.000</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4"><strong>Invoices</strong></td>
                    </tr>
                    <tr>

                    </tr>
                    <tr>
                        <td colspan="4" class="indent-sm" style="
    padding-left: 10px;
">David Moreno</td>
                    </tr>
                    <tr>
                        <td class="indent-md" style="
    padding-left: 15px;
"><strong>2002-07-25 - Pending -
                                $2.00</strong></td>
                        <td>$2.00</td>
                        <td>%0.000</td>
                        <td>$2.00</td>
                    </tr>
                    <tr>
                        <td class="indent-lg" style="
    padding-left: 20px;
">Initial EWE Service</td>
                        <td>$2.00</td>
                        <td>%0.000</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="indent-md" style="
    padding-left: 15px;
"><strong>2002-07-25 - Pending -
                                $2.00</strong></td>
                        <td>$2.00</td>
                        <td>%0.000</td>
                        <td>$2.00</td>
                    </tr>
                    <tr>
                        <td class="indent-lg" style="
    padding-left: 20px;
">Initial EWE Service</td>
                        <td>$2.00</td>
                        <td>%0.000</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="indent-sm" style="
    padding-left: 10px;
">Akela Pest Kent Garrett
                        </td>
                    </tr>
                    <tr>
                        <td class="indent-md" style="
    padding-left: 15px;
"><strong>2019-11-04 - Cancelled -
                                $500.00</strong></td>
                        <td>$500.00</td>
                        <td>%0.000</td>
                        <td>$500.00</td>
                    </tr>
                    <tr>
                        <td class="indent-lg" style="
    padding-left: 20px;
">Regular Subscription Service</td>
                        <td>$315.00</td>
                        <td>%0.000</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="indent-md" style="
    padding-left: 15px;
"><strong>2019-11-04 - Cancelled -
                                $500.00</strong></td>
                        <td>$500.00</td>
                        <td>%0.000</td>
                        <td>$500.00</td>
                    </tr>
                    <tr>
                        <td class="indent-lg" style="
    padding-left: 20px;
">Regular Subscription Service</td>
                        <td>$315.00</td>
                        <td>%0.000</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="indent-md">
                            <strong>Total:</strong>
                        </td>
                        <td>$500.00</td>
                        <td>%0.000</td>
                        <td>$500.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="container footer">
            <div class="row bottom-pad-sm">
                <div class="span12">
                    Please remit payment to the following address:
                </div>
            </div>
            <div class="row bottom-pad-sm" style="
    margin-bottom: 15px;
">
                <div class="span12">Pocomos Software<br>1570 N Main St, Spanish Fork, UT 84660678678<br>(188)
                    888-8888<br>
                    info@pocomos.net,info@pocomos.net3</div>
            </div>
            <div class="row">
                <div class="span12">
                    <table class="table table-borderless table-condensed">
                        <tbody>
                            <tr>
                                <td><strong>Account# 484606 </strong></td>
                                <td colspan="2"><strong>Bill# 820</strong></td>
                                <td>Please return this portion with your payment</td>
                            </tr>
                            <tr>
                                <td>Akela Pest Kent Garrett</td>
                                <td>Service Amount</td>
                                <td>Tax</td>
                                <td>Amount Due</td>
                            </tr>
                            <tr>
                                <td rowspan="2">5295 Arlington Avenue, Riverside, CA 92504</td>
                                <td>$636.00</td>
                                <td>%0.000</td>
                                <td>$537.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


</body>

</html>
