<?php

/**
 * Plugin Name: swmenu
 * Description: swMenu widget to display a menu created by swmenu.com
 * Author: Sean White
 * Version: 1.2
 * Author URI: http://www.swmenu.com
 */
defined('ABSPATH') or die("Cannot access pages directly.");

defined("DS") or define("DS", DIRECTORY_SEPARATOR);
//error_reporting(E_ALL);

class sw_swmenu {

    function sw_swmenu() {
        if (!is_admin()) {
            // Header styles
            add_action('wp_head', array('sw_swmenu', 'header'));
          wp_enqueue_script('jquery');

        }
        
    }

    function header() {
       $swmenupro=sw_swmenu::get_style();
       echo "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . sw_swmenu::get_plugin_directory() . "/css/menu.css\" media=\"screen\" />\n";
       
       if(@$swmenupro['responsive']==('flat'||'accordion')){
         echo "<link type='text/css' href='" . sw_swmenu::get_plugin_directory() . "/css/menu_responsive.css' rel='stylesheet' />\n";
       }
       if(@$swmenupro['pie_hack']){
         echo "<!--[if lt IE 9]>\n<link type='text/css' href='" . sw_swmenu::get_plugin_directory() . "/css/menu_pie.css' rel='stylesheet' />\n<![endif]-->\n";
       }
    }

    function get_plugin_directory() {
       return plugin_dir_url( __FILE__ );
    }
    
    function get_style(){
        $swmenupro=array();
        $handle = fopen(sw_swmenu::get_plugin_directory() . '/vars.txt', 'r');
        $import = fread($handle, 1000000);
        fclose($handle);
        eval('$swmenupro = ' . $import . ';');
        return $swmenupro;
        
        }

}

;

// Include the widget
include_once(dirname( __FILE__ ) . '/swmenu_widget.php');

// Initialize the plugin.
$swmenu = new sw_swmenu();

// Register the widget
add_action('widgets_init', create_function('', 'return register_widget("sw_swmenu_widget");'));
?>