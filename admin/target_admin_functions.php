<?php

defined('ABSPATH') || exit;

add_action('admin_menu', 'target_admin_menu');
add_action('admin_init', 'target_admin_init');
//add_action('wp_enqueue_scripts', 'target_adding_scripts');
add_action('admin_enqueue_scripts', 'target_adding_scripts');
//add_action('wp_print_styles', 'target_adding_scripts');

function target_adding_scripts()
{
    $custom_js_ver = date("ymd-Gis", filemtime(plugin_dir_path(__FILE__) . 'settings_script.js'));
    $custom_css_ver = date("ymd-Gis", filemtime(plugin_dir_path(__FILE__) . 'stylesheet.css'));
    wp_enqueue_style('settings_style', plugin_dir_url(__FILE__) . 'stylesheet.css', array(), $custom_css_ver);
    wp_enqueue_script('settings_script', plugin_dir_url(__FILE__) . 'settings_script.js', array('jquery'), $custom_js_ver);
    // wp_register_script('settings_script', plugins_url('settings_script.js', __FILE__), array('jquery'),'1.1', true);

    // wp_enqueue_script('settings_script');
}

function target_admin_menu()
{
    // Create top-level menu item
    $menu_page = add_menu_page('Target Notifications Configuration Page', 'Target Notifications', 'manage_options',
        'target-main-menu', 'target_complex_main', plugins_url('adminicon.png', __FILE__));
    $sub_suffix = add_submenu_page('target-main-menu', 'Subscribers list', 'Subscribers',
        'manage_options', 'target-sub-menu', 'target_subscribers_submenu');
    $cat_page = add_submenu_page('target-main-menu', 'User interests', 'User interests',
        'manage_options', 'target-menu-select', 'target_selection_submenu');
    $integ_page = add_submenu_page('target-main-menu', 'Mailchimp integration', 'Mailchimp integration',
        'manage_options', 'target-menu-integration', 'target_integration_submenu');

    if (!empty($menu_page)) {
        add_action('load-' . $menu_page, 'target_help_tabs');
    }
    if (!empty($cat_page)) {
        add_action('load-' . $cat_page, 'target_cat_help_tabs');
    }
    if (!empty($sub_suffix)) {
        add_action("admin_print_scripts-$sub_suffix", 'target_sub_export_scripts');
    }
}
function target_admin_init()
{

    add_action('admin_post_save_target_options', 'process_target_options');
    add_action('admin_post_target_send_test', 'target_send_test');
    add_action('admin_post_target_reset_post', 'target_reset_post');
    add_action('admin_post_target_pagination', 'target_pagination');
    add_action('admin_post_save_target_subscriber', 'process_target_subscriber');
    add_action('admin_post_export_target_subscribers', 'export_target_subscribers');
    add_action('admin_post_delete_target_subscriber', 'delete_target_subscriber');
    add_action('admin_post_save_target_categories_options', 'process_target_categories_options');
    add_action('admin_post_save_target_mailchimp', 'process_target_mailchimp');
    //add_action('admin_post_get_target_mailchimp_lists', 'process_target_mailchimp_lists');
}

function target_sub_export_scripts()
{
    wp_enqueue_script('jquery-ui-sortable');
}

function target_complex_main()
{

    // Retrieve plugin configuration options from database
    $options = get_option('target_options');

    ?>
 <?php if (isset($_GET['message']) && $_GET['message'] == '1') {?>
 <div id='message' class='updated fade'><p><strong>Settings
 Saved</strong></p></div>
<?php }?>
<?php if (isset($_GET['message']) && $_GET['message'] == '2') {?>
 <div id='message' class='updated fade'><p><strong>Email sent</strong></p></div>
<?php }?>
<?php if (isset($_GET['message']) && $_GET['message'] == '3') {?>
 <div id='message' class='updated fade'><p><strong>There's a problem</strong></p></div>
<?php }?>
<?php if (isset($_GET['message']) && $_GET['message'] == '4') {?>
 <div id='message' class='updated fade'><p><strong>Last post reset</strong></p></div>
<?php }?>
 <div id="target-general" class="frap">
 <h2>Target Notifications</h2>
 <?php if (isset($_GET['tab'])) {
        target_admin_tabs($_GET['tab']);
    } else {
        target_admin_tabs('general');
    }
    ?>
 <form method="post" action="admin-post.php">
 <input type="hidden" name="action" value="save_target_options" />
 <!-- Adding security through hidden referrer field -->
 <?php wp_nonce_field('target');?>
<?php if (isset($_GET['tab'])) {
        $tab = $_GET['tab'];
    } else {
        $tab = 'general';
    }
    ?>
    <input type="hidden" name="target_get_tab" value="<?php echo esc_html($tab); ?>" />
    <table class="form-table">
    <?php switch ($tab) {
        case 'general':
            ?>

 <tr style="vertical-align:bottom">
 <th scope="row">Sender name</th>
 <td>
    <input type="text" name="target_sender_name"
 value="<?php echo esc_html($options['target_sender_name']);
            ?>"/>
</td>
 </tr>
          <tr style="vertical-align:bottom">
            <th scope="row"> Sender mail </th>
 <td>
    <input type="text" name="target_sender_mail"
 value="<?php echo esc_html($options['target_sender_mail']);
            ?>"/>
</td>
</tr>
<tr style="vertical-align:bottom">
            <th scope="row"> Return path </th>
 <td>
    <input type="text" name="target_return_path"
 value="<?php echo esc_html($options['target_return_path']);
            ?>"/>
</td>
</tr>
<tr style="vertical-align:bottom">
            <th scope="row"> Reply to </th>
 <td>
    <input type="text" name="target_reply_to"
 value="<?php echo esc_html($options['target_reply_to']);
            ?>"/>
</td>
</tr>
 <tr style="vertical-align:bottom">
            <th scope="row"> Emails sent per hour </th>
 <td>
    <input type="number" name="target_max_emails" min="0"
 value="<?php echo esc_html($options['target_max_emails']);
            ?>"/>
</td>
</tr></table>
<?php submit_button();?>
</form>
<?php
break;
        case 'newsletter':
            ?>
<tr style="vertical-align:bottom">
  <th scope="row"> Email subject </th>
 <td>
    <input type="text" size="60" name="target_email_subject"
 value="<?php echo esc_html($options['target_email_subject']); ?>"/>
</td>
</tr>
<tr style="vertical-align:bottom">
  <th scope="row"> Email body </th>
 <td>
    <!-- <textarea rows="12" cols="75" name="target_email_body">
    <?php //echo esc_html($options['target_email_body']); ?></textarea> -->
    <?php $display = $options['target_automatic_mail'] ? 'display:block' : 'display:none';
            $display_bulk = $options['target_automatic_mail'] ? 'display:none' : 'display:block';
            ?>
    <div name='target_body' style=<?php echo $display ?>>
    <?php wp_editor(stripslashes($options['target_email_body']), 'target_email_body', array('editor_height' => '300px'));

            ?></div>

    <div name='target_body_bulk' style=<?php echo $display_bulk ?>>
     <?php wp_editor(stripslashes($options['target_email_body_bulk']), 'target_email_body_bulk', array('editor_height' => '300px'));
            ?> </div>
</td>
</tr>
</table>
<?php submit_button();?>
</form>
<!-- <input type="text" size="40" name="atarget_test_email"
 value="<?php //echo esc_html(get_option('admin_email'));
            ?>"/>
<input type="submit" value="Test Email" class="button-secondary"/><br/> -->

<p>Placeholders to add to email subject or body:</p>
<p>%%BLOG_URL%% - %%HOME_URL%% - %%BLOG_NAME%% - %%BLOG_DESCRIPTION%% - %%POST_TITLE%% - %%POST_URL%% - %%CONTENT_FIRST_PARAGRAPH%% - %%CONTENT%% - %%POST_THUMBNAIL%%
        </p>
<p>%%POST_TITLE_i%% - %%POST_URL_i%% - %%CONTENT_FIRST_PARAGRAPH_i%% - %%CONTENT_i%% - %%POST_THUMBNAIL_i%% *i from 1 to 4 for recent posts </p>
<div class="send_test_form">
 <!-- <form method="post" id="targetsendtest" action="'<?//php admin_url('admin-ajax.php'); ?> '"> -->
 <form method="post" action="admin-post.php">
           <?php wp_nonce_field('target_test', 'security');?>
<input type="hidden" name="action" value="target_send_test">
<input type="hidden" name="target_get_tab" value="<?php echo esc_html($tab); ?>" />
<input type="text" size="40" name="target_test_email" placeholder="Recipient email"
 value="<?php echo esc_html($options['target_test_email']);
            ?>"/>
<input type="submit" value="Test Email" class="button-secondary"/>
</form>
</div>
<?php break;
        case 'automation':
            ?>
<tr style="vertical-align:bottom">
            <th scope="row"> Send automatically after publishing </th>
 <td>
 <label class="automatic-radio" style="margin-right: 4em;">
 <input type="radio" name="target_automatic_mail" value="0" class="hi"
 <?php checked(true, $options['target_automatic_mail']);
            ?>>Yes</input></label>
    <label class="automatic-radio"><input type="radio" name="target_automatic_mail" value="1"
 <?php checked(false, $options['target_automatic_mail']);
            ?>>No</input></label>
</td>
</tr>
<?php $disabled = $options['target_automatic_mail'] ? 'disabled' : '';?>
<tr style="vertical-align:bottom" class=<?php echo $disabled ?>>
            <th scope="row"> When to send the emails  </th>

 <td id="target_schedule" >
 <input type="number" name="target_email_schedule_number"  value="<?php echo esc_html($options['target_email_schedule_number']); ?>">
    <label>Per</label>
    <select name="target_email_schedule">
    <option value="week" <?php selected($options['target_email_schedule'], 'week');?>>Week</option>
 <option value="day" <?php selected($options['target_email_schedule'], 'day');?>>Day</option>
 <option value="month" <?php selected($options['target_email_schedule'], 'month');?>>Month</option>
 </select>
</td>
</tr>
<tr style="vertical-align:bottom">
            <th scope="row"> Send to emails saved through </th>
 <td>
 <label style="margin-right: 4em;">
 <input type="radio" name="recipients" value="subscribed"
 <?php if ($options['recipients'] === "subscribed") {
                echo ' checked="checked"';
            }
            ?>>
 Subscription</label>
 <label>
 <input type="radio" name="recipients" value="registered"
 <?php if ($options['recipients'] === "registered") {
                echo ' checked="checked"';
            }
            ?>>
 Registration</label>
</td>
</tr>
<tr style="vertical-align:bottom">
            <th scope="row"> Send if user search in  </th>
 <td>
 <label style="margin-right: 4em;">
 <input type="checkbox" name="target_title_search"
 <?php if (!isset($options['target_title_search']) || $options['target_title_search']) {
                echo ' checked="checked"';
            }
            ?>>Post title</label>
     <label><input type="checkbox" name="target_body_search"
 <?php if (!isset($options['target_body_search']) || $options['target_body_search']) {
                echo ' checked="checked"';
            }
            ?>>Post body</label>
</td>
</tr>
<tr style="vertical-align:bottom">
            <th scope="row"> Save registered user's searches  </th>
 <td>
 <input type="number" name="target_search_number"  value="<?php echo esc_html($options['target_search_number']); ?>">
    <label>per user</label>
</td>
</tr>
</table>
<!-- <input type="text" size="40" name="atarget_test_email"
 value="<?php //echo esc_html(get_option('admin_email'));
            ?>"/>
<input type="submit" value="Test Email" class="button-secondary"/><br/> -->

<!-- <p>Placeholders to add to email subject or body:</p>
<p>%%BLOG_URL%% - %%HOME_URL%% - %%BLOG_NAME%% - %%BLOG_DESCRIPTION%% - %%POST_TITLE%% - %%POST_URL%% - %%CONTENT_FIRST_PARAGRAPH%% - %%CONTENT%% - %%POST_THUMBNAIL%%
        </p>
<p>%%POST_TITLE_i%% - %%POST_URL_i%% - %%CONTENT_FIRST_PARAGRAPH_i%% - %%CONTENT_i%% - %%POST_THUMBNAIL_i%% *i from 1 to 4 for recent posts </p> -->

<!--   Request users for email: <input type="checkbox"
 name="request_user_email"
 <?php
//if (
            //$options['request_user_email'] ) echo ' checked="checked" ';
            ?>
<!--  <input type="submit" value="Submit"
 class="button-primary"/><br /> -->
 <?php // break; ?>
 <?php submit_button();?>
 </form>
 <?php //case 'newsletter': ?>
 <!-- <div class="send_test_form">
 <form method="post" id="targetsendtest" action="'<?//php admin_url('admin-ajax.php'); ?> '">
 <form method="post" action="admin-post.php">
           <?php // wp_nonce_field('target_test', 'security');?>
<input type="hidden" name="action" value="target_send_test">
<input type="text" size="40" name="target_test_email" placeholder="Recipient email"
 value="<?php //echo esc_html($options['target_test_email']);
            ?>"/>
<input type="submit" value="Test Email" class="button-secondary"/>
</form>
</div>
<br>
    <?php break;
        case 'progress': ?>
<div class="reset_last_post_form">
 <!-- <form method="post" id="targetsendtest" action="'<?//php admin_url('admin-ajax.php'); ?> '"> -->
</form>
 <form method="post" action="admin-post.php">
           <?php wp_nonce_field('target_reset', 'security');?>
           <input type="hidden" name="target_get_tab" value="<?php echo esc_html($tab); ?>" />
           <table class="form-table">
<input type="hidden" name="action" value="target_reset_post">
<th scope="row">Progress</th>
 <td><?php
if ($options['target_automatic_mail'] == true) {
                if (isset($options['target_last_post']) && !empty($options['target_last_post'])) {?>
Currently sending post: <?php
echo get_the_title($options['target_last_post'][0]) . " - Published " . get_the_date("", $options['target_last_post'][0]);
                } elseif (isset($options['target_last_post_completed']) && !empty($options['target_last_post_completed'])) {
                    ?>
    Last post sent: <?php
echo get_the_title($options['target_last_post_completed']) . " - Published " . get_the_date("", $options['target_last_post_completed']);} ?>
    <br>
    <?php
global $wpdb;
                $count_query = 'select count(*) as total from ' . $wpdb->get_blog_prefix();
                $count_query .= 'target_subscribers ';
// $s_rank_query = 'select row_num from (';
                // $s_rank_query .= 'select ROW_NUMBER() OVER (ORDER BY id) row_num, id from ' . $wpdb->get_blog_prefix();
                // $s_rank_query .= 'target_subscribers ';
                // $s_rank_query .= 'ORDER BY id) where id = %d';
                $s_rank_query = 'select * from (select id, @curRank := @curRank + 1 as rank from ' . $wpdb->get_blog_prefix();
                $s_rank_query .= 'target_subscribers s, (select @curRank :=0) r ';
                $s_rank_query .= 'order by id ASC) list where list.id = %d ';
                $r_rank_query = 'select * from (select id, @curRank := @curRank + 1 as rank from ' . $wpdb->get_blog_prefix();
                $r_rank_query .= 'users u, (select @curRank :=0) r ';
                $r_rank_query .= 'order by id ASC) list where list.id = %d ';
                //$rank_result = $wpdb->get_results($wpdb->prepare($s_rank_query, $options['target_last_subscriber_id_sent']), ARRAY_A);
                if ($options['recipients'] === "subscribed") {
                    if (isset($options['target_last_subscriber_id_sent'])) {
                        $rank_result = $wpdb->get_results($wpdb->prepare($s_rank_query, $options['target_last_subscriber_id_sent']), ARRAY_A);
                        $percent = $wpdb->get_results($count_query)[0]->total > 0 ? intval($rank_result[0]['rank'] / $wpdb->get_results($count_query)[0]->total * 100) : 0;
                        ?>Last subscriber processed: <?php
echo isset($options['target_last_subscriber_id_sent']) ? $rank_result[0]['rank'] . "/" . $wpdb->get_results($count_query)[0]->total : 'None';
                        ?><br>
                <?php
if (isset($options['target_s_emails_sent'])) {

                            ?>Emails sent: <?php
echo $options['target_s_emails_sent'];}
                        ?><br>
<br>
<div id="Progress_Status">
    <div id="mprogressBar" style="width:<?php echo $percent ?>%">&nbsp;<?php echo $percent ?>%&nbsp;</div></div>

            <?php
}} else {
                    if (isset($options['target_last_id_sent'])) {
                        $rank_result = $wpdb->get_results($wpdb->prepare($r_rank_query, $options['target_last_id_sent']), ARRAY_A);
                        $percent = count_users()['total_users'] > 0 ? intval($rank_result[0]['rank'] / count_users()['total_users'] * 100) : 0;
                        ?>Last registered user processed: <?php
echo 'Last registered user processed:' . $options['target_last_id_sent'] !== null ? $rank_result[0]['rank'] . "/" . count_users()['total_users'] : 'None';

                        ?><br>
                        <?php
if (isset($options['target_r_emails_sent'])) {

                            ?>Emails sent: <?php
echo $options['target_r_emails_sent'];}
                        ?><br>
    <br>
    <div id="Progress_Status">
        <div id="mprogressBar" style="width:<?php echo $percent ?>%">&nbsp;<?php echo $percent ?>%&nbsp;</div></div>

                <?php
}}
            } else {
                if (isset($options['bundle_posts_sending']) && !empty($options['bundle_posts_sending'])) {
                    if (isset($options['target_bundle_done']) && $options['target_bundle_done'] === '1') {?>
             Posts processed: <?php
echo get_the_title($options['bundle_posts_sending'][0]) . " - Published " . get_the_date("", $options['bundle_posts_sending'][0]);} else { ?>
            Posts processing: <?php
echo get_the_title($options['bundle_posts_sending'][0]) . " - Published " . get_the_date("", $options['bundle_posts_sending'][0]);
                    }}
                ?>

                <br>
                <?php
global $wpdb;
                $count_query = 'select count(*) as total from ' . $wpdb->get_blog_prefix();
                $count_query .= 'target_subscribers ';
                // $s_rank_query = 'select row_num from (';
                // $s_rank_query .= 'select ROW_NUMBER() OVER (ORDER BY id) row_num, id from ' . $wpdb->get_blog_prefix();
                // $s_rank_query .= 'target_subscribers ';
                // $s_rank_query .= 'ORDER BY id) where id = %d';
                $s_rank_query = 'select * from (select id, @curRank := @curRank + 1 as rank from ' . $wpdb->get_blog_prefix();
                $s_rank_query .= 'target_subscribers s, (select @curRank :=0) r ';
                $s_rank_query .= 'order by id ASC) list where list.id = %d ';
                $r_rank_query = 'select * from (select id, @curRank := @curRank + 1 as rank from ' . $wpdb->get_blog_prefix();
                $r_rank_query .= 'users u, (select @curRank :=0) r ';
                $r_rank_query .= 'order by id ASC) list where list.id = %d ';
                //$rank_result = $wpdb->get_results($wpdb->prepare($s_rank_query, $options['target_last_subscriber_id_sent']), ARRAY_A);
                if ($options['recipients'] === "subscribed") {
                    if (isset($options['target_last_sub_id_sent_bundle'])) {
                        $rank_result = $wpdb->get_results($wpdb->prepare($s_rank_query,
                            $options['target_last_sub_id_sent_bundle']), ARRAY_A);
                        $percent = $wpdb->get_results($count_query)[0]->total > 0 ?
                        intval($rank_result[0]['rank'] / $wpdb->get_results($count_query)[0]->total * 100) : 0;
                        ?>Last subscriber processed: <?php
echo $options['target_last_sub_id_sent_bundle'] ? $rank_result[0]['rank'] . "/" . $wpdb->get_results($count_query)[0]->total : 'None';
                        ?><br>
             <?php
if (isset($options['target_sb_emails_sent'])) {

                            ?>Emails sent: <?php
echo $options['target_sb_emails_sent'];}
                        ?><br>
            <br>
            <div id="Progress_Status">
                <div id="mprogressBar" style="width:<?php echo $percent ?>%">&nbsp;<?php echo $percent ?>%&nbsp;</div></div>

            <?php
}} else {

                    if (isset($options['target_last_id_sent'])) {
                        $rank_result = $wpdb->get_results($wpdb->prepare($r_rank_query, $options['target_last_id_sent']), ARRAY_A);
                        $percent = count_users()['total_users'] > 0 ? intval($rank_result[0]['rank'] / count_users()['total_users'] * 100) : 0;
                        ?>Last registered user processed: <?php
echo 'Last registered user processed:' . $options['target_last_id_sent'] !== null ? $rank_result[0]['rank'] . "/" . count_users()['total_users'] : 'None';

                        ?><br>
                <br>
                <div id="Progress_Status">
                    <div id="mprogressBar" style="width:<?php echo $percent ?>%">&nbsp;<?php echo $percent ?>%&nbsp;</div></div>
                    <?php
if (isset($options['target_r_emails_sent']) && !empty($options['target_r_emails_sent'])) {

                            ?>Emails sent: <?php
echo $options['target_r_emails_sent'];}
                        ?><br>
                <?php
}}

            }
            ?>

</td><td><input type="submit" value="Reset last post" class="button-secondary" style="background-color:#aa3700; border-color:#aa3700; color:white"/>
</td>
</tr>
</table>
<input type="hidden" name="target_get_tab" value="<?php echo esc_html($tab); ?>" />
</form>
    <?php }?>
</div>
<!-- <script type='text/javascript'>
jQuery(document).ready(function($){
    console.log('why');
$('#targetsendtest').ajaxForm({
success:function(response){
console.log(response);
jQuery('.send_test_form').html( response )
},error:function(response){
console.log(response);}
});
});
</script> -->
 </div>
<?php
}
function target_admin_tabs($current = 'general')
{
    $tabs = array('general' => 'General Settings', 'newsletter' => 'Newsletter', 'automation' => 'Email Automation', 'progress' => 'Progress');
    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab => $name) {
        $class = ($tab == $current) ? 'nav-tab nav-tab-active' : "nav-tab";
        echo "<a class='$class' href='?page=target-main-menu&tab=$tab'>$name</a>";

    }
    echo '</h2>';
}

function target_send_test()
{
    check_ajax_referer('target_test', 'security');
    $options = get_option('target_options');
    $latest_pt = get_posts("post_type=post&numberposts=1&fields=ids");
    if (isset($options['use_mailchimp']) && $options['use_mailchimp'] == true) {
        if (isset($_POST['target_test_email']) && filter_var($_POST['target_test_email'], FILTER_VALIDATE_EMAIL)) {
            $options['target_test_email'] = sanitize_text_field($_POST['target_test_email']);
            update_option('target_options', $options);
            $post = get_post($latest_pt[0]);

            $result = target_mailchimp_send($post, true, $_POST['target_test_email']);
            if ($result === '1') {
                $url_parameters = isset($_POST['target_get_tab']) ? 'message=2&tab=' . $_POST['target_get_tab'] : 'message=2';
                wp_redirect(admin_url('admin.php?page=target-main-menu&' . $url_parameters));
            } else {
                $url_parameters = isset($_POST['target_get_tab']) ? 'message=3&tab=' . $_POST['target_get_tab'] : 'message=3';
                wp_redirect(admin_url('admin.php?page=target-main-menu&' . $url_parameters));
            }
        }
        exit;
    } else {
        if (isset($_POST['target_test_email']) && filter_var($_POST['target_test_email'], FILTER_VALIDATE_EMAIL)) {

            if ($options['target_sender_mail']) {
                $sender_mail = $options['target_sender_mail'];
            } else {
                $sender_mail = get_option('admin_email');
            }

            $from = $options['target_sender_name'];
            $headers = array('From: ' . $from . ' <' . $sender_mail . '>');
            $headers[] = 'Content-type: text/html';
            if (!isset($options['target_email_body']) || empty($options['target_email_body'])) {
                $message = 'A new post you might be interested in has been added. ';
                $message .= '<br />';
                $message .= 'Post Title: ' . get_the_title($latest_pt[0]);
                $message .= '<br />';
                $message .= '<br />';
                $message .= '<a href="';
                $message .= get_permalink($latest_pt[0]);
                $message .= '">Check the post</a>';
            } else {

                $message = stripslashes($options['target_email_body']);
                $message = target_replace_placeholders($message, $latest_pt[0]);
            }
            if (!isset($options['target_email_subject']) || empty($options['target_email_subject'])) {
                $email_title = htmlspecialchars_decode(get_bloginfo(),
                    ENT_QUOTES)
                //  - New results for one of your searches: "
                 . " - " . $post->post_title;
            } else {
                $email_title = $options['target_email_subject'];
                $email_title = target_replace_placeholders($email_title, $latest_pt[0]);

            }
            $options['target_test_email'] = sanitize_text_field($_POST['target_test_email']);
            update_option('target_options', $options);
            $mail = wp_mail($_POST['target_test_email'], $email_title, $message, $headers);
            if ($mail) {
                // wp_redirect(add_query_arg(
                //     array('page' => 'target-main-menu',
                //         'message' => '2'),
                //     admin_url('options-general.php')));
                $url_parameters = isset($_POST['target_get_tab']) ? 'message=2&tab=' . $_POST['target_get_tab'] : 'message=2';
                wp_redirect(admin_url('admin.php?page=target-main-menu&' . $url_parameters));
            } else {
                // wp_redirect(add_query_arg(
                //     array('page' => 'target-main-menu',
                //         'message' => '3'),
                //     admin_url('options-general.php')));
                $url_parameters = isset($_POST['target_get_tab']) ? 'message=3&tab=' . $_POST['target_get_tab'] : 'message=3';
                wp_redirect(admin_url('admin.php?page=target-main-menu&' . $url_parameters));
            }

        }
        exit;
    }
}
function target_reset_post()
{
    check_ajax_referer('target_reset', 'security');
    $options = get_option('target_options');
    $options['target_last_post'] = array();
    $options['target_last_subscriber_id_sent'] = null;
    $options['target_last_id_sent'] = null;
    $options['target_last_sub_id_sent_bundle'] = null;
    update_option('target_options', $options);
    // wp_redirect(add_query_arg(
    //     array('page' => 'target-main-menu',
    //         'message' => '4'),
    //     admin_url('options-general.php')));
    $url_parameters = isset($_POST['target_get_tab']) ? 'message=4&tab=' . $_POST['target_get_tab'] : 'message=4';
    wp_redirect(admin_url('admin.php?page=target-main-menu&' . $url_parameters));
    exit;
}
function process_target_options()
{
    // Check that user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }

    // Check that nonce field created in configuration form
    // is present
    check_admin_referer('target');
    // Retrieve original plugin options array
    $options = get_option('target_options');
    // Cycle through all text form fields and store their values
    // in the options array
    if (isset($_POST['target_get_tab'])) {
        $tab = $_POST['target_get_tab'];
    } else {
        $tab = 'general';
    }

    switch ($tab) {
        case 'general':
            foreach (array('target_sender_name', 'target_sender_mail', 'target_return_path', 'target_reply_to') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = sanitize_text_field($_POST[$option_name]);
                }
            }
            foreach (array('target_max_emails') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = sanitize_text_field($_POST[$option_name]);
                    if (!is_numeric($options[$option_name])) {
                        $options[$option_name] = 100;
                    }

                }
            }
            break;
        case 'automation':
            foreach (array('target_automatic_mail') as $option_name) {
                if (isset($_POST[$option_name]) && $_POST[$option_name] === '0') {
                    $options[$option_name] = true;
                    wp_unschedule_hook('target_cron_bundle_action');
                } else {
                    $options[$option_name] = false;
                }
            }
            foreach (array('target_title_search', 'target_body_search') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = true;
                } else {
                    $options[$option_name] = false;
                }
            }
            foreach (array('target_search_number') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = sanitize_text_field($_POST[$option_name]);
                }
            }
            foreach (array('target_email_schedule_number') as $option_name) {
                if (isset($_POST[$option_name]) && (is_int($_POST[$option_name]) || ctype_digit($_POST[$option_name])) && (int) $_POST[$option_name] > 0) {
                    $interval = $options[$option_name];
                    $options[$option_name] = sanitize_text_field($_POST[$option_name]);
                    if ($interval !== $options[$option_name]) {
                        wp_unschedule_hook('target_cron_bundle_action');
                    }
                }
            }
            foreach (array('target_email_schedule') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $schedule = $options[$option_name];
                    $options[$option_name] = sanitize_text_field($_POST[$option_name]);
                    if ($schedule !== $options[$option_name]) {
                        wp_unschedule_hook('target_cron_bundle_action');
                    }
                }
            }
            foreach (array('recipients') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = sanitize_text_field($_POST[$option_name]);
                }
            }
            break;
        case 'newsletter':
            foreach (array('target_email_subject', 'target_email_body', 'target_email_body_bulk', 'target_test_email') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = sanitize_text_field($_POST[$option_name]);
                }
            }
            break;
    }
    // Store updated options array to database
    update_option('target_options', $options);
    // Redirect the page to the configuration form that was
    // processed
    // wp_redirect(add_query_arg(
    //     array('page' => 'target-main-menu',
    //         'message' => '1'),
    //     admin_url('options-general.php')));
    $url_parameters = isset($_POST['target_get_tab']) ? 'message=1&tab=' . $_POST['target_get_tab'] : 'message=1';
    wp_redirect(admin_url('admin.php?page=target-main-menu&' . $url_parameters));
    exit;
}
