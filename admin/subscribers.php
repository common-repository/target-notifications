<?php

defined('ABSPATH') || exit;

function target_pagination()
{

    $options = get_option('target_options');
    $current_page = $options['target_subscribers_search'];
    foreach (array('target_first') as $option_name) {
        if (isset($_POST[$option_name])) {
            $target_search_page = 1;
            $options['target_subscribers_search'] = $target_search_page;
            update_option('target_options', $options);
        }
    }
    foreach (array('target_next') as $option_name) {
        if (isset($_POST[$option_name])) {

            $target_search_page = $current_page + 1;
            $options['target_subscribers_search'] = $target_search_page;
            update_option('target_options', $options);
        }
    }
    foreach (array('target_prev') as $option_name) {
        if (isset($_POST[$option_name])) {
            $target_search_page = $current_page - 1;
            $options['target_subscribers_search'] = $target_search_page;
            update_option('target_options', $options);
        }
    }
    foreach (array('target_last') as $option_name) {
        if (isset($_POST[$option_name])) {
            $target_search_page = -1;
            $options['target_subscribers_search'] = $target_search_page;
            update_option('target_options', $options);
        }
    }
    foreach (array('search_page') as $option_name) {
        if (isset($_POST[$option_name])) {
            $target_search_page = $_POST[$option_name];
            $options['target_subscribers_search'] = $target_search_page;
            update_option('target_options', $options);
        }
    }
    wp_redirect(add_query_arg(array('page' => 'target-sub-menu'), admin_url('admin.php')));
    exit;
}
function delete_target_subscriber()
{
    // Check that user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }

    // Check if nonce field is present
    check_admin_referer('target_deletion');

    if (!empty($_POST['subs'])) {
        $subs_to_delete = $_POST['subs'];

        global $wpdb;

        foreach ($subs_to_delete as $sub_to_delete) {
            $query = 'DELETE from ' . $wpdb->get_blog_prefix();
            $query .= 'target_subscribers ';
            $query .= 'WHERE id = %d';
            $wpdb->query($wpdb->prepare($query, intval($sub_to_delete)));
            $s_query = 'DELETE from ' . $wpdb->get_blog_prefix();
            $s_query .= 'target_subscriber_selection ';
            $s_query .= 'WHERE subscriber_id = %d';
            $wpdb->query($wpdb->prepare($s_query, intval($sub_to_delete)));
        }
    }

    // Redirect the page to the user submission form
    wp_redirect(add_query_arg(array('page' => 'target-sub-menu', 'message' => '3'), admin_url('admin.php')));
    exit;
}

function target_subscribers_submenu()
{

    global $wpdb;
    ?>
    <!-- Top-level menu -->
    <div id="target-general" class="wrap">

    <?php if (isset($_GET['message']) && $_GET['message'] == '1') {?>
 <div id='message' class='updated fade'><p><strong>New Subscriber Saved</strong></p></div>
<?php }?>
<?php if (isset($_GET['message']) && $_GET['message'] == '2') {?>
 <div id='message' class='updated fade'><p><strong> Subscriber Already Exists</strong></p></div>
<?php }?>
<?php if (isset($_GET['message']) && $_GET['message'] == '3') {?>
 <div id='message' class='updated fade'><p><strong> Subscriber Successfully Deleted</strong></p></div>
<?php }?>
    <?php
if (empty($_GET['id'])) {
        ?><h2>Subscribers <a class="add-new-h2" href="<?php echo
        add_query_arg(array('page' => 'target-sub-menu',
            'id' => 'new'), admin_url('admin.php')); ?>">
    Add New Subscriber</a> <a class="add-new-h2" href="<?php echo
        add_query_arg(array('page' => 'target-sub-menu',
            'id' => 'export'), admin_url('admin.php')); ?>">
    Export</a></h2>
    <?php
$items_per_page = 20;
        $search_page = 0;

        $options = get_option('target_options');
        $current_page = isset($options['target_subscribers_search']) ? $options['target_subscribers_search'] : 1;
        if (!isset($options['target_subscribers_search']) || empty($options['target_subscribers_search'])) {
            $current_page = 1;
            $options['target_subscribers_search'] = $current_page;
            update_option('target_options', $options);
        }

        $tableName = $wpdb->get_blog_prefix() . 'target_subscribers';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $tableName");
        $last_page = floor($count / $items_per_page) - ($count % $items_per_page == 0 ? 1 : 0) + 1;
        if ($last_page == 0) {
            $last_page = 1;
        }

        if (!isset($current_page)) {
            $current_page = 1;
        }
        if ($current_page > $last_page || $current_page < 0) {
            $current_page = $last_page;
            $options['target_subscribers_search'] = $current_page;
            update_option('target_options', $options);
        }
        $subscriber_query = 'select * from ';
        $subscriber_query .= $wpdb->get_blog_prefix() . 'target_subscribers ';
        $subscriber_query .= 'ORDER by ID DESC ';
        $subscriber_query .= 'LIMIT %d OFFSET %d';
        $subscribers = $wpdb->get_results(
            $wpdb->prepare($subscriber_query, $items_per_page, $items_per_page * ($current_page - 1)), ARRAY_A);

        if ($last_page < 0) {
            $last_page = 0;
        }

        $text_nb_search = isset($options['target_subscribers_search']) ? $options['target_subscribers_search'] : 1;

        ?>
        <div class="target-paginator">

<?php target_button('target_first', '«', "target_pagination");?>
<?php target_button('target_prev', '‹', "target_pagination");?>
<?php target_text('search_page', 3, $text_nb_search);?>
of <?php echo $last_page ?> <?php target_button('target_go', 'Go', "target_pagination", 'search_page');?>
<?php target_button('target_next', '›', "target_pagination");?>
<?php target_button('target_last', '»', "target_pagination");?>

<?php echo $count ?> <?php echo 'subscriber(s) found' ?>


</div>
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
	<input type="hidden" name="action" value="delete_target_subscriber" />

	<!-- Adding security through hidden referrer field -->
	<?php wp_nonce_field('target_deletion');?>
    <input type="submit" value="Delete Selected" class="button-primary"/>
    <br /><br />

<table class="widefat fixed" >
 <thead><tr>
 <th style="width: 50px"></th>
 <th style="width: 80px">ID</th>
 <th style="width: 300px">Email</th>
 <th>Registered</th></tr></thead>
 <?php

        if ($subscribers) {
            foreach ($subscribers as $sub) {
                echo '<tr style="background: #FFF">';
                echo '<td><input type="checkbox" name="subs[]" value="';
                echo esc_attr($sub['id']) . '" /></td>';
                echo '<td>' . $sub['id'] . '</td>';
                echo '<td>' . $sub['email'] . '</td>';
                echo '<td>' . $sub['registered_user_id'] . '</td></tr>';
            }
        } else {
            echo '<tr style="background: #FFF">';
            echo '<td colspan=4>No Subscriber Found</td></tr>';
        }
        ?>
 </table><br />

</form>

 <?php
} elseif (isset($_GET['id']) && ($_GET['id'] == 'new' || is_numeric($_GET['id']))) {

        $subscriber_id = intval($_GET['id']);
        $subscirber_data = array();
        $mode = 'new';
        if ($subscriber_id > 0) {
            $sub_query = 'select * from ' . $wpdb->get_blog_prefix();
            $sub_query .= 'target_subscribers where id = ' . $subscriber_id;
            $subscriber_data = $wpdb->get_row($wpdb->prepare($sub_query),
                ARRAY_A);
            // Set variable to indicate page mode
            if ($subscriber_data) {
                $mode = 'edit';
            }

        } else {
            $subscriber_data['email'] = '';
            $subscriber_data['name'] = '';
            $subscriber_data['status'] = '';
        }
// Display title based on current mode
        if ($mode == 'new') {
            echo '<h3>Add New Subscriber</h3>';
        } elseif ($mode == 'edit') {
            echo '<h3>Edit Subscriber  - ';
            echo $subscriber_data['email'] . '</h3>';
        }
        ?>
 <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
 <input type="hidden" name="action" value="save_target_subscriber" />
 <input type="hidden" name="subscriber_id" value="<?php echo esc_attr($subscriber_id); ?>" />
 <!-- Adding security through hidden referrer field -->
 <?php wp_nonce_field('target_add_edit');?>
 <!-- Display editing form -->
 <table>
 <tr>
 <td style="width: 150px">Email</td>
 <td><input type="text" name="email" size="60" value="<?php echo esc_attr(
            $subscriber_data['email']); ?>"/></td>
 </tr>
 <tr>
 <td>Status</td><td>
 <select name="status">
 <?php
// Display drop-down list of statuses
        // from list in array
        $sub_statuses = array('S' => 'Subscribed', 'U' => 'Unsubscribed');
        foreach ($sub_statuses as $status_mark => $status) {
            // Add selected tag when entry matches
            // existing status
            echo '<option value="' . $status_mark . '" ';
            selected($subscriber_data['status'], $status_mark);
            echo '>' . $status;
        }
        ?>
 </select>
 </td>
 </tr>
 </table>
 <input type="submit" value="Submit" class="button-primary"/>
 </form>
 <?php
} elseif (isset($_GET['id']) && $_GET['id'] == 'export') {
        ?>
    <h2>Export</h2>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="export_target_subscribers" />
    <!-- Adding security through hidden referrer field -->
    <?php wp_nonce_field('target_export');?>
    <!-- Display editing form -->
    <table style="margin-top:20px;">
    <tr>
    <td>Field separator</td><td>
    <select name="separator">
    <?php
// Display drop-down list of statuses
        // from list in array
        $field_sep = array(';' => 'Semicolon', ',' => 'Comma', '\t' => 'Tabulation');
        foreach ($field_sep as $sep_mark => $sep) {
            // Add selected tag when entry matches
            // existing status
            echo '<option value="' . $sep_mark . '" ';
            //selected($subscriber_data['status'], $status_mark);
            echo '>' . $sep;
        }
        ?>
    </select>
    </td>
    </tr>
    </table>

    <h4>Define order and include columns</h4>
<div style="display:inline-block;width: 150px;height:150px;padding: 5px;
  border: 1px solid gray;overflow:auto;box-shadow: 0 0 3px gray;">
<ul id = "sortable-1">
 <li ><input type="checkbox" class="moveCheckbox" value="1"><label>Status</label></li>
 <li><input type="checkbox" class="moveCheckbox" value="2"><label>Subscription date</label></li>
 <!-- <li><input type="checkbox" class="moveCheckbox" value="3"><label>Terms</label></li> -->
 <li><input type="checkbox" class="moveCheckbox" value="4"><label>Term IDs</label></li>
 <li><input type="checkbox" class="moveCheckbox" value="5"><label>Wp subscriber ID</label></li>
      </ul></div><div style="display:inline-block;height:150px;width: 100px;overflow:auto;padding: 5px;">
      <div style="display:flex;justify-content:center;align-items:center;margin:auto auto;height:150px;">
      <input type="button" id="leftall" value="<<"  />
    <input type="button" id="rightall" value=">>" /></div></div>
      <div style="display:inline-block;width: 150px;height:150px;padding: 5px;
  border: 1px solid gray;overflow:auto;box-shadow: 0 0 3px gray;
  ">
      <ul id = "sortable-2">
      <li><input type="checkbox" name="exportField[]" class="moveCheckbox" value="6" checked><label>Email</label></li>
 <li><input type="checkbox" name="exportField[]" class="moveCheckbox" value="7" checked><label>ID</label></li>
 <li><input type="checkbox" name="exportField[]" class="moveCheckbox" value="8" checked><label>Name</label></li>

      </ul></div>

<div>
<br>
    <input type="submit" value="Export" class="button-primary"/></div>
    </form><?php
}
    ?>


    </div>
    <script type="text/javascript">
jQuery(document).ready( function($) {
    $('table#tesst tbody').sortable({
  classes: {
    "ui-sortable": "highlight"
  }
});

            $( "#sortable-1" ).sortable();
            $( "#sortable-2" ).sortable();
            var buttons = document.querySelectorAll(".moveBtn");
            var inputs = document.querySelectorAll(".moveCheckbox");
            var list1 = document.getElementById("sortable-1");
    var list2 = document.getElementById("sortable-2");
    function moveItemChecked(e) {
        var moveCheckedTo = this.parentElement.parentElement == list1 ? list2 : list1;
        moveCheckedTo.appendChild(this.parentElement);
        if(this.getAttribute('name')!=null)
        this.removeAttribute('name');
        else this.name="exportField[]";
    }
    function moveAllItems(origin, dest) {
    $(origin).children().appendTo(dest);
}

    for (var i = 0; i < inputs.length; i++) {
        inputs[i].addEventListener("change",moveItemChecked);
    }
    $('#rightall').on("click",function(){
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].checked =  true;
    }
        moveAllItems('#sortable-1', '#sortable-2');

    });
    $('#leftall').on("click",function(){
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].checked = false;
    }
        moveAllItems('#sortable-2', '#sortable-1');

    });

});
</script>
    <?php
}
function process_target_subscriber()
{
    // Check if user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    // Check if nonce field is present for security
    check_admin_referer('target_add_edit');
    global $wpdb;
    $subscriber_data = array();
    $subscriber_data['email'] = (isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '');
    $subscriber_data['status'] = (isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '');

    $tableName = $wpdb->get_blog_prefix() . 'target_subscribers';
    $add_message = '0';
    if (isset($_POST['subscriber_id']) && 0 == $_POST['subscriber_id']) {

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tableName WHERE email = %s", $_POST['email']
        ));
        if (!$exists) {
            $subscriber_data['created'] = date('Y-m-d');
            $hey = $wpdb->insert($tableName, $subscriber_data);
            $add_message = '1';
        } else {
            $add_message = '2';
        }
    } elseif (isset($_POST['subscriber_id']) && $_POST['subscriber_id'] > 0) {
        $subscriber_data['updated'] = date('Y-m-d');
        $wpdb->update($tableName, $subscriber_data, array('id' => $_POST['subscriber_id']));
    }
    wp_redirect(add_query_arg(array('page' => 'target-sub-menu', 'message' => $add_message), admin_url('admin.php')));
    exit;

}
function export_target_subscribers()
{
    // Check that user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }

    // Check if nonce field is present
    check_admin_referer('target_export');
    ob_start();

    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'subscribers-' . $domain . '-' . time() . '.csv';

    $data_rows = array();
    global $wpdb;
    $sql = 'SELECT * FROM ' . $wpdb->get_blog_prefix();
    $sql .= 'target_subscribers';
    $subscribers = $wpdb->get_results($sql, 'ARRAY_A');

    $replacements = array(
        '6' => 'email',
        '7' => 'id',
        '8' => 'name',
        '1' => 'status',
        '2' => 'created',
        '5' => 'registered_user_id',
        '3' => 'terms',
        '4' => 'terms',
    );
    if (isset($_POST['exportField'])) {
        $fields = $_POST['exportField'];
        if (in_array('3', $fields) || in_array('4', $fields)) {
            $sql_terms = 'SELECT * FROM ' . $wpdb->get_blog_prefix();
            $sql_terms .= 'target_subscriber_selection';
            $sub_terms = $wpdb->get_results($sql_terms, 'ARRAY_A');
            $grouped_terms = array();
            foreach ($sub_terms as $key => $item) {
                $grouped_terms[$item['subscriber_id']][] = $item['term_id'];
            }
            foreach ($grouped_terms as $key => $item) {
                $grouped_terms[$key] = implode(';', $grouped_terms[$key]);
            }
        }
        foreach ($fields as $key => $value) {
            if (isset($replacements[$value])) {
                if ($value !== '3' && $value !== '4') {
                    $fields[$key] = $replacements[$value];
                }

            }
        }
    } else {
        $fields = array('email');
    }

    $header_row = array_values($fields);
    foreach ($subscribers as $subscriber) {
        $row = array();
        foreach ($fields as $key => $value) {
            if ($value !== '3' && $value !== '4') {
                $row[] = $subscriber[$value];
            } else {
                if (isset($grouped_terms)) {
                    $row[] = $grouped_terms[$subscriber['id']];
                }

            }
        }
        $data_rows[] = $row;
    }
    $delimiter = (isset($_POST['separator']) ? sanitize_text_field($_POST['separator']) : ';');
    $fh = @fopen('php://output', 'w');
    fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-type: text/csv');
    header("Content-Disposition: attachment; filename={$filename}");
    header('Expires: 0');
    header('Pragma: public');
    fputcsv($fh, $header_row);
    foreach ($data_rows as $data_row) {
        fputcsv($fh, $data_row, $delimiter);
    }
    fclose($fh);

    ob_end_flush();

}