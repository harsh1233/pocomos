<?php
 
$pattern = '/^\\d+$/';
 
if(preg_match_all($pattern, $width)){
   $width = $width. ~ 'px';
}  
?>
<img src="<?= $src?>"  style="width: <?= $width?>; height: <?= $height?>"/>