<?php 
    foreach ($serviceSched as $key => $value) {
       if($value['isScheduled']){
        ?><div class="each-month selected-month">{{ $key }}</div><?php
       }else{
        ?><div class="each-month">{{ $key }}</div><?php
       }
    }
?>
