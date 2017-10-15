<?php
/*
What is this:          These are the shared functions included with the f1predict WordPress plugin
Author:                Tim Carey
Version:               0.2

Functions held within this file:
--------------------------------
1) f1s_unique_multidim_array - http://php.net/manual/en/function.array-unique.php
2) f1s_gchart_racesource - makes a google chart showing race points source
3) f1s_gchart_champsource - makes a google chart showing championship points source
*/

// A function to load my style sheet
function wpdocs_register_plugin_styles() {
    wp_register_style( 'f1s_style', plugins_url( 'f1predict/f1s_style.css' ) );
    wp_enqueue_style( 'f1s_style' );
}

// A function to rewrite a multidimentsional array
function f1s_unique_multidim_array($array) { 
    $temp_array = array();
    $key_array = array();  
    $i = 0; 
    $j = 0;
    
    foreach($array as $val) { 
        $temp_array = array_values($temp_array);
        if (!in_array($val['Player'], $key_array)) { 
            $key_array[$i] = $val['Player']; 
            $temp_array[$i] = $val; 
        }  else {
            $j = array_search($val['Player'], array_column($temp_array, 'Player'));
            $temp_array[$j]['Pole'] += $val['Pole'];
            $temp_array[$j]['Top8'] += $val['Top8'];
            $temp_array[$j]['Position'] += $val['Position'];
            $temp_array[$j]['Total'] += $val['Total'];
        }
        $i++; 
    } 
    return $temp_array; 
}

function f1s_gchart_stackbar($array, $chartname) {
	$i = 0;

	$countArrayLength = count($array);

	$chart = "
    <script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script>
    <script type=\"text/javascript\">
      google.charts.load('current', {'packages':['bar']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Players', 'Pole', 'Top8', 'Position'],";

	for($i=0;$i<$countArrayLength;$i++){
	    $chart .= "['" . $array[$i]['Player'] . "'," . $array[$i]['Pole'] . "," . $array[$i]['Top8'] . "," . $array[$i]['Position'] . "],";
	} 

	$chart .= 
	"
	    ]);

	    var options = {
	    	fontName: 'Open Sans',
	        height: 400,
	        isStacked: 'true',
	        backgroundColor: '#dddddd',
	        bars: 'horizontal',
	        colors: '#19877C'
	    };

		var chart = new google.charts.Bar(document.getElementById('" . $chartname . "'));
        chart.draw(data, google.charts.Bar.convertOptions(options));
	}
	</script>
	";

	$chart .= "<div id=" . $chartname . "></div>";

	return $chart;
}

// A function to sort a multidimensional array by one or it's sub array values
function sortBySubValue($array, $value, $asc = true, $preserveKeys = false)
{
    if (is_object(reset($array))) {
        $preserveKeys ? uasort($array, function ($a, $b) use ($value, $asc) {
            return $a->{$value} == $b->{$value} ? 0 : ($a->{$value} - $b->{$value}) * ($asc ? 1 : -1);
        }) : usort($array, function ($a, $b) use ($value, $asc) {
            return $a->{$value} == $b->{$value} ? 0 : ($a->{$value} - $b->{$value}) * ($asc ? 1 : -1);
        });
    } else {
        $preserveKeys ? uasort($array, function ($a, $b) use ($value, $asc) {
            return $a[$value] == $b[$value] ? 0 : ($a[$value] - $b[$value]) * ($asc ? 1 : -1);
        }) : usort($array, function ($a, $b) use ($value, $asc) {
            return $a[$value] == $b[$value] ? 0 : ($a[$value] - $b[$value]) * ($asc ? 1 : -1);
        });
    }
    return $array;
}

?>