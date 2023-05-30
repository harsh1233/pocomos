<?php 
    foreach ($serviceSched as $key => $value) {
      if ($value['isScheduled']) {
        if($value['initial_amount']){
        ?><div class="each-month selected-month">{{ $key }}<br /> ${{ $value['initial_amount'] }}</div><?php
        }else{
        ?><div class="each-month selected-month">{{ $key }}<br /> ${{ $value['totalAmount'] }}</div><?php
        }
      }else{
        ?><div class="each-month">{{ $key }} <br /> ${{ $value['totalAmount'] }}</div><?php
      }
    }
?>
