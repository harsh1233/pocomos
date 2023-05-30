<?php
$selected = 0;
?>
 
<div class="f-left agr-inputs">
   <div class="input-group f-left length-box">
       <?php
       if($contract_length_in_months == 24 ){
           $selected = 1;
           ?>
           <img class="checkbox-img" src="/img/contracts/insight1/box-checked.png">
           <?php
       }else{
           ?>
           <img class="checkbox-img" src="/img/contracts/insight1/box.png">
           <?php
       }       
       ?>
       <span class="placeholder">2 ANS </span>
   </div>
   <div class="input-group f-left length-box">
       <?php
       if($contract_length_in_months == 36 ){
           $selected = 1;
           ?>
           <img class="checkbox-img" src="/img/contracts/insight1/box-checked.png">
           <?php
       }else{
           ?>
           <img class="checkbox-img" src="/img/contracts/insight1/box.png">
           <?php
       }
       ?>
       <span class="placeholder">3 ANS </span>
   </div>
   <div class="input-group f-left length-box">
       <?php
       if($selected == 0 ){
       ?>
        <img class="checkbox-img" src="/img/contracts/insight1/box-checked.png">
        <span class="placeholder">Other: {{$contract_start_date}} - {{$contract_end_date}}</span>
       <?php
       }else{
       ?>
        <img class="checkbox-img" src="/img/contracts/insight1/box.png">
        <span class="placeholder">Autre</span>
       <?php
       }
       ?>
   </div>
</div>