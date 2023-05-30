<?php
$total_contracts = count($contracts);
$count = 0;
if($total_contracts >= 1){
   ?>
   <table cellpadding="5" cellspacing="0">
       <tbody>
           <?php   
           foreach($contracts as $key => $contract){ 
               if($key==0 || (($key==0) % 3 )== 0 ){
                   ?><tr><?php
               } 
            ?>
               <td style="width: 33%; vertical-align: top">
                   <table style="width: 100%" cellpadding="5" cellspacing="0" border="1">
                       <thead>
                           <tr>
                               <th colspan="2">{{$contract['serviceType']? $contract['serviceType'] : ''}}</th>
                           </tr>
                           <tr>
                               <th>Date</th>
                               <th>Technician</th>
                           </tr>
                       </thead>
                       <tbody>
                       <?php
                           foreach($contract['jobs'] as $job){
                           ?>
                            <tr>
                               <td>{{$job['date_scheduled'] ? date_format(date_create($job['date_scheduled']),'m/d/Y') : date('m/d/Y')}}</td>
                               <td>{{ $job['slot_id'] ? ($job['slot_id'] ? $job['technician_id']: 'None' ): 'None'}}</td>
                           </tr>
                           <?php  
                           }
                           ?> 
                       </tbody>
                   </table>
               </td>
               <?php
               if($key==$total_contracts || (($key==0) % 2 )== 0 ){
                   ?></tr><?php
               }
               ?>
            <?php  
           }
       ?>           
       </tbody>
   </table>
<?php
} 
?>