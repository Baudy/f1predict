<?php
/*
What is this:          These are the table rendering functions included with the f1predict WordPress plugin
Author:                Tim Carey
Version:               0.2

Functions held within this file:
--------------------------------
1) 
*/

function f1s_table_scoreboard10($title, $array, $places) {
    $html = "
        <div class='lap'>" . $title . "</div>
    <div class='ranking'>";
    foreach(range(0, $places-1) as $i) {
        $html .= "
      <div class='driver driver-" . $array[$i]['Constructor'] . "'>
        <div class='driver-position'>" . $array[$i]['Position'] . "</div>
        <div class='driver-color'></div>
        <div class='driver-name'>" . $array[$i]['Name'] . "</div>
        <div class='driver-time'>" . $array[$i]['Time'] . "</div>
      </div>";
    }
    $html .= "
    </div>    
    ";

    return $html;
}

?>