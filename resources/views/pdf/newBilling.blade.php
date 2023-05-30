<?php
$currentYear = 'none';
?>
<table border="0" cellpadding="0" cellspacing="0" class="billing-calender" width="100%">
   <tbody>
       <tr>
           <td class="month-text-agreement" colspan="13"><b>SERVICE AGREEMENT SCHEDULE</b>  &nbsp; 1 YEAR &nbsp; &nbsp; 2 YEARS (5% OFF) &nbsp; &nbsp; 3 YEARS (10% OFF) </td>
       </tr>
       <tr>
           <?php
           foreach($billingSched->prices as $key => $value){
               $pre_tax_amount = $value['amount'];
               $sales_tax_amount = $value['sales_tax'];
               $monthName = explode(' ', $key);
              
               if(in_array($monthName[0], $exceptions) ){
                   ?>
                   <td class="month-text exception-month" style="background-color:#ddd" >{{ $key }}</td>
                   <?php
               }else{
                   ?>
                   <td class="month-text" style="color:green; margin-bottom:10px; margin-top:10px;" >{{ $key }}</td>
                   <?php
               }
           }
       ?>
       </tr>
       <tr>
           <?php
               foreach($billingSched->prices as $key => $value){
                   $preTax_amount = $value['amount'];
                   $salesTax_amount = $value['sales_tax'];
                   $shaded = $value['shaded'];
                   $monthName = explode(' ', $key);
                   if(in_array($monthName[0], $exceptions) ){
                   ?>
                   <td class="amount-text exception-month" style="background-color:#ddd">
                       <?php
                       }else{
                       ?>
                       <td class="amount-text" style="margin-bottom:10px; margin-top:10px;">
                       <?php
                       }
                       ?>
                       ${{ number_format($preTax_amount,2) }}
                       <?php
                           if($salesTax_amount > 0){
                           ?>
                           </br>
                           <span class="sales-tax" style="font-size: 75%;">tx. ${{ number_format($salesTax_amount,2)}}</span>
                           <?php
                           }
                           if($shaded){
                               ?></br> X<?php
                           }
                       ?>
                   </td>
                   <?php
               }
           ?>
       </tr>
   </tbody>
</table>
 
<style type="text/css">
.month-text {text-align: center; color: #007f01; font-weight: bold; text-transform : uppercase; padding: 5px 20px; }
.amount-text {text-align: center; padding: 20px 0px; }
.sales-tax {text-align: center;}
.billing-calender tr td {border: 1px solid  #D5D5D5; }
.month-text-agreement{padding: 5px 10px; text-align: left;}
.billing-calender .exception-month{ background-color: #d5d5d557 !important;}
</style>