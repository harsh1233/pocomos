<table cellpadding="2" cellspacing="0" border="1" height="200">
   <tr>
       <?php
        foreach($schedule->prices as $key => $value){
               $pre_tax_amount = $value['amount'];
               $sales_tax_amount = $value['sales_tax'];
               $monthName = explode(' ', $key);
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
               <span style="text-align: center">{{ $key }}</span><br>
               <?php
                   if(!$pre_tax_amount){
                       ?>               
                       <span style="text-align: center">-</span>
                       <?php
                   }else{
                       ?>
                       <span style="text-align: center;<?= ($pre_tax_amount > 4) ? 'font-size: 10px;' : '' ?>">{{ number_format($pre_tax_amount,2)}}</span><br>
                       <?php
                       if($sales_tax_amount > 0){
                       ?>               
                       <span style="text-align: center; font-size: 75%;">tx. {{ number_format($sales_tax_amount,2) }}</span>
                       <?php
                       }
                   }
               ?></td><?php
        }
       ?>
   </tr>
</table>