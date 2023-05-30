<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0" />
        <title></title>
        <?php /* ?>
        {% stylesheets package='assetic' filter='?yui_css, cssrewrite' output='css/pdf.css'
            'css/lib/bootstrap.css'
            'css/lib/font-awesome.css'
            'css/lib/font-awesome-5-1-1.css'
            'css/src/less/module/pdf.less'
        %}
        <link rel="stylesheet" href="{{ asset_url }}" />
        {% endstylesheets %}
        <?php */ ?>
        <style type="text/css">
        .address-adjustment{
            width: 4.5in;
            margin-left: 60px;
        }
        </style>
    </head>

    <body>
        <div class="container-fluid">
            <div class="row-fluid">
                <div class="span6">
                    <div class="sender-address">
                        <div class="sender-wrapper">
                            <img class="logo" src="
                            @if($parameters['office']['logo'])
                                {{ $parameters['office']['logo']['path'] }}
                            @else
                                {{ absolute_url(asset('img/pocomos_logo.png')) }}
                            @endif" 
                            alt="{{ $parameters['office']['name'] }}" title="{{ $parameters['office']['name'] }} Logo" />
                            <div class="address">
                                {{ ($parameters['office']['coontact_address']['street'].' '.trim($parameters['office']['coontact_address']['suite'])) }}, {{ $parameters['office']['coontact_address']['city'] }}, {{ $parameters['office']['coontact_address']['region']['code'] }} {{ $parameters['office']['coontact_address']['postal_code'] }}
                            </div>
                            @if($parameters['invoiceConfig']['show_office_phone'])
                                <div>
                                    <strong>{{ $parameters['office']['coontact_address']['primaryPhone']['number'] }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="recipient-address">
                        @if($parameters['invoiceConfig']['adjust_name_address_to_right']) <div class="address-adjustment">@endif
                        @set attn = '';
                        @if($parameters['invoiceConfig']['show_attn'])
                            @set attn = 'Attn: ';
                        @endif
                        @if($parameters['invoiceConfig']['enlarge_name_address_size_on_invoice'])<div style="font-size: 19px !important;">@endif
                            <?php /* ?>
                            {{ pdf.address( attn ~ billing_customer.billingName|default(billing_customer), billing_customer.billingAddress) }}
                            <?php */ ?>
                            @if($parameters['invoiceConfig']['show_all_phone_number_along_type'])                                         
                                @foreach($parameters['phoneNumbers'] as $value)
                                        <div class="recipient-all-phone">
                                        {{ $value }} {{ $value['phone']['type'] }} - {{ $value['phone']['alias'] }}
                                        </div>
                                @endforeach
                            @else
                                <?php /* ?>
                                @if($parameters['invoiceConfig']['show_phone_number'])
                                    {{ billing_customer.billingAddress.phone }}
                                @endif
                                <?php */ ?>
                            @endif
                        @if($parameters['invoiceConfig']['show_customer_emailaddress'])
                            <div class="recipient-all-phone">
                                {{ $parameters['serviceCustomer']['email'] }}
                            </div>
                        @endif
                        @if($parameters['invoiceConfig']['enlarge_name_address_size_on_invoice'])</div>@endif
                        @if($parameters['invoiceConfig']['adjust_name_address_to_right']) </div>@endif
                    </div>
                </div>

            </div>
            <hr class="detach">
        </div>

        Invoice #{{ $parameters['invoice']['id'] }}

        <table class="table centered">
            <thead>
            <tr>
                <th>Account #</th>
                <th>Invoice #</th>
                @if(!in_array($parameters['invoice']['status'], ['Not sent', 'Sent']))<th>Status</th>@endif
                @if($parameters['invoiceConfig']['show_purchase_order_number'])<th>Purchase Order Number</th>@endif
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $parameters['serviceCustomer']['external_account_id'] }}</td>
                <td>{{ $parameters['invoice']['id'] }}</td>
                @if(!in_array($parameters['invoice']['status'], ['Not sent', 'Sent']))<td>{{ $parameters['invoice']['status'] }}</td>@endif
                @if($parameters['invoiceConfig']['show_purchase_order_number'])<td class="center">{{ $parameters['invoice']['contract']['purchase_order_number'] }}</td>@endif
            </tr>
            </tbody>
        </table>


        <?php /* ?>
        {% macro address(name, addr, size) %}
        <div class="address">
            {{ name }}<br>
            {{ addr.street }} {{ addr.suite }}<br>
            {{ addr.city }}, {{ addr.region.code }} {{ addr.postalCode }}
        </div>
        {% endmacro %}
        {% macro pests(jobOrContract) %}{% spaceless %}
        {% set job = jobOrContract.dateScheduled|default(false) ? true : false %}
        @if(job and jobOrContract.pests|length %}
        {{ jobOrContract.pests|join(', ') }}
        {% elseif job %}
        {{ (jobOrContract.contract.pests|merge(jobOrContract.contract.specialtyPests))|join(', ') }}
        {% elseif jobOrContract %}
        {{ (jobOrContract.pests|merge(jobOrContract.specialtyPests))|join(', ') }}
        @endif
        {% endspaceless %}{% endmacro %}
        <?php */ ?>

        <table class="table">
            <thead>
            <tr>
                <th>Service Address</th>
                @if(!is_null($parameters['job']))<th>Service Date</th>@endif
                @if($parameters['invoiceConfig']['show_technician'])<th>Technician</th>@endif
                @if($parameters['invoiceConfig']['show_time_in'])<th>Time In</th>@endif
                @if($parameters['invoiceConfig']['show_time_out'])<th>Time Out</th>@endif
                @if($parameters['invoiceConfig']['show_due_date'])<th>Due On</th>@endif
                @if($parameters['invoiceConfig']['show_technician_license'])<th>Tech. License</th>@endif
                @if($parameters['invoiceConfig']['show_business_license'])<th>Bus. License</th>@endif
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="service-address">
                    <?php /* ?>
                    @if($parameters['serviceCustomer']['parent_detail']['customer_details'] and $parameters['invoiceConfig']['show_child_customer_name'])
                        {{ $parameters['serviceCustomer']['first_name'] }} <br/>
                    @else
                        {{ $parameters['serviceCustomer']['contact_address']['street'] }}
                    @endif
                    <?php */ ?>
                </td>
                @if(!is_null($parameters['job']))
                    @if (! ($parameters['job']['status'] && config('constants.COMPLETE') === $parameters['job']['status']) )
                        <td>{{ date('D, m/d/Y', strtotime($parameters['job']['date_scheduled'])) }}
                        @if( $parameters['invoiceConfig']['show_time_in'])
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
                @if($parameters['invoiceConfig']['show_technician'])
                    <td>
                        {{ $parameters['technician']['user_detail']['user_details']['first_name'] }}
                        @if($parameters['invoiceConfig']['show_technician_photo'] and $parameters['technician_photo_src'])
                            <br/><br/>
                            <img width="100px" src="{{ $parameters['technician_photo_src'] }}" alt="{{ $parameters['technician']['user_detail']['user_details']['first_name'] }}" title="{{ technician }}" />
                        @endif
                    </td>
                @endif
                @if($parameters['invoiceConfig']['show_time_in'])<td>{{ date('h:iA', strtotime($parameters['job']['time_begin'])) }}</td>@endif
                @if($parameters['invoiceConfig']['show_time_out'])<td>{{ date('h:iA', strtotime($parameters['job']['time_end'])) }}</td>@endif
                @if($parameters['invoiceConfig']['show_due_date'])<td>{{ date('m/d/y', strtotime($parameters['invoice']['date_due'])) }}</td>@endif

                <?php /*?>
                @if($parameters['job'] and $parameters['invoiceConfig']['show_technician_license'])
                    <td>{{ job.technician.licenseFor(job.contract.serviceType)|default(' ') }}</td>
                @endif
                <?php */?>
                @if($parameters['invoiceConfig']['show_business_license'])<td>{{ $parameters['office']['license_number'] }}</td>@endif
            </tr>
            </tbody>
        </table>

        @if($parameters['invoiceConfig']['show_custom_fields'])
        <table class="table">
            <tbody>
            <?php /* ?>
            @foreach($parameters['customFields'] as $customField)
            <tr>
                <th>{{ $customField.customFieldConfiguration.label }}</th>
                <td class="service-address">{{ customField.value }}</td>
            </tr>
            @endforeach
            <?php */ ?>
            </tbody>
        </table>
        @endif

        <table class="table">
            <thead>
                <tr>
                @if($parameters['invoiceConfig']['show_map_code'])
                    <th>Map Code</th>
                @endif
                </tr>
            </thead>
            <!-- <tbody>
                <tr>
                @if($parameters['invoiceConfig']['show_map_code'])
                    <td class="service-address">{{ $parameters['job']['contract']['map_code'] }}</td>
                @endif
                </tr>
            </tbody> -->
        </table>
        

        @if($parameters['job'] and !empty($parameters['job']['weather']) or $parameters['invoiceConfig']['show_targeted_pests'])
            <table class="table">
                <thead>
                <tr>
                    @if($parameters['invoiceConfig']['show_targeted_pests'])<th>{{ $parameters['office']['name'] }}</th>@endif
                    @if($parameters['invoiceConfig']['show_last_service_date'] and $parameters['lastJob'])<th>Last Service Date</th>@endif
                    @if($parameters['invoiceConfig']['show_marketing_type'])<th>Marketing Type</th>@endif
                    <th>Other Info</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <!-- pdf.pests(job)|default(' ') -->
                    @if($parameters['invoiceConfig']['show_targeted_pests'])<td>{{ '' }}</td>@endif
                    @if($parameters['invoiceConfig']['show_last_service_date'] and $parameters['lastJob'])<td>{{ date('m/d/Y' , strtotime($parameters['lastJob']['date_completed'])) }}</td>@endif
                    @if($parameters['invoiceConfig']['show_marketing_type'])<td>{{ $parameters['invoice']['contract']['marketing_type']['name'] }}</td>@endif
                    @if($parameters['invoiceConfig']['show_weather']) <td>{{ $parameters['job']['weather'] }}</td>@else<td>&nbsp;</td>@endif               
                </tr>
                </tbody>
            </table>
        @endif
        @set shownItems = [];
        @if($parameters['job'] and $parameters['invoiceConfig']['show_product_fields'])
            @if(! ($parameters['job']['status'] && config('constants.COMPLETE') === $parameters['job']['status']) )
                @if($parameters['products'])

                    <table class="table">
                        <thead>
                        <tr>
                            @if($parameters['invoiceConfig']['show_products_used'])<th colspan="5">Product used</th>@endif
                            @if($parameters['invoiceConfig']['show_dilution_rate'])<th colspan="">Dilution Rate</th>@endif
                            @if($parameters['invoiceConfig']['show_areas_applied'])<th colspan="5">Areas applied</th>@endif
                            @if($parameters['invoiceConfig']['show_application_type'])<th colspan="">Application type</th>@endif
                            @if($parameters['invoiceConfig']['show_amount_of_product'])<th colspan="">Amount</th>@endif
                            <th class="right" colspan="">Price</th>
                            @if($parameters['invoiceConfig']['show_application_rate'])<th colspan="">Application Rate</th>@endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($parameters['products'] as $product)
                            <tr>
                                <td colspan="5" style="line-height: 1.5em">
                                    <span style="font-size: 1.2em;">□</span>
                                    <span>{{ $product['name'] }}</span>
                                    @if($parameters['invoiceConfig']['showEpaCode'] and $product['epa_code'])
                                        @if($parameters['invoiceConfig']['epa_code_or_number'])
                                            <span class="epa-code">({{ $parameters['invoiceConfig']['epa_code_or_number'] }}: {{ $product['epa_code'] }})</span>
                                        @else
                                            <span class="epa-code">(EPA Code: {{ $product['epa_code'] }})</span>
                                        @endif
                                    @endif
                                </td>
                                @if($parameters['invoiceConfig']['show_dilution_rate'])
                                <td>{{ $product['dilution_rate'] }}</td>
                                @endif
                                <td colspan="5" style="line-height: 1.5em">
                                    @foreach($parameters['areas'] as $area)
                                        @if(loop.index < 7)
                                            <span style="font-size: 1.2em;">□</span>
                                            <span>{{ area.name }}</span>
                                            @if(loop.index == 3)
                                                <br/>
                                            @endif
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            @if($parameters['invoiceConfig']['show_application_rate'])<td>{{ $parameters['product']['application_rate'] }}</td>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                @endif
            @elseif ( count($parameters['job']['get_job_products']) )
                <table class="table">
                    <thead>
                    <tr>
                        @if($parameters['invoiceConfig']['show_products_used'])<th>Product used</th>@endif
                        @if($parameters['invoiceConfig']['show_dilution_rate'])<th colspan="">Dilution Rate</th>@endif
                        @if($parameters['invoiceConfig']['show_areas_applied'])<th>Areas applied</th>@endif
                        @if($parameters['invoiceConfig']['show_application_type'])<th> @if($parameters['invoiceConfig']['rename_application_type']) {{ $parameters['invoiceConfig']['application_type_text'] }} @else Application type @endif</th>@endif
                        @if($parameters['invoiceConfig']['show_amount_of_product'])<th>Amount</th>@endif
                        <th class="right">Price</th>
                        @if($parameters['invoiceConfig']['show_application_rate'])<th colspan="">Application Rate</th>@endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($parameters['job']['get_job_products'] as $appliedProduct)
                        <?php /* ?>
                        @if($appliedProduct->invoice_item
                            {% set shownItems = shownItems|merge([appliedProduct.invoiceItem]) %}
                        @endif
                        <?php */ ?>
                        <tr>
                            @if($parameters['invoiceConfig']['showProductsUsed'])
                                <td>
                                    {{ $appliedProduct->product }}
                                    @if($parameters['invoiceConfig']['show_epa_code'] and $appliedProduct->product->epa_code)

                                        @if($parameters['invoiceConfig']['epa_code_or_number'])
                                                <span class="epa-code">({{ $parameters['invoiceConfig']['epa_code_or_number'] }}: {{ $appliedProduct->product->epa_code }})</span>
                                        @else
                                                <span class="epa-code">(EPA Code: {{ $appliedProduct->product->epa_code }})</span>
                                        @endif

                                    @endif
                                </td>
                            @endif
                            @if($parameters['invoiceConfig']['showDilutionRate'])<td>{{ $appliedProduct['dilution_rate'] }}</td>@endif
                            @if($parameters['invoiceConfig']['showAreasApplied'])<td>{{ $appliedProduct['areas']|join(', ') }}</td>@endif
                            @if($parameters['invoiceConfig']['show_application_type'])<td>{{ $appliedProduct['service'] }}</td>@endif
                            @if($parameters['invoiceConfig']['showAmountOfProduct'])<td>{{ $appliedProduct['amount']+0 }}{{ $appliedProduct['product'].unit == 'unit' ? '' : $appliedProduct['product'].unit }}</td>@endif
                            <td class="right">@if($appliedProduct['invoiceItem']){{ ($appliedProduct['invoiceItem'].price|number_format(2)) }}@endif</td>
                            @if($parameters['invoiceConfig']['show_application_rate'])<td>{{ $appliedProduct['applicationRate'] }}</td>@endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        @if($parameters['invoiceConfig']['show_payment_method'] and count($parameters['invoice']->transactions_details) > 0)
            <table class="table">
                <thead>
                <tr>
                    <th>Amount</th>
                    <th class="right">Payment Method</th>
                </tr>
                @foreach($parameters['invoice']['transactions'] as $transaction)
                    <tr>
                        @if(transaction['network'] == 'Points')
                            <td>{{ transaction['amount']/100 }}</td>
                        @else
                            <td>{{ transaction['amount'] }}</td>
                        @endif
                        <td class="right">{{ transaction['network'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <table class="table">
            <thead>
            <tr>
                <th>Services/Fees</th>
                <th class="right">Price</th>
            </tr>
            </thead>
            <tbody>
            @set total = 0.00;
            <?php /* ?>
            @foreach(item in !in_array($parameters['invoice']['items'] if item, shownItems)
                {% set total = total + item.totalAmountDue %}
                <tr>
                    <td>{{ item.description }}</td>
                    <td class="right">{{ item.price|number_format(2) }}</td>
                </tr>
            @endforeach
            <?php */ ?>
            </tbody>
        </table>
        <div class="row-fluid" style="margin-top: 20px">
            <div class="span8">
                @if($parameters['job'] and (($parameters['invoiceConfig']['show_technician_note'] and $parameters['job']['technician_note']) or $parameters['job']['treatmentNote']))
                    <table class="table">
                        <thead>
                        <tr>
                            @if($parameters['invoiceConfig']['show_technician_note'] and $parameters['job']['technician_note'] )<th>Technician Note</th>@endif
                            @if($parameters['job']['treatmentNote'])<th>Treatment Note</th>@endif
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            @if($parameters['invoiceConfig']['show_technician_note'] and $parameters['job']['technician_note'])<td>{{ $parameters['job']['technician_note'] }}</td>@endif
                            @if($parameters['job']['treatmentNote'])<td>{{ $parameters['job']['treatmentNote'] }}</td>@endif
                        </tr>
                        </tbody>
                    </table>
                @endif
                @if($parameters['job'] and ($parameters['invoiceConfig']['show_job_notes'] and $parameters['job']['note']))
                    <table class="table" style="max-width: 50%">
                        <thead>
                        <tr>
                            <th>Job Note</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><div style="width: 550px;"> {{ $parameters['job']['note'] }} </div> </td>
                        </tr>
                        </tbody>
                    </table>
                @endif
                @if($parameters['job'] and $parameters['job']['signature_detail'])
                    <p class="pseudo-head">Accepted by</p>
                    <div class="pseudo-body">
                        <img class="signature" src="file://{{ $parameters['job']['signature_detail']['path'] }}" alt="Customer signature" title="Customer signature">
                    </div>
                @endif
                @if($parameters['invoiceConfig']['show_technician_signature'] and $parameters['technicianSignature'])
                    <p class="pseudo-head">Technician Signature</p>
                    <div class="pseudo-body">
                        <img class="signature" src="file://{{ $parameters['technicianSignature']['path'] }}" alt="Technician signature" title="Technician signature">
                    </div>
                @endif
            </div>

            <div class="span4">
                <table class="table no-border pricing-overview">
                    <tbody>
                    <tr>
                        <th>Account Credit</th>
                        <td>{{ number_format($parameters['billingCustomer']['state_details']['balance_credit'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Subtotal</th>
                        <td>{{ number_format($parameters['invoice']['amount_due'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Sales Tax @if($parameters['invoiceConfig']['show_tax_code']) - {{ $parameters['taxCode'] }}@endif</th>
                        <td>{{ number_format($parameters['invoice']['total_sales_tax_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Paid</th>
                        <td>{{ number_format(($parameters['invoice']['amount_due'] - $parameters['invoice']['balance']), 2) }}</td>
                    </tr>
                    <tr class="total">
                        <th>TOTAL</th>
                        <td>{{ number_format($parameters['invoice']['balance'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>
                        @if($parameters['portalLink'])
                            @if($parameters['invoiceConfig']['show_portal_or_quick_link'] == 1)
                                @if($parameters['office']['customer_portal_link'])
                                    <a href="{{ $parameters['office']['customer_portal_link'] }}">Pay Through Customer Portal</a>
                                @elseif($parameters['hash'])
                                    <a href="{{ url($parameters['hash'] ) }}">Pay Through Customer Portal</a>
                                @else
                                    <a href="{{ url('login') }}">Pay Through Customer Portal</a>
                                @endif
                            @endif
                            @if($parameters['invoiceConfig']['show_portal_or_quick_link'] == 2)
                                    <a href="{{ portalLink }}">Quick Pay Now</a>
                            @endif
                        @endif    
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div class="foot-note">
                    @if($parameters['invoice']['contract']['profile_details']['autopay_account'] and $parameters['invoiceConfig']['include_do_not_pay_in_invoice'] and $parameters['autoPayAccountExpired'] == false)
                        <p style="font-weight: bold; font-size: 120%; margin-bottom: 10px">AutoPay - Do Not Pay</p><br>
                    @else
                        Make checks payable to:<br>
                    @endif
                    <strong>{{ $parameters['office']['name'] }}</strong><br>
                    {{ $parameters['office']['coontact_address']['street'] }} {{ $parameters['office']['coontact_address']['suite'] }}<br>
                    {{ $parameters['office']['coontact_address']['city'] }}, {{ $parameters['office']['coontact_address']['region']['code'] }}, {{ $parameters['office']['coontact_address']['postal_code'] }}<br>
                    <strong>Account Number: {{ ($parameters['serviceCustomer']['external_account_id']) }}</strong>
                </div>
            </div>
        </div>
        @if($parameters['invoiceConfig']['show_attach_photos'])
            @if(!empty($parameters['job']))
                @if(!empty($parameters['job']->attachments))
                    <h3>Attached Photos</h3>
                    <table width="100%" cellpadding="2" cellspacing="2">
                        @foreach($parameters['job']->attachments as $file)
                        <tr>
                            <td width="50%">
                            <img src="file://{{ $file['path'] }}" class="attached-service-photos" title="{{ $file['filename'] }}" width="400px;">   
                            </td>
                            <td>&emsp;</td>
                            <td>&emsp;</td>
                            <td valign="top"><p>{{ $file['file_description'] }}</p></td>
                        </tr>
                        <tr>
                            <td colspan="4">&nbsp;</td>
                        </tr>
                        @endforeach
                    </table>
                @endif
            @endif
        @endif
    </body>
</html>