<table class="other-info">
   <tr class="col-main capitalize" id="pests">
   <?php
       // $rowHeight = count($pests) / $colNum || round(floor(0));
       $rowHeight = floor(round(count($pests) / $colNum));
       $rowAdjust = ceil(round(((count($pests) / $colNum) - $rowHeight) * $colNum));
       $startSlice = 0;
       $colLength = 12/$colNum;
 
       for ($i=0; $i < $colNum-1 ; $i++) {
       ?>
        <td class="col-xs-{{ $colLength }}">
           <ul class="mar-pad-0">
           <?php
               $adjust = 0; 
               if($rowAdjust){
                   $adjust = 1;
                   $rowAdjust = $rowAdjust - 1;
               }
               $curSlice = array_slice($pests,$startSlice,$rowHeight + $adjust);
               $startSlice = $startSlice + ($rowHeight + $adjust);
               foreach ($curSlice as $key => $pest) {
               ?>
                   <li>
                       <div class="input-group psuedo-input">
                       <span>
                       <?php
                       if($pest == $selected){
                       ?>
                       <img class="checkbox-img" src="/img/contracts/insight1/box-checked.png">
                       <?php
                       }else{
                       ?>
                       <img class="checkbox-img" src="/img/contracts/insight1/box.png">
                       <?php
                       }
                       ?>
                       {{$pest['name']}}
                       </span>
                       </div>
                   </li>
               <?php
               }
           ?>
           </ul>
        </td>
       <?php
       }
   ?>
   </tr>
</table>
 
<!--Pests 2-->
<!-- end table 3 -->