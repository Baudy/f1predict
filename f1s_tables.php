<?php
/*
What is this:          These are the table rendering functions included with the f1predict WordPress plugin
Author:                Tim Carey
Version:               0.3

Functions held within this file:
--------------------------------
1) f1s_table_scoreboard10 - the standard F1 style top ten scoreboard
2) f1s_treemap_01 - a Google Charts Treemap
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

function f1s_treemap_01($array, $root) {
$html = '
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load(\'current\', {\'packages\':[\'treemap\']});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
            [\'Player\', \'Parent\', \'Points (size)\'],
            [\'' . $root . '\', null, 0],
';
foreach($array as $var) {
    $html .= "['" . $var[0] . "', '" . $var[1] . "', " . $var[2] . "],
    ";
    }
$html .= "
        ]);

        tree = new google.visualization.TreeMap(document.getElementById('chart_div'));

        tree.draw(data, {
          minColor: '#b9e0f7',
          midColor: '#f58426',
          maxColor: '#ef4423',
          maxPostDepth: 2,
          headerHeight: 30,
          fontFamily: 'Ubuntu Condensed',
          fontColor: '#182e3a',
          headerColor: '#b9e0f7',
          showScale: true
        });
      }
    </script>
    <div id=\"chart_div\" style=\"height: 500px;\"></div>
";
return($html);
}

?>