<div class="d-inline-block">
    <?php
        if($autopay){
        ?>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" class="pr-2">
            <path d="M20 12.194v9.806h-20v-20h18.272l-1.951 2h-14.321v16h16v-5.768l2-2.038zm.904-10.027l-9.404 9.639-4.405-4.176-3.095 3.097 7.5 7.273 12.5-12.737-3.096-3.096z"></path>
        </svg>
        <?php
        }else{
        ?>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" class="pr-2">
            <path d="M22 2v20h-20v-20h20zm2-2h-24v24h24v-24z"></path>
        </svg>
        <?php
        }    
    ?>
    <span class="text-capitalize pr-3">AutoPay (See Below For Details)</span>
</div>

