<table cellpadding="2" cellspacing="0" border="1" height="200">
   <?php
   $cols = min(max(sqrt(ceil(round(count($pests)))),sqrt(ceil(round(count($specialty))))),10);
   if(count($pests)){
   ?>
    <tr>
       <td colspan="{{ $cols }}">returns a pluralized version of the pest label.</td>
   </tr>
   <?php
   for ($i=0; $i < max(0,(count($pests)/$cols),1); $i++){
       $curSlice = array_slice($pests,$cols * $i , $cols);
       if((count($curSlice)) > 0){
       ?>
           <tr>
               <?php
                   foreach ($curSlice as $key => $pest) {
                     ?>
                     <td style="text-align: center;">{{$pest['name']}}</td>
                     <?php
                   }
                   if(count($curSlice) > 0 && count($curSlice) < $cols ){
                       for ($j=0; $j < $cols - count($curSlice); $j++) {
                           ?><td></td><?php
                       }
                   }
               ?>
           </tr>
       <?php
       }
   }
   ?>
   <?php
   }
   if(count($specialty)){
       ?>
        <tr>
           <td colspan="{{ $cols }}">Specialty</td>
       </tr>
       <?php
           for ($i=0; $i < max(0,(count($specialty)/$cols),1); $i++){
               $curSlice = array_slice($specialty,$cols * $i , $cols);
               if((count($curSlice)) > 0){
               ?>
               <tr>
                   <?php
                       foreach ($curSlice as $key => $pest) {
                       ?>
                       <td style="text-align: center;">{{$pest['name']}}</td>
                       <?php
                       }
                       if(count($curSlice) < $cols ){
                           for ($j=0; $j < $cols - count($curSlice); $j++) {
                               ?><td></td><?php
                           }
                       }
                   ?>
               </tr>
               <?php
               }
           }
   }
   ?>
</table>