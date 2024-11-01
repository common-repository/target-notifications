<?php
/*
Plugin Name: Target Notifications
Plugin URI: https://targetnotifications.wordpress.com/
Description: Target Notifications lets you send automated personalized notifications based on users' categories/tags selected or search queries.
Version: 1.1.1
Author: Simple Plugins
Author URI: https://targetnotifications.wordpress.com/
License: GPLv2

 */

add_action('wp_enqueue_scripts', 'target_load_jquery');
require plugin_dir_path(__FILE__) . 'includes/logger.php';
require plugin_dir_path(__FILE__) . 'includes/controls.php';
require plugin_dir_path(__FILE__) . 'includes/helpers.php';
if (is_admin()) {
    require plugin_dir_path(__FILE__) . 'admin/target_admin_functions.php';
    require plugin_dir_path(__FILE__) . 'admin/subscribers.php';
    require plugin_dir_path(__FILE__) . 'admin/interests.php';
    require plugin_dir_path(__FILE__) . 'admin/integrations/integrations.php';
    require plugin_dir_path(__FILE__) . 'admin/integrations/contact-form-7.php';
    require plugin_dir_path(__FILE__) . 'admin/admin_help.php';
}
require plugin_dir_path(__FILE__) . 'integrations/contact-form-7.php';
require plugin_dir_path(__FILE__) . 'includes/target_core.php';
register_activation_hook(__FILE__, 'target_activate');
register_deactivation_hook(__FILE__, 'target_deactivate');
function target_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/target_activation.php';
    target_activation_function();
}
function target_deactivate()
{
    wp_clear_scheduled_hook('target_cron_send_action');
    wp_clear_scheduled_hook('target_cron_bundle_action');
}
require plugin_dir_path(__FILE__) . 'shortcode/target_shortcode.php';
//require_once plugin_dir_path(__FILE__) . 'widget.php';

wp_mkdir_p(TARGET_LOG_DIR);
function target_load_jquery()
{
    wp_enqueue_script('jquery-form');
}
