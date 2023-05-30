<!-- Invoice heading -->
<style>
    th {
        font-weight: bold;
        text-align: center;
    }

    td.center {
        text-align: center;
    }
    table{
        margin-bottom: 15px;
        width: 100%;
    }
    th,td{
        font-size: medium;
    }
</style>
<table width="100%" cellspacing="2" cellpadding="2" style="font-size: 16px; margin-bottom: 20px;">
    <tr>
        <td width="70%" valign="top" style="text-align: left;">
            <div class="sender-address">
                <div class="sender-wrapper">
                    <img class="logo" style="width: 100%;height: 90px;object-fit: cover;max-width: 380px;"
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
        <td style="width: 25%">
            <table cellpadding="0" cellspacing="0" border="1">
                <tr>
                    <th>Date</th>
                    <th>Invoice #</th>
                </tr>
                <tr>
                    <td class="center">{{ date('m/d/y', strtotime($parameters['invoice']['date_created'])) }}</td>
                    <td class="center">{{ $parameters['invoice']['id'] }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table>
    <tr>
        <td style="width: 60%"></td>
        <td style="width: 40%">
            <table cellpadding="0" cellspacing="0" border="1">
                <tr>
                    <th style="font-size: 7pt;">Business License</th>
                    <th style="font-size: 7pt;">Technician License</th>
                </tr>
                <tr>
                    <td class="center">{{ $parameters['office']['license_number'] ?? 'n/a' }}</td>
                    <td class="center">
                        n/a
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Invoice introduction -->
<table>
    <tr>
        <!-- <td style="width: 40%; font-size: 8pt; text-align: left;">{{ $parameters['invoiceIntro'] }}</td> -->
        <td style="width: 5%"></td>
        <td style="width: 55%">
            <table cellpadding="2" cellspacing="0" border="1">
                <tr>
                    <th>Service Address</th>
                    <th>Bill To</th>
                </tr>
                <tr>
                    <td style="font-size: 7pt;">
                        {{ $parameters['pest_contract']['contract_details']['profile_details']['customer_details']['first_name'] ?? '' }}<br>{{ $parameters['pest_contract']['contract_details']['profile_details']['customer_details']['contact_address']['street'] ?? '' }}
                        {{ $parameters['pest_contract']['contract_details']['profile_details']['customer_details']['contact_address']['suite'] ?? '' }}<br>{{ $parameters['pest_contract']['contract_details']['profile_details']['customer_details']['contact_address']['city'] ?? '' }},
                        {{ $parameters['pest_contract']['contract_details']['profile_details']['customer_details']['contact_address']['region']['code'] ?? '' }}
                        {{ $parameters['job'] ? $parameters['job']['contract']['contract_details']['profile_details']['customer_details']['contact_address']['postal_code'] : '' }}
                    </td>
                    <td style="font-size: 7pt;">
                        {{ $parameters['responsibleCustomer']['billing_name'] ? $parameters['responsibleCustomer']['billing_name'] : $parameters['responsibleCustomer']['first_name'] }}<br>{{ $parameters['responsibleCustomer']['billing_address']['street'] ?? '' }}
                        {{ $parameters['responsibleCustomer']['billing_address']['suite'] ?? '' }}<br>{{ $parameters['responsibleCustomer']['billing_address']['city'] ?? '' }},
                        {{ $parameters['responsibleCustomer']['billing_address']['region']['code'] ?? '' }}
                        {{ $parameters['responsibleCustomer']['billing_address']['postalCode'] ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Cancelled contract -->
@if (config('constants.CANCELLED') === $parameters['invoice']['status'])
    <p style="font-size: 14pt; color: red; font-weight: bold">CANCELLED</p>
@endif

<!-- Invoice body -->
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

    td.center {
        text-align: center;
    }

    td.small {
        font-size: 8pt;
    }
</style>
<table>
    <tr>
        <td>
            <table cellpadding="2" cellspacing="0" border="1" height="200">
                <tr>
                    <th style="width: 15%">Service Date</th>
                    <th style="width: 20%">Technician</th>
                    @if ($parameters['officeSettings']['include_begin_end_in_invoice'])
                        <th style="width: 10%">Time in</th>
                        <th style="width: 10%">Time out</th>
                    @endif
                    <th style="width: 45%">{{ $parameters['officeSettings']['name'] }}</th>
                </tr>
                <tr>
                    <td class="center">
                        {{ $parameters['job'] ? date('m/d/y', strtotime($parameters['job']['date_completed'])) : 'N/A' }}
                    </td>
                    <td class="center">
                        {{ $parameters['job'] ? $parameters['job']['technician_detail']['user_detail']['user_details']['first_name'] : 'N/A' }}
                    </td>
                    @if ($parameters['officeSettings']['include_begin_end_in_invoice'])
                        <td class="center">
                            {{ $parameters['job'] ? ($parameters['job']['time_begin'] ? date('g:i A', strtotime($parameters['job']['time_begin'])) : 'N/A') : 'N/A' }}
                        </td>
                        <td class="center">
                            {{ $parameters['job'] ? ($parameters['job']['time_end'] ? date('g:i A', strtotime($parameters['job']['time_end'])) : 'N/A') : 'N/A' }}
                        </td>
                    @endif
                    <td class="center">
                        @foreach ($parameters['pests'] as $pest)
                            {{ $pest->id }}
                        @endforeach
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>

<!-- Invoice footer -->
<style>
    th {
        font-weight: normal;
        text-align: left;
    }

    td {
        font-weight: bold;
        text-align: right;
    }
</style>
<table>
    <tr>
        <td style="text-align: right;" valign="top">Accepted By: </td>
        <td style="width: 40%; text-align: left;" valign="top">Technician Note:
            <br> {{ $parameters['job'] ? $parameters['job']['technician_note'] : 'N/A' }} <br>
            @if (isset($parameters['job']))
                Plant and Material to be Treated:
                <br>{{ $parameters['job']['treatmentNote'] ?? 'N/A' }}
            @endif
        </td>

    </tr>
</table>

<!-- Company details -->
<h3>{{ $parameters['office']['name'] }} Billing Statement -
    {{ $parameters['office']['coontact_address']['primaryPhone']['number'] }}</h3>

<!-- Company head -->
<style>
    th {
        font-weight: bold;
        text-align: center;
    }

    td.center {
        text-align: center;
    }
</style>
<table>
    <tr>
        <td style="width: 87%"></td>
        <td style="width: 13%">
            <table cellpadding="0" cellspacing="0" border="1" style="margin: 0;">
                <tr>
                    <th>Invoice #</th>
                </tr>
                <tr>
                    <td class="center">{{ $parameters['invoice']['id'] }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Company info -->
<table>
    <tr>
        <td style="width: 55%">
            <table cellpadding="2" cellspacing="0" border="1">
                <tr>
                    <th>Bill To</th>
                </tr>
                <tr>
                    <td>{{ $parameters['responsibleCustomer']['billing_name'] ? $parameters['responsibleCustomer']['billing_name'] : $parameters['responsibleCustomer']['first_name'] }}<br>{{ $parameters['responsibleCustomer']['billing_address']['street'] ?? '' }}
                        {{ $parameters['responsibleCustomer']['billing_address']['suite'] ?? '' }}<br>{{ $parameters['responsibleCustomer']['billing_address']['city'] ?? '' }},
                        {{ $parameters['responsibleCustomer']['billing_address']['region']['code'] ?? '' }}
                        {{ $parameters['responsibleCustomer']['billing_address']['postal_code'] ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Company details -->
<style>
    th {
        font-weight: normal;
        text-align: center;
    }

    td {
        font-weight: bold;
        text-align: right;
    }

    td.details {
        font-weight: normal;
        font-size: 7pt;
        text-align: center;
    }
</style>
<h4 align="right">Billing Date: {{ date('m/d/y', strtotime($parameters['invoice']['date_created'])) }}</h4>
<table>
    <tr>
        <td style="width: 70%"></td>
        <td style="width: 30%">
            <table cellpadding="2" cellspacing="0" border="1">
                <tr>
                    <th style="width: 70%">Subtotal:</th>
                    <td style="width: 30%">
                        ${{ number_format($parameters['invoice']['amount_due'] - $parameters['invoice']['sales_tax'], 2) }}
                    </td>
                </tr>
                <tr>
                    <th style="width: 70%">Sales Tax:</th>
                    <td style="width: 30%">${{ number_format($parameters['invoice']['sales_tax'], 2) }}</td>
                </tr>
                <tr>
                    <th style="border-bottom: 2px solid black; border-right: 1px solid black">Paid: </th>
                    <td style="border-bottom: 2px solid black">
                        ${{ number_format($parameters['invoice']['amount_due'] - $parameters['invoice']['balance'], 2) }}
                    </td>
                </tr>
                @if ($parameters['invoiceSettings']['display_outstanding_on_invoice'])
                    <tr>
                        <th>Total Outstanding:@if ($parameters['invoice']['contract']['profile_details']['autopay'] and
                            $parameters['outstanding_balance'] <= 0 and
                            $parameters['invoiceSettings']['include_do_not_pay'])
                                -Auto Pay-
                            @endif
                        </th>
                        <td>${{ number_format($parameters['outstanding_balance'], 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <th>Total:</th>
                        <td>${{ number_format($parameters['invoice']['balance'], 2) }}</td>
                    </tr>
                @endif

                @if ($parameters['invoice']['contract']['profile_details']['points_account']['balance'] > 0)
                    <tr>
                        <th>Available Credit:</th>
                        <td>${{ number_format($parameters['invoice']['contract']['profile_details']['points_account']['balance'] / 100, 2) }}
                        </td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
<table>
    <tr>
        <td style="width: 70%"></td>
        <td class="details" style="width: 30%"><br></td>
    </tr>
    <tr>
        <td style="width: 70%"></td>
        <td class="details" style="width: 30%">
            Please write account number on check.<br>
            <b>Account Number: {{ $parameters['customer']['external_account_id'] }}</b><br>
            <br>
            Make checks payable to:<br>
            <b>{{ $parameters['office']['name'] }}</b><br>
            {{ $parameters['office']['coontact_address']['street'] }} @if ($parameters['office']['coontact_address']['suite'])
                # {{ $parameters['office']['coontact_address']['suite'] }}
            @endif <br>
            {{ $parameters['office']['coontact_address']['city'] }},
            {{ $parameters['office']['coontact_address']['region']['code'] }},
            {{ $parameters['office']['coontact_address']['postal_code'] }}

        </td>
    </tr>
</table>
