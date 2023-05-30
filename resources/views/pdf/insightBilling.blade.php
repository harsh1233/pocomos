<?php
$season = 'none';
foreach ($billingSched as $key => $month) {
   $newSeason =  $month['timeoftheyear'] ?? '';
   if($season != 'none' &&  $newSeason != $season){
       ?>
       </div>
       </div>
       </div>
       <?php
   }
   if($newSeason != $season){
   ?>
       <div class="service-col toty-container">
           <div class="bordered">
               <div class="season">
                   <img src="/img/contracts/insight1/{{ $newSeason }}.png" alt="">
                   <span class="bold heading">{{ $newSeason }} </span>
               </div>
               <div class="row mar-0 toty-box">
   <?php
   }
   ?>
   <div class="col-xs-4 pad-0 billing-box<?= ($month['shaded'] ? 'shaded' : '') ?>">
       <label >{{ $month['label'] }}</label>
       <div class="p">
           <span class="placeholder toty-box-amount">${{ number_format($month['totalAmount'],2) }}</span>
       </div>
   </div>
   <?php
   $season = $newSeason;
}
?>
</div>
</div>
</div>
 
<style>
   .shaded {
       background-color: rgba(222, 222, 222, 0.58);
   }
</style>