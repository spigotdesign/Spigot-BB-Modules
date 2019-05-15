<?php
/**
 * Plugin Name: 	Spigot Beaver Builder Modules
 * Plugin URI: 		https://spigotdesign.com/
 * Description: 	Custom builder modules.
 * Version: 		1.0
 * Author: 			Bryan Hoffman
 * Author URI: 		https://spigotdesign.com/
 */


define( 'SPIGOT_BB_MODULES_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPIGOT_BB_MODULES_URL', plugins_url( '/', __FILE__ ) );

require_once SPIGOT_BB_MODULES_DIR . 'classes/class-spigot-bb-modules-loader.php';
