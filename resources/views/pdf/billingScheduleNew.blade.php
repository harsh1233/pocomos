<?php
$slength = count($schedule->prices);
 
 
for ($i=0; $i < max($slength/12,1); $i++){
   $start = $i * 12;
   if($start < $slength){
       $scheduled = array_slice($schedule->prices,$start,12);
       ?>
       <table cellpadding="2" cellspacing="0" border="1" height="200">
           <tr>
               <?php
               foreach($scheduled as $key => $month){
                   $monthName = explode(' ', $key);
                   $pre_tax_amount = $month['amount'];
                   $sales_tax_amount = $month['sales_tax'];
 
                   if(in_array($monthName[0], $exceptions) ){
                   ?>
                      <td  style="width: 8.33%; text-align: center; background-color:#ddd">
                   <?php   
                   }else{
                    ?>
                       <td  style="width: 8.33%; text-align: center">
                    <?php  
                   }
                   ?>
                   <span style="text-align: center; font-size: 10px; font-weight: normal;">{{ $key }}<br/></span>
                   <?php
                   if(!$pre_tax_amount){
                   ?>
                       <span style="text-align: center">-</span>
                   <?php
                   }else{
                   ?>
                       <span style="text-align: center; font-size: 10px; font-weight:bold;">${{ number_format($pre_tax_amount,2) }}</span>
                       <br/>
                   <?php
                   }
                   if($sales_tax_amount > 0){
                   ?>
                       <span style="text-align: center; font-size: 8px;">tx. <br/>${{ number_format($sales_tax_amount,2) }}</span>
                   <?php
                   }
                   ?></td><?php
               }
               ?>
           </tr>
       </table>
       <?php
   }
}
?>