<?php

// Check that code was called from WordPress with
// uninstallation constant declared
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// if (get_option('target_user_search_queries') != false) {
//     delete_option('target_user_search_queries');
// }
//if (get_option('target_options') != false) {
//target_log_file("uninstall target options");
delete_option('target_options');

//}
$meta_type = 'user';
$user_id = 0; // This will be ignored, since we are deleting for all users.
$meta_key = 'target_user_search_queries';
$meta_value = ''; // Also ignored. The meta will be deleted regardless of value.
$delete_all = true;

delete_metadata($meta_type, $user_id, $meta_key, $meta_value, $delete_all);

global $wpdb;
// Check if site is configured for network installation
if (is_multisite()) {
    if (!empty($_GET['networkwide'])) {
        // Get blog list and cycle through all blogs
        $start_blog = $wpdb->blogid;
        $blog_list = $wpdb->get_col('SELECT blog_id FROM ' . $wpdb->blogs);
        foreach ($blog_list as $blog) {
            switch_to_blog($blog);
            // Call function to delete bug table with prefix
            target_drop_table($wpdb->get_blog_prefix());
        }
        switch_to_blog($start_blog);
        return;
    }
}

target_drop_table($wpdb->prefix);
function target_drop_table($prefix)
{
    global $wpdb;
    $wpdb->query('DROP TABLE IF EXISTS ' . $prefix . 'target_subscriber_selection');
    $wpdb->query('DROP TABLE IF EXISTS ' . $prefix . 'target_subscribers');

}
