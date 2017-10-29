<?php
/*
Plugin Name: F1 Prediction Stats
Plugin URI: http://timcarey.net
Description: Displays statistics from the Motor Racing League plugin, using the Ergast API
Author: Tim Carey
Author URI: http://timcarey.net
Version: 0.3

Shortcodes in this plugin:
--------------------------------
1) f1s_race_result - a shortcode which returns the results of a specific race, defaulting to current
2) f1s_season - a shortcode which returns the details of a specific season, defaulting to current
3) f1s_race_source - a shortcode to return a table with the source of player points for a given race
4) f1s_season_source - a shortcode to return a table with the source of player points for a given season
--------------------------------

*/

// Exit if called directly
if ( ! defined( 'ABSPATH')) {
    exit;
}

// Include all my other files
require (plugin_dir_path(__FILE__) . 'f1s_shortcodes.php');
require (plugin_dir_path(__FILE__) . 'f1s_functions.php');
require (plugin_dir_path(__FILE__) . 'f1s_tables.php');

// Register my style sheet
add_action( 'wp_enqueue_scripts', 'wpdocs_register_plugin_styles' );

// Here's the end of the file.  Farewell.
?>