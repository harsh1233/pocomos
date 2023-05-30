<?php
   $currentYear = 'none';
?>
<table border="0" cellpadding="0" cellspacing="0" class="billing-calender" width="100%">
   <tbody>
       <tr>
           <?php
               foreach($schedule->prices as $key => $value){
                   $pre_tax_amount = $value['amount'];
                   $sales_tax_amount = $value['sales_tax'];
                   $monthName = explode(' ', $key);
                  
                   if(in_array($monthName[0], $exceptions) ){
                       ?>
                       <td class="month-text exception-month" style="background-color:#ddd" >{{ $key }}</td>
                       <?php
                   }else{
                       ?>
                       <td class="month-text">{{ $key }}</td>
                       <?php
                   }
               }
           ?>
       </tr>
       <tr>
           <?php
               foreach($schedule->prices as $key => $value){
                   $preTax_amount = $value['amount'];
                   $salesTax_amount = $value['sales_tax'];
                   $monthName = explode(' ', $key);
                   if(in_array($monthName[0], $exceptions) ){
                   ?>
                   <td class="amount-text exception-month" style="background-color:#ddd">
                       <?php
                       }else{
                       ?>
                       <td class="amount-text" >
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
                       ?>
                   </td>
                   <?php
               }
           ?>
       </tr>
   </tbody>
</table>
 
<style type="text/css">
.month-text {
   text-align: center;
   color: #007f01;
   font-weight: bold;
   font-size: 12px !important;
   text-transform : uppercase;
   padding: 5px 20px;
}
.amount-text {
   text-align: center;
   font-weight: bold;
   font-size: 12px !important;
   padding: 20px 0px;
}
.billing-calender tr td {
border: 1px solid  #D5D5D5;
}
.billing-calender .exception-month{ background-color: #ddd !important;}
</style>