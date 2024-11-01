<?php

defined('ABSPATH') || exit;

function target_integration_submenu()
{
    global $wpdb;
    ?>
    <?php if (isset($_GET['message']) && $_GET['message'] == '2') {?>
 <div id='message' class='updated fade'><p><strong>Options Saved. Please head to the user interests menu to select interest categories.</strong></p></div>
<?php } else if (isset($_GET['message']) && $_GET['message'] == '3') {?>
        <div id='message' class='updated fade'><p><strong>There seems to be a problem</strong></p></div>
       <?php
} else if (isset($_GET['message']) && $_GET['message'] == '4') {?>
    <div id='message' class='updated fade'><p><strong>Please enter the API key</strong></p></div>
   <?php
}
    ?>
    <!-- Top-level menu -->
    <div id="target-general" class="wrap">
    <h2>Mailchimp integration </h2>
    <div class="category_options_form">
    <form method="post" id="targetmailchimp" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="save_target_mailchimp" />
    <table class="form-table">
    <!-- <form method="post" action="admin-post.php">
    <table class="form-table">
 <input type="hidden" name="action" value="save_target_user_selection_options" /> -->
 <!-- Adding security through hidden referrer field -->
 <!-- <?php //wp_nonce_field('target_selection');?> -->
 <?php
wp_nonce_field('target_mailchimp_form');
    global $wpdb;
    $options = get_option('target_options');
    if (isset($options['mailchimp_key'])) {
        $api_key = isset($options['mailchimp_key']) ? $options['mailchimp_key'] : '';
        $dc = substr($api_key, strpos($api_key, '-') + 1);
        $api_length = strlen($api_key) - strlen($dc);
    }

    if (!isset($_GET['message']) || (isset($_GET['message']) && $_GET['message'] == '2')) {
        ?>

<tr style="vertical-align:bottom" class="mailchimp_first">
 <th scope="row">API Key </th>
 <td>
 <input type="text" name="mailchimp_key" size="40" value="<?php echo isset(
            $options['mailchimp_key']) ? esc_attr(str_repeat('*', $api_length) .
            $dc) : ''; ?>"/>
</td>
</tr>
<tr style="vertical-align:bottom" class="mailchimp_first">
 <th scope="row">Store and send with Mailchimp </th>
 <td>
 <label  style="margin-right: 4em;" class="switch">
 <input type="checkbox" name="use_mailchimp" value="0"
 <?php checked(true, isset($options['use_mailchimp']) ? $options['use_mailchimp'] : false);
        ?>> <span class="slider round"></span></label>
</td>
</tr>
 <?php }
    if (isset($_GET['message']) && ($_GET['message'] == '1' || $_GET['message'] == '2')) {?>
    <?php
if (isset($_GET['lists'])) {
        $lists = $_GET['lists']['lists'];
        json_decode(json_encode($lists), true);
        foreach ($lists as $list) {
            echo ' <input type="hidden" name="get_target_mailchimp_lists[' . $list['id'] . ']" value="' . $list['name'] . '" />';
        }
    }
        ?>

<tr >
<th scope="row">Mailchimp list</th><td>
    <select name="mailchimp_list" style="min-width:150px">
 <?php
// Display drop-down list of statuses
        // from list in array
        // $sub_statuses = array('S' => 'Subscribed', 'U' => 'Unsubscribed');
        // foreach ($sub_statuses as $status_mark => $status) {
        //     // Add selected tag when entry matches
        //     // existing status
        //     echo '<option value="' . $status_mark . '" ';
        //     echo '>' . $status;
        // }
        if (isset($_GET['lists'])) {
            $lists = $_GET['lists']['lists'];
            json_decode(json_encode($lists), true);
            foreach ($lists as $list) {
                echo '<option value="' . $list['id'] . '" ';
                if (isset($options['mailchimp_list'])) {
                    selected(array_keys($options['mailchimp_list'])[0], $list['id']);
                }
                echo '>' . $list['name'];
            }
        } else {
            echo '<option value="' . array_keys($options['mailchimp_list'])[0] . '" ';
            echo '>' . array_values($options['mailchimp_list'])[0];
        }
        ?>
 </select>
    </td>
    </tr>
<?php }?>
</table>
<?php if (isset($_GET['message']) && $_GET['message'] == '1') {?>
<input type="submit" value="Save changes" class="button-primary" style="margin-top:20px;"/>
<?php } else if (isset($_GET['message']) && $_GET['message'] == '2') {

    } else {?>
    <input type="submit" value="Next" class="button-primary" style="margin-top:20px;"/>
<?php }?>
<!-- <div class="mailchimp_last"> -->
<?php //submit_button();
    ?>
    <!-- </div> -->
    <!-- </form>
    <form method="post" id="targetmailchimplist" action="<?php //echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="get_target_mailchimp_lists" />
    <?php //wp_nonce_field('target_mailchimp_lists');?>
<input type="submit" value="Next" class="button-primary" style="margin-top:20px;"/> -->
   </form>
    </div>
</div>
<?php
}
function process_target_mailchimp()
{
// Check if user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
// Check if nonce field is present for security
    check_admin_referer('target_mailchimp_form');
    $options = get_option('target_options');
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    if (!isset($_POST['mailchimp_list'])) {
        if (isset($_POST['mailchimp_key']) || (isset($options['mailchimp_key']) && !empty($options['mailchimp_key']))) {
            if (isset($_POST['mailchimp_key']) && isset($_POST['mailchimp_key'][0]) && $_POST['mailchimp_key'][0] != '*') {
                $options['mailchimp_key'] = sanitize_text_field($_POST['mailchimp_key']);
            }
            foreach (array('use_mailchimp') as $option_name) {
                if (isset($_POST[$option_name]) && $_POST[$option_name] === '0') {
                    $options[$option_name] = true;
                } else {
                    $options[$option_name] = false;
                }
            }
            $dc = substr($options['mailchimp_key'], strpos($options['mailchimp_key'], '-') + 1);
            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode('user:' . $options['mailchimp_key']),
                ),
            );
            $fields = 'lists.id,lists.name';
            $response = wp_remote_get('https://' . $dc . '.api.mailchimp.com/3.0/lists/?fields=' . $fields, $args);

            if ($response['response']['code'] == 200) {
                $body = json_decode($response['body']);
                //target_log_file($body);
                wp_redirect(add_query_arg(
                    array('page' => 'target-menu-integration',
                        'message' => '1', 'lists' => $body),
                    admin_url('admin.php')));
            } else {
                //target_log_file('<b>' . $response['response']['code'] . $body->title . ':</b> ' . $body->detail);
                wp_redirect(add_query_arg(
                    array('page' => 'target-menu-integration',
                        'message' => '3'),
                    admin_url('admin.php')));
            }
        } else {
            wp_redirect(add_query_arg(
                array('page' => 'target-menu-integration',
                    'message' => '4'),
                admin_url('admin.php')));
        }
    }
    if (isset($_POST['get_target_mailchimp_lists'])) {
        foreach (array('mailchimp_list') as $option_name) {
            if (isset($_POST[$option_name])) {
                if (isset($_POST['get_target_mailchimp_lists'][$_POST[$option_name]])) {
                    $options[$option_name] = array();
                    $options[$option_name][$_POST[$option_name]] = $_POST['get_target_mailchimp_lists'][$_POST[$option_name]];
                }
            }
        }
    }
    update_option('target_options', $options);
    if (isset($_POST['get_target_mailchimp_lists'])) {
        //create groups
        wp_redirect(add_query_arg(
            array('page' => 'target-menu-integration',
                'message' => '2'),
            admin_url('admin.php')));
    }
    exit;
}
