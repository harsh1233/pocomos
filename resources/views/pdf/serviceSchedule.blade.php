<?php 
$slength = count($schedule);
$str = '';

for ($i=0; $i < max($slength/12,1); $i++){
    $start =  (($i) * 12);
    if($start < $slength){
        $scheduled = array_slice($schedule,$start,12);
        $str .= `<table cellpadding="2" cellspacing="0" border="1" height="200"><tr>`;
            foreach($scheduled as $key => $month){
                $monthName = explode(' ', $key);
                if(in_array($monthName[0], $exceptions) ){
                    $str .= `<td style="width: 8.33%; text-align: center; background-color:#ddd">`;
                }else{
                    $str .= `<td style="width: 8.33%; text-align: center">`;
                }
                $str .= `<span style="text-align: center">{{ $key }}</span><br><span style="text-align: center">`;
                if($month['isScheduled']){
                    $str .= 'X';
                }else{
                    $str .= `&nbsp`;
                }
            }
            $str .= `</span><br></td></tr></table>`;
    }
}
return $str;
?>








