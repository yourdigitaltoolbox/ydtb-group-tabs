<?php

/**
 * Plugin Name: YDTB Group Tabs
 * Plugin URI: https://yourdigitaltoolbox.com
 * Author: Your Digital Toolbox
 * Author URI: https://yourdigitaltoolbox.com
 * Description: A simple plugin to create tab groups in WordPress.
 * Version: 0.0.1 
 * Text Domain: ydtb-group-tabs
 * Domain Path: /languages  
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$autoload_path = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoload_path)) {
    add_action(hook_name: 'admin_notices', callback: function (): void {
        $message = __(text: 'YDTB Tab Groups was downloaded from source and has not been built. Please run `composer install` inside the plugin directory <br> OR <br> install a released version of the plugin which will have already been built.', domain: 'ydtb-group-tabs');
        echo '<div class="notice notice-error">';
        echo '<p>' . $message . '</p>';
        echo '</div>';
    });
}
require_once $autoload_path;
new YDTBGroupTabsRoot\Plugin;
