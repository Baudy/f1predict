<?php
/*
What is this:          These are the shortcodes included with the f1predict WordPress plugin
Author:                Tim Carey
Version:               0.3

Functions held within this file:
--------------------------------
1) f1s_race_result - a shortcode which returns the results of a specific race, defaulting to current
2) f1s_season - a shortcode which returns the details of a specific season, defaulting to current
3) f1s_race_source - a shortcode to return a table with the source of player points for a given race
4) f1s_season_source - a shortcode to return a table with the source of player points for a given season
5) f1s_results_predictions - a shortcode to return race results and player predictions for a given race
6) f1s_season_progression - a shortcode to return a chart showing points progression between two races
7) f1s_scoreboard - a shortcode to test scoreboard setup
8) f1s_competition_treemap - a shortcode to create a Google Treemap for a given season
--------------------------------
*/

// Bring in the WP functions
add_shortcode('f1s_race_result', 'f1s_race_result');
add_shortcode('f1s_season', 'f1s_season');
add_shortcode('f1s_race_source', 'f1s_race_source');
add_shortcode('f1s_season_source', 'f1s_season_source');
add_shortcode('f1s_results_predictions', 'f1s_results_predictions');
add_shortcode('f1s_season_progression', 'f1s_season_progression');
add_shortcode('f1s_scoreboard', 'f1s_scoreboard');
add_shortcode('f1s_competition_treemap', 'f1s_competition_treemap');

//require (plugin_dir_path(__FILE__) . 'f1s_functions.php');

// 1) f1s_race_result
function f1s_race_result($atts, $content = null) {
    $atts = shortcode_atts(
    	array(
    		'year' => 'current',
    		'round' => 'last',
    	), $atts
    );

    $f1s_array = array();
    $f1s_url = 'http://ergast.com/api/f1/' . $atts['year'] . '/' . $atts['round'] . '/results';
    $f1s_xml = simplexml_load_file($f1s_url) or die ("we couldn't load the file");

    foreach(range(0, 9) as $i) {
        $f1s_firstname = $f1s_xml->RaceTable->Race->ResultsList->Result[$i]->Driver[0]->GivenName;
        $f1s_surname = $f1s_xml->RaceTable->Race->ResultsList->Result[$i]->Driver[0]->FamilyName;
        $f1s_shortname = (string)$f1s_xml->RaceTable->Race->ResultsList->Result[$i]->Driver[0]['code'];
        $f1s_constructor = (string)$f1s_xml->RaceTable->Race->ResultsList->Result[$i]->Constructor['constructorId'] ;
        $f1s_time = (string)$f1s_xml->RaceTable->Race->ResultsList->Result[$i]->Time;
        if($f1s_time=='') {
            $f1s_time = (string)$f1s_xml->RaceTable->Race->ResultsList->Result[$i]->Status;
        }
        $f1s_array[] = array('Position' => ($i+1), 'Name' => $f1s_shortname, 'Constructor' => $f1s_constructor, 'Time' => $f1s_time);
    }

    $f1s_title = $f1s_xml->RaceTable->Race['season'] . ' ' . $f1s_xml->RaceTable->Race->RaceName;

    $html = f1s_table_scoreboard10($f1s_title, $f1s_array, 10);

    /*  here's the old code which makes a standard table
    $html = '<table class="f1predict">';
    $html .= '<th colspan="2" class="dark_span">' . $f1s_xml->RaceTable->Race->RaceName . '</th>';
    // columns
    $html .= '<tr align="left">';
    foreach($f1s_array[0] as $key=>$value){
            $html .= '<th>' . htmlspecialchars($key) . '</th>';
        }
    $html .= '</tr>';
    // data rows
    foreach( $f1s_array as $key=>$value){
        $html .= '<tr>';
        foreach($value as $key2=>$value2){
            $html .= '<td>' . htmlspecialchars($value2) . '</td>';
        }
        $html .= '</tr>';
    }
    // finish table and return it
    $html .= '</table><br>';
    */  // this is the end of the old code which made a standard table

    // echo "<pre>";
    // print_r($f1s_array);
    // echo "</pre>";

    return $html;
}

// 2) f1s_season
function f1s_season($atts, $content = null) {
    $atts = shortcode_atts(
    	array(
    		'year' => 'current'
    	), $atts
    );

    $f1s_array = array();
    $f1s_url = 'http://ergast.com/api/f1/' . $atts['year'];
    $f1s_xml = simplexml_load_file($f1s_url) or die ("we couldn't load the file");

    // header
    $html = '<table class="f1predict">';
    $html .= '<tr><th colspan="4" class="dark_span">' . $f1s_xml->RaceTable['season'] . ' Races</th></tr>';

    // header
    $html .= '<tr align="left">';
    $html .= '<th>Round</th> <th>Race Name</th> <th>Date</th> <th>Time</th>';
    $html .= '</tr>';

    // data rows
    foreach($f1s_xml->RaceTable->Race as $key=>$value){
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($value['round']) . '</td>';
        $html .= '<td title="' . $value->Circuit->CircuitName[0] . '">' . htmlspecialchars($value->RaceName[0]) . '</td>';
        $html .= '<td>' . htmlspecialchars($value->Date[0]) . '</td>';
        $html .= '<td>' . htmlspecialchars($value->Time[0]) . '</td>';
        $html .= '</tr>';
    }
    // finish table and return it
    $html .= '</table><br>';
    return $html;
}

// 3) f1s_race_source
function f1s_race_source($atts, $content = null) {
    $atts = shortcode_atts(
        array(
            'race_id' => '1',
            'display' => 'both'
        ), $atts
    );
    $race_id = $atts['race_id'];
    $display = $atts['display'];

    // query from the database
    global $wpdb;
    $results = $wpdb->get_results ( "
        SELECT  f1pc_motorracingleague_entry.player_name AS Player,
            f1pc_motorracingleague_entry.race_id AS Race,
            f1pc_motorracingleague_entry.points_breakdown AS Points
        FROM f1pc_motorracingleague_entry
        WHERE f1pc_motorracingleague_entry.race_id = '$race_id'
    " );

    // cycle into an array and calculate points gained from exact position matches
    $output = array();
    $i = 0;
    foreach(range(0, count($results)-1) as $i) {
        $results[$i]->Points = unserialize($results[$i]->Points);
        $output[$i]['Player'] = $results[$i]->Player;
        $output[$i]['Pole'] = 0 + $results[$i]->Points[0];
        $output[$i]['Top8'] = 0 + $results[$i]->Points['bonus'];
        $output[$i]['Position'] = 0 + ($results[$i]->Points['total'] - ($results[$i]->Points[0] + $results[$i]->Points['bonus']));
        $output[$i]['Total'] = 0 + $results[$i]->Points['total'];
        $i++;
    }

    //sort the output array in decreasing order based on the total scores
    usort($output, function ($a,$b) {
              return $a['Total']<$b['Total'];
         }
    );

    // spanning header
    $html = '<table class="f1predict">';
    $html .= '<tr><th colspan="5" class="dark_span">Race Points Source</th></tr>';

    // header
    $html .= '<tr align="left">';
    $html .= '<th>Player</th> <th>Pole</th> <th>Top 8</th> <th>Position</th> <th>Total</th>';
    $html .= '</tr>';

    // data rows
    foreach($output as $key=>$value){
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($value['Player']) . '</td>';
        $html .= '<td>' . htmlspecialchars($value['Pole']) . '</td>';
        $html .= '<td>' . htmlspecialchars($value['Top8']) . '</td>';
        $html .= '<td>' . htmlspecialchars($value['Position']) . '</td>';
        $html .= '<td>' . htmlspecialchars($value['Total']) . '</td>';
        $html .= '</tr>';
    }
    // finish the table
    $html .= '</table><br>';

    // create a chart
    $chart = f1s_gchart_stackbar($output, 'race_source');

    // return the table and the chart
    switch ($display) {
        case 'table':
            return $html;
            break;
        case 'graph':
            return $chart;
            break;
        case 'both':
            return $html . $chart;
            break;
    }
}


// 4) f1s_season_source
function f1s_season_source($atts, $content = null) {
    $atts = shortcode_atts(
    	array(
    		'starting_race' => '1',
    		'ending_race' => '1',
            'display' => 'both'
    	), $atts
    );
    $race_id1 = $atts['starting_race'];
    $race_id2 = $atts['ending_race'];
    $display = $atts['display'];

    // query from the database
    global $wpdb;
    $results = $wpdb->get_results ( "
        SELECT 	f1pc_motorracingleague_entry.player_name AS Player,
    		f1pc_motorracingleague_entry.race_id AS Race,
            f1pc_motorracingleague_entry.points_breakdown AS Points
        FROM f1pc_motorracingleague_entry
        WHERE f1pc_motorracingleague_entry.race_id >= '$race_id1'
        AND f1pc_motorracingleague_entry.race_id <= '$race_id2'
    " );

    // cycle into an array and calculate points gained from exact position matches
    $output = array();
    $i = 0;
    foreach( $results as $result ) {
        $result->Points = unserialize($result->Points);
        $output[$i]['Player'] = $result->Player;
        $output[$i]['Pole'] = 0 + $result->Points[0];
        $output[$i]['Top8'] = 0 + $result->Points['bonus'];
        $output[$i]['Position'] = 0 + ($result->Points['total'] - ($result->Points['bonus'] + $result->Points[0]));
        $output[$i]['Total'] = 0 + $result->Points['total'];
        $i++;
    }

    // call the function to add the results from the output array
    $output = f1s_unique_multidim_array($output);

    //sort the output array in decreasing order based on the total scores
    usort($output, function ($a,$b) {
              return $a['Total']<$b['Total'];
         }
    );

    // spanning header
    $html = '<table class="f1predict">';
    $html .= '<tr><th colspan="5" class="dark_span">Championship Points Source</th></tr>';

    // header
    $html .= '<tr align="left">';
    $html .= '<th>Player</th> <th>Pole</th> <th>Top 8</th> <th>Position</th> <th>Total</th>';
    $html .= '</tr>';

    // data rows
    foreach($output as $key=>$value){
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($value['Player']) . '</td>';
        $html .= '<td>' . htmlspecialchars($value['Pole']) . '</td>';
        $html .= '<td>' . htmlspecialchars($value['Top8']) . '</td>';
        $html .= '<td>' . htmlspecialchars($value['Position']) . '</td>';
        $html .= '<td>' . htmlspecialchars($value['Total']) . '</td>';
        $html .= '</tr>';
    }
    // finish table and return it
    $html .= '</table><br>';

    // create a chart
    $chart = f1s_gchart_stackbar($output, 'season_source');

    // return the table and the chart
    switch ($display) {
        case 'table':
            return $html;
            break;
        case 'graph':
            return $chart;
            break;
        case 'both':
            return $html . $chart;
            break;
    }

}

// 5) f1s_results_predictions
function f1s_results_predictions($atts, $content = null) {
    $atts = shortcode_atts(
        array(
            'race_id' => '1',
            'title' => 'Results for Race'
        ), $atts
    );
    $race_id = $atts['race_id'];
    $title = $atts['title'];
    global $wpdb;
    $results = $wpdb->get_results ( "
    SELECT r.position AS 'Position', p.shortcode AS 'Driver'
    FROM f1pc_motorracingleague_result r
    INNER JOIN f1pc_motorracingleague_participant p ON r.participant_id = p.id
    WHERE r.race_id = '$race_id'
    ORDER BY r.position
    " ) or die ('Call Tim & tell him I couldn\'t return the results query');

    $predictions = $wpdb->get_results ( "
    SELECT
    en.player_name as 'Player',
    pr.position AS 'Position',
    pa.shortcode AS 'Driver',
    en.points AS 'Points'
    FROM f1pc_motorracingleague_prediction pr
    JOIN f1pc_motorracingleague_participant pa ON pr.participant_id = pa.id
    JOIN f1pc_motorracingleague_entry en ON pr.entry_id = en.id
    WHERE en.race_id = '$race_id'
    ORDER BY en.points DESC, en.player_name, pr.position
    " ) or die ('Call Tim & tell him I couldn\'t return the predictions query');

    // spanning header
    $html = '<table class="f1predict">';
    $html .= '<tr><th colspan="11" class="dark_span">' . $title . '</th></tr>';

    // titles header
    $html .= '<tr align="left">';
    $html .= '<th>Player</th> <th>Pole</th> <th>1st</th> <th>2nd</th> <th>3rd</th> <th>4th</th> <th>5th</th> <th>6th</th> <th>7th</th> <th>8th</th> <th>Points</th>';
    $html .= '</tr>';

    // race result (with red highlight)
        $html .= '<tr class="highlight">';
        $html .= '<td>RACE RESULT</td>';
        $html .= '<td>' . htmlspecialchars($results[0]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($results[1]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($results[2]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($results[3]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($results[4]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($results[5]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($results[6]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($results[7]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($results[8]->Driver) . '</td>';
        $html .= '<td>N/A</td>';
        $html .= '</tr>';

    // predictions
    $i = 0;
    $j = count($predictions);
    foreach ($predictions as $spinette) {
        if ($i==$j) break;
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($predictions[$i]->Player) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[$i]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[($i+1)]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[($i+2)]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[($i+3)]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[($i+4)]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[($i+5)]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[($i+6)]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[($i+7)]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[($i+8)]->Driver) . '</td>';
        $html .= '<td>' . htmlspecialchars($predictions[$i]->Points) . '</td>';
        $html .= '</tr>';
        $i = ($i+9);
    }
    $html .= '</table><br>';
    return $html;
}

// 6) f1s_season_progression
function f1s_season_progression($atts, $content = null) {
    $atts = shortcode_atts(
        array(
            'start_race' => '1',
            'end_race' => '2'
        ), $atts
    );
    $start_race = $atts['start_race'];
    $end_race = $atts['end_race'];

    // get the data from the database
    global $wpdb;
    $results = $wpdb->get_results ( "
    SELECT 
    f1pc_motorracingleague_entry.player_name AS 'Player',
    f1pc_motorracingleague_race.circuit AS 'Race',
    f1pc_motorracingleague_race.id AS 'race_id',
    f1pc_motorracingleague_entry.points AS 'Points'
    FROM f1pc_motorracingleague_entry
    JOIN f1pc_motorracingleague_race ON f1pc_motorracingleague_entry.race_id=f1pc_motorracingleague_race.id
    WHERE f1pc_motorracingleague_entry.race_id >= '$start_race'
    AND f1pc_motorracingleague_entry.race_id <= '$end_race'
    AND f1pc_motorracingleague_entry.points > 0
    ORDER BY f1pc_motorracingleague_entry.race_id, f1pc_motorracingleague_entry.player_name
    " ) or die ('Call Tim & tell him I couldn\'t return the results query');

    // make an array of players
    $players_arr = array();
    $i = 0;
    foreach($results as $val) {
        if(!in_array($val->Player, $players_arr)) {
            $players_arr[$i] = $val->Player;
            $i++;
        }
    }

    // make an array of races
    $races_arr = array();
    $i = 0;
    foreach($results as $val) {
        if(!in_array($val->Race, $races_arr)) {
            $races_arr[$val->race_id] = $val->Race; 
            $i++;
        }
    }

    // this will be an array of players with each result listed with race_id as their index
    $data1 = array();
    $i = 0;
    foreach($players_arr as $val) {
        $data1[$i] = array("Name"=>$val, "Total"=>0);
        foreach($races_arr as $key => $pal) {
            $data1[$i][$key] = 0;
        }
        $i++;
    }

    $i = 0;
    $j = 0;
    foreach($results as $val) {
        $j = array_search($val->Player, array_column($data1, 'Name'));
        $data1[$j][$val->race_id] = $val->Points;
        $data1[$j]["Total"] = $data1[$j]["Total"] + $val->Points;
    }

    // now make it incrementally scoring
    $i = 0;
    $j = $start_race + count($races_arr);
    foreach($data1 as $key => $val) {
        for($i=$start_race+1;$i<$j;$i++) {
            $data1[$key][$i] = $data1[$key][$i] + $data1[$key][$i-1];
        }
    }

    // sort the array by total points
    $data1 = sortBySubValue($data1, "Total", false);

    // populate the first array entry with race titles
    array_unshift($data1, array("Name"=>"Races"));
    foreach($races_arr as $key => $val) {
        //$data1[0][$key] = substr($val, 0, 3);
        $data1[0][$key] = $val;
    }

    //echo "<pre>";
    //print_r($data1);
    //echo "</pre>";

    $chart = "
    <script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script>
    <script type=\"text/javascript\">
    google.charts.load('current', {'packages':['line']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {

        var data = new google.visualization.DataTable();
    ";

    $chart .= "data.addColumn('string', 'Races');";

    for($i=1;$i<count($data1)-1;$i++){
        $chart .= "data.addColumn('number', '" . $data1[$i]['Name'] . "');";
    }
    $chart .= "

        data.addRows([
    ";

    foreach($races_arr as $rkey => $rval){
        $chart .= '[\'' . substr($rval, 0, 3) . '\', ';
        for($i=1;$i<count($data1)-1;$i++){
            $chart .= $data1[$i][$rkey] . ', ';
        }
        $chart .= "],";
    } 

    $chart .= "
        ]);

        var options = {
            fontName: 'Ubuntu Condensed',
            height: '600',
            backgroundColor: '#ffffff',
            animation: {duration: '900', startup: 'true', easing: 'in'}
        };

        var chart = new google.charts.Line(document.getElementById('prog_chart'));
        chart.draw(data, google.charts.Line.convertOptions(options));
        }
    </script>
    ";

    $chart .= '<div id="prog_chart"></div>';

    //return '<pre>' . $chart . '</pre>';
    return $chart;
}

function f1s_competition_treemap($atts, $content = null) {
    $atts = shortcode_atts(
        array(
            'season' => '2017'
        ), $atts
    );
    $input_1 = $atts['season'];

    // get the results from the database
    $results = f1s_results_01($input_1);

    // make an array of races
    $races_arr = array();
    $i = 0;
    foreach($results as $val) {
        if(!in_array($val['Race'], $races_arr)) {
            $races_arr[$i] = $val['Race'];
            $i++;
        }   
    }

    // make an array of players
    $player_arr = array();
    $i = 0;
    foreach($results as $val) {
        if(!in_array($val['Player'], $player_arr)) {
            $player_arr[$i] = $val['Player'];
            $i++;
        }   
    }

    // write the array for the treemap
    $treemap = array();
    $treemap[0][0] = $input_1;
    $treemap[0][1] = 'null';
    $treemap[0][2] = '0';

    $i=0 + count($treemap);
    foreach($player_arr as $var) {
        $treemap[$i][0] = $var;
        $treemap[$i][1] = $input_1;
        $treemap[$i][2] = '0';
        $i++;
    }

    $i=0 + count($treemap);
    foreach($results as $var) {
        $treemap[$i][0] = $var['Race'] . " - " . $var['Player'] . " (" . $var['Total'] . ")";
        $treemap[$i][1] = $var['Player'];
        $treemap[$i][2] = $var['Total'];    
        $i++;
    }

    $i=0 + count($treemap);
    foreach($results as $var) {
        $treemap[$i][0] = $var['Race'] . " - " . $var['Player'] . " - Pole (" . $var['Pole'] . ")";
        $treemap[$i][1] = $var['Race'] . " - " . $var['Player'] . " (" . $var['Total'] . ")";
        $treemap[$i][2] = $var['Pole'];    
        $i++;
        $treemap[$i][0] = $var['Race'] . " - " . $var['Player'] . " - Top 8 (" . $var['Top8'] . ")";
        $treemap[$i][1] = $var['Race'] . " - " . $var['Player'] . " (" . $var['Total'] . ")";
        $treemap[$i][2] = $var['Top8'];    
        $i++;
        $treemap[$i][0] = $var['Race'] . " - " . $var['Player'] . " - Position (" . $var['Position'] . ")";
        $treemap[$i][1] = $var['Race'] . " - " . $var['Player'] . " (" . $var['Total'] . ")";
        $treemap[$i][2] = $var['Position'];    
        $i++;
    }

    $html = f1s_treemap_01($treemap,$input_1);
    return($html);
}

?>