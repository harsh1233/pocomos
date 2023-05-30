<?php 
    $evenRow = true;
    foreach ($pests as $key => $value) {
      if($key == 0 || $key/2 == 0){
        $evenRow = true;
        ?>
        <div class="row">
        <?php
      }else{
        $evenRow = false;
      }
      ?>
        <div class="d-inline-flex align-items-center col-md-6">
            <?php
                if(array_search($value['id'], array_column($selected, 'pest_id')) !== false) {
                // if($selected){
                ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" class="pr-2">
                    <path d="M20 12.194v9.806h-20v-20h18.272l-1.951 2h-14.321v16h16v-5.768l2-2.038zm.904-10.027l-9.404 9.639-4.405-4.176-3.095 3.097 7.5 7.273 12.5-12.737-3.096-3.096z"></path>
                </svg>
                <?php
                }else{
                ?>
                 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" class="pr-2">
                    <path d="M22 2v20h-20v-20h20zm2-2h-24v24h24v-24z"></path>
                </svg>
                <?php
                }
            ?>
            <span class="text-capitalize pr-3">{{ $value['name'] }}</span>
        </div>
      <?php
      if ($evenRow == false) {
        ?></div><?php
      }
      $keyValue = $key;
    }
    if($evenRow){
    ?> </div><?php
    }
?>
