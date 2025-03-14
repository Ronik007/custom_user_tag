<?php

/* 
Plugin Name: Custom User Tags
Plugin URI: https://growquest.in/
Description: Adds a custom taxonomy "User Tag" under the Users menu, allowing administrators to categorize users.
Version: 1.0.0
Requires at least: 5.0
Requires PHP: 7.0
Author: Ronik M Gajjar
Author URI: https://growquest.in/
License:     GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: custom-user-tags
Domain Path: /languages
*/


// Check for ABSPATH
if (!defined('ABSPATH')) {
    exit;
}

// Define Some Constants
if (!defined('CUSTOM_USER_TAG_PATH')) define('CUSTOM_USER_TAG_PATH', plugin_dir_path(__FILE__));
if (!defined('CUSTOM_USER_TAG_URL')) define('CUSTOM_USER_TAG_URL', plugin_dir_url(__FILE__));

// Include the Custom User Tag Main Class
require_once('inc/main-class.php');

// Get the Instance of the Main Class
\CUSTOMER_USER_TAG_MAIN::getInstance();