<?php

// Register function to be called when new blogs are added
// to a network site
add_action('wpmu_new_blog', 'target_new_network_site');

function target_activation_function()
{
    // Get access to global database access class
    global $wpdb;
    // Check to see if WordPress installation is a network
    if (is_multisite()) {
        // If it is, cycle through all blogs, switch to them
        // and call function to create plugin table
        if (!empty($_GET['networkwide'])) {
            $start_blog = $wpdb->blogid;
            $blog_list = $wpdb->get_col('SELECT blog_id FROM ' . $wpdb->blogs);
            foreach ($blog_list as $blog) {
                switch_to_blog($blog);
                // Send blog table prefix to creation function
                target_create_table($wpdb->get_blog_prefix());
            }
            switch_to_blog($start_blog);
            return;
        }
    }
    target_create_table($wpdb->get_blog_prefix());
    $options = get_option('target_options', array());
    add_filter('cron_schedules', 'target_add_custom_cron_schedule', 1001);
    //Use wp_next_scheduled to check if the event is already scheduled
    $timestamp = wp_next_scheduled('target_cron_send_action');
    //if (is_admin()) {
    if ($timestamp == false && (!defined('WP_INSTALLING') || !WP_INSTALLING)) {
        wp_schedule_event(time(), 'target-notifications', 'target_cron_send_action');
    }
    //}
    $bundle_timestamp = wp_next_scheduled('target_cron_bundle_action');
    //if (is_admin()) {
    if ($bundle_timestamp == false && (!defined('WP_INSTALLING') || !WP_INSTALLING) && get_option('target_options')['target_automatic_mail'] == false) {
        wp_schedule_event(time(), 'target-notifications-bundle', 'target_cron_bundle_action');
    }
    // }
    $new_options['target_sender_mail'] = get_option('admin_email');
    $new_options['target_sender_name'] = get_option('blogname');
    $new_options['target_return_path'] = get_option('admin_email');
    $new_options['target_reply_to'] = get_option('admin_email');
    $new_options['target_test_email'] = get_option('admin_email');
    $new_options['target_automatic_mail'] = true;
    $new_options['target_title_search'] = true;
    $new_options['target_body_mail'] = true;
    $new_options['target_max_emails'] = 100;
    $new_options['target_email_schedule_number'] = 4;
    $new_options['target_email_schedule'] = 'week';
    $new_options['recipients'] = 'registered';
    $new_options['target_search_number'] = 20;
    $new_options['target_email_subject'] = htmlspecialchars_decode(get_bloginfo(),
        ENT_QUOTES) . " - New results for one of your searches ";
    $new_options['target_email_body'] = "A new post you might be interested in has been added. ";
    $new_options['target_email_body_bulk'] = "New posts you might be interested in have been added. ";
    $merged_options = wp_parse_args($options, $new_options); //array_merge( $new_options, $options );
    $compare_options = array_diff_key($new_options, $options);
    if (empty($options) || !empty($compare_options)) {
        update_option('target_options', $merged_options);
    }
}

function target_create_table($prefix)
{
    // Prepare SQL query to create database table
    // using function parameter
    global $charset_collate;
    // global $wpdb;
    //$wpdb_collate = $wpdb->collate;
    $creation_query =
        'CREATE TABLE ' . $prefix . 'target_subscribers (
            `id` int (20) NOT NULL AUTO_INCREMENT,
             `email` varchar (100) UNIQUE DEFAULT NULL,
              `name` varchar (100) DEFAULT NULL,
              `created` date DEFAULT NULL,
              `updated` date DEFAULT NULL,
               `status` varchar (1) NOT NULL DEFAULT "S",
                `registered_user_id` int(20) NOT NULL DEFAULT 0,
        PRIMARY KEY  (`id`)) ;';
    // COLLATE {$wpdb_collate}';
    //$charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    //target_log_file(ABSPATH, 'abspath');
    dbDelta($creation_query);
    //target_log_file($delta);
    // $wpdb->query($creation_query);
    $creation_query_categories =
    // "CREATE TABLE `" . $prefix . "target_subscriber_selection` (term_id int(20) NOT NULL DEFAULT 0, subscriber_id int(20) NOT NULL DEFAULT 0, PRIMARY KEY  (term_id,subscriber_id),KEY term_id (term_id),KEY subscriber_id (subscriber_id), FOREIGN KEY (subscriber_id) REFERENCES `" . $prefix . "target_subscribers`(id)) $charset_collate;";
    'CREATE TABLE ' . $prefix . 'target_subscriber_selection (term_id int(20) NOT NULL DEFAULT 0, subscriber_id int(20) NOT NULL DEFAULT 0, PRIMARY KEY  (term_id,subscriber_id),KEY term_id (term_id),KEY subscriber_id (subscriber_id), FOREIGN KEY (subscriber_id) REFERENCES `' . $prefix . 'target_subscribers`(id)) ;';
    //$charset_collate;";
    dbDelta($creation_query_categories);
    //$wpdb->query($creation_query_categories);
}

function target_new_network_site($blog_id)
{
    global $wpdb;
    // Check if this plugin is active when new blog is created
    // Include plugin functions if it is
    if (!function_exists('is_plugin_active_for_network')) {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
    }

    // Select current blog, create new table and switch back
    if (is_plugin_active_for_network(plugin_basename(__FILE__))) {
        $start_blog = $wpdb->blogid;
        switch_to_blog($blog_id);
        // Send blog table prefix to table creation function
        target_create_table($wpdb->get_blog_prefix());
        switch_to_blog($start_blog);
    }
}
