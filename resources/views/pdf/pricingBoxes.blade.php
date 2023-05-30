<?php

$regularInitialPrice = $pricing_overview['regularInitialPrice'];
$initialDiscount     = $pricing_overview['initialDiscount'];
$salesTax            = $pricing_overview['salesTax'];
$initialPrice        = $pricing_overview['initialPrice'];
$recurringDiscount   = $pricing_overview['recurringDiscount'];
$recurringPrice      = $pricing_overview['recurringPrice'];
$handleDiscountTypes = $pricing_overview['handleDiscountTypes'];

// discountAmount -- start
$discountAmount = $amountCalculated = 0;
foreach ($handleDiscountTypes as $discountType) {
    if($discountType->discount){
        if($discountType->discount->value_type === "percent"){
            $price = $discountType->amount/100;
            $amountCalculated += $recurringPrice * $price;
        }else{
            $amountCalculated += $discountType->amount;
        }
    }
}
$discountAmount += $amountCalculated;
// discountAmount -- end

// variable value store
$totalPostDiscount                  = $initialPrice - $recurringDiscount;
$monthlyTotalPostDiscount           = $totalPostDiscount + ($totalPostDiscount * $salesTax);
$totalRecurringSalesTax             = ($recurringPrice - $discountAmount) * $salesTax;
$totalRecurringPriceTotal           = $recurringPrice + $totalRecurringSalesTax;
$monthlyTotalInitialTax             = ($recurringPrice - $initialDiscount) * $salesTax;
$monthlyTotalPostDiscountNew        = $totalPostDiscount + ($totalPostDiscount * $salesTax);
$totalInitialTax                    = $initialPrice * $salesTax;
$totalInitialPrice                  = $initialPrice + ( $initialPrice * $salesTax );
$dueAtSignUpSalesTax                = ($initialPrice - $recurringDiscount) * $salesTax;
$dueAtSignUpTotal                   = ($initialPrice - $recurringDiscount) + $dueAtSignUpSalesTax;
$twoPaymentInitialServiceSalesTax   = ($initialPrice - $recurringDiscount) * $salesTax;
$twoPaymentInitialServiceTotal      = ($initialPrice - $recurringDiscount) + $twoPaymentInitialServiceSalesTax;
$twoPaymentRecurringServiceSalesTax = ($recurringPrice - $discountAmount) * $salesTax;
$twoPaymentRecurringServiceTotal    = ($recurringPrice - $discountAmount) + $twoPaymentRecurringServiceSalesTax;
$totalSalesTaxInstallments          = ($initialPrice - $initialDiscount) * $salesTax;
$totalPriceInstallments             = ($initialPrice - $initialDiscount) + $totalSalesTaxInstallments;

$noInstallment = 1;
if(isset($pricing_overview['numberOfPayments'])){
    $noInstallment = $pricing_overview['numberOfPayments'];
}
// $noInstallment = $handleDiscountTypes[0]->contract->number_of_payments; 

$perInstallmentPerPrice             = $initialPrice / $noInstallment;
$perDiscountInstallments            = $initialDiscount / $noInstallment ;
$installmentPerServiceSalesTax      = $perDiscountInstallments * $salesTax;
$serviceTax                         = (($initialPrice / $noInstallment) - $perDiscountInstallments) * $salesTax;
$installmentFinalPriceTotal         = ($perInstallmentPerPrice - $perDiscountInstallments) + $serviceTax;

if($pricing_overview['frequency'] == 'Per service'){
?>
<table cellpadding="2" cellspacing="0" border="0" height="200" width="200">
    <tr>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%" colspan="2">Initial Service Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Regular Initial Price</td>
                        <td style="text-align: right;width: 40%">${{ number_format($regularInitialPrice, 2) }}</td>
                    </tr>
                    <?php
                if($initialDiscount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Discount</td>
                        <td style="text-align: right;width: 40%">${{ number_format($initialDiscount, 2) }}</td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax ({{ number_format($salesTax * 100, 2) }}%)
                        </td>
                        <td style="text-align: right; width: 40%">${{ number_format($initialPrice * $salesTax, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Initial Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($monthlyTotalPostDiscount, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
        <td style="width: 80%"></td>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%" colspan="2">Recurring Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Regular Recurring Price</td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($recurringPrice, 2) }}
                        </td>
                    </tr>
                    <?php
                if($discountAmount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Recurring Discount</td>
                        <td style="width: 40%;text-align: right;">${{ number_format($discountAmount, 2) }}</td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax{{ number_format($salesTax * 100, 2) }}%
                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($totalRecurringSalesTax, 2) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Recurring Price</th>
                        <th style="text-align: right; width: 40%">${{ number_format($totalRecurringPriceTotal, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>
<?php
}elseif($pricing_overview['frequency'] == 'Monthly'){
?>
<table cellpadding="2" cellspacing="0" border="0" height="200" width="200">
    <tr>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%">Monthly Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Regular Price</td>
                        <td style="text-align: right; width: 40%">${{ number_format($regularInitialPrice, 2) }}</td>
                    </tr>
                    <?php
                if($initialDiscount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Discount(-)</td>
                        <td style="width: 40%;text-align: right;">
                            ${{ number_format($initialDiscount, 2) }}</td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax
                            {{ number_format($salesTax * 100, 2) }}
                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($monthlyTotalInitialTax, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Monthly Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($monthlyTotalPostDiscountNew, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>
<?php
}elseif($pricing_overview['frequency'] == 'Initial monthly'){
?>
<table cellpadding="2" cellspacing="0" border="0" height="200" width="200">
    <tr>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%" colspan="2">Initial Service Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Regular Initial Price</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($regularInitialPrice, 2) }}
                        </td>
                    </tr>
                    <?php
                if($initialDiscount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Discount</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($initialDiscount, 2) }}
                        </td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax
                            {{ number_format($salesTax * 100, 2) }}%
                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($totalInitialTax, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($totalInitialPrice, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
        <td style="width: 80%"></td>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%" colspan="2">Monthly Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Monthly Price</td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($recurringPrice, 2) }}
                        </td>
                    </tr>
                    <?php
                if($discountAmount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Discount</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($discountAmount, 2) }}
                        </td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Monthly Tax
                            ${{ number_format($recurringPrice, 2) }}
                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($totalRecurringSalesTax, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Monthly Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($totalRecurringPriceTotal, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>
<?php
}elseif($pricing_overview['frequency'] == 'Due at signup'){
?>
<table cellpadding="2" cellspacing="0" border="0" height="200" width="200">
    <tr>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%">Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Normal Price</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($initialPrice, 2) }}
                        </td>
                    </tr>
                    <?php
                if($initialDiscount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Discount</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($initialDiscount, 2) }}
                        </td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax
                            {{ number_format($salesTax * 100, 2) }}%

                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($dueAtSignUpSalesTax, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($dueAtSignUpTotal, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>
<?php
}elseif($pricing_overview['frequency'] == 'Two payments'){
?>
<table cellpadding="2" cellspacing="0" border="0" height="200" width="200">
    <tr>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%">Initial Service Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Regular Initial Price</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($initialPrice, 2) }}
                        </td>
                    </tr>
                    <?php
                if($initialDiscount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Discount</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($initialDiscount, 2) }}
                        </td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax
                            ${{ number_format($salesTax * 100, 2) }}%
                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($twoPaymentInitialServiceSalesTax, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Initial Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($twoPaymentInitialServiceTotal, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
        <td style="width: 80%"></td>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%">Followup Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Regular Recurring Price</td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($recurringPrice, 2) }}
                        </td>
                    </tr>
                    <?php
                if($recurringDiscount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Recurring Discount</td>
                        <td style="width: 40%;text-align: right;">
                            ${{ number_format($recurringDiscount, 2) }}
                        </td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax
                            {{ number_format($salesTax * 100, 2) }}%
                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($twoPaymentRecurringServiceSalesTax, 2) }}%
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Followup Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($twoPaymentRecurringServiceTotal, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>
<?php
}elseif($pricing_overview['frequency'] == 'Installments'){
?>
<table cellpadding="2" cellspacing="0" border="0" height="200" width="200">
    <tr>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%" colspan="2">Installment Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">No of Installments</td>
                        <td style="text-align: right;width: 40%">
                            {{ $noInstallment }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 60%;text-align: left;">Installment Total</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($initialPrice, 2) }}
                        </td>
                    </tr>
                    <?php
                if($initialDiscount > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Discount</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($initialDiscount, 2) }}
                        </td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax
                            {{ number_format($salesTax * 100, 2) }}%
                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($totalSalesTaxInstallments, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($totalPriceInstallments, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
        <td style="width: 80%"></td>
        <td style="width: 40%">
            <table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
                <thead>
                    <tr>
                        <th style="width: 100%" colspan="2">Per Installment Pricing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 60%;text-align: left;">Installment Price</td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($perInstallmentPerPrice, 2) }}
                        </td>
                    </tr>
                    <?php
                if($perDiscountInstallments > 0){
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Discount</td>
                        <td style="text-align: right;width: 40%">
                            ${{ number_format($perDiscountInstallments, 2) }}
                        </td>
                    </tr>
                    <?php
                }
                ?>
                    <tr>
                        <td style="width: 60%;text-align: left;">Sales Tax
                            {{ number_format($salesTax * 100, 2) }}%
                        </td>
                        <td style="text-align: right; width: 40%">
                            ${{ number_format($installmentPerServiceSalesTax, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width: 60%;text-align: left;">Total Price</th>
                        <th style="text-align: right; width: 40%">
                            ${{ number_format($installmentFinalPriceTotal, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>
<?php
}
?>
