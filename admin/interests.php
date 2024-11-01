<?php

defined('ABSPATH') || exit;

function target_admin_form_tabs($current = 'categories')
{
    $tabs = array('categories' => 'Categories', 'tags' => 'Tags');
    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab => $name) {
        $class = ($tab == $current) ? 'nav-tab nav-tab-active' : "nav-tab";
        echo "<a class='$class' href='?page=target-menu-select&tab=$tab'>$name</a>";

    }
    echo '</h2>';
}
function target_selection_submenu()
{
    global $wpdb;
    ?>
    <?php if (isset($_GET['message']) && $_GET['message'] == '1') {?>
 <div id='message' class='updated fade'><p><strong>Options
 Saved</strong></p></div>
<?php }?>
    <!-- Top-level menu -->
    <div id="target-general" class="wrap">
    <h2>User interests </h2>
    <?php if (isset($_GET['tab'])) {
        target_admin_form_tabs($_GET['tab']);
    } else {
        target_admin_form_tabs('categories');
    }
    ?>
    <div class="category_options_form">
    <form method="post" id="targetcatoptions" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="save_target_categories_options" />
    <?php if (isset($_GET['tab'])) {
        $tab = $_GET['tab'];
    } else {
        $tab = 'categories';
    }
    ?>
    <input type="hidden" name="target_get_tab" value="<?php echo esc_html($tab); ?>" />
    <table class="form-table">
    <!-- <form method="post" action="admin-post.php">
    <table class="form-table">
 <input type="hidden" name="action" value="save_target_user_selection_options" /> -->
 <!-- Adding security through hidden referrer field -->
 <!-- <?php //wp_nonce_field('target_selection');?> -->
 <?php
wp_nonce_field('target_catoptions_form');
    global $wpdb;
    $options = get_option('target_options');
    switch ($tab) {
        case 'categories':
            ?>
 <tr style="vertical-align:bottom">
 <th scope="row">Categories </th>
 <td>
 <?php

            echo target_categ_tree(0, '');
            ?>
</td>
</tr>
<tr style="vertical-align:bottom">
 <th scope="row">Show descriptions </th>
 <td>
 <label  style="margin-right: 4em;">
 <input type="radio" name="show_cat_descriptions" value="0"
 <?php checked(true, isset($options['show_cat_descriptions']) ? $options['show_cat_descriptions'] : true);
            ?>>Yes</input></label>
    <label ><input type="radio" name="show_cat_descriptions" value="1"
 <?php checked(false, isset($options['show_cat_descriptions']) ? $options['show_cat_descriptions'] : true);
            ?>>No</input></label>
</td>
</tr>
<tr style="vertical-align:bottom">
 <th scope="row">Show frequency </th>
 <td>
 <label  style="margin-right: 4em;">
 <input type="radio" name="show_cat_frequency" value="0"
 <?php checked(true, isset($options['show_cat_frequency']) ? $options['show_cat_frequency'] : true);
            ?>>Yes</input></label>
    <label ><input type="radio" name="show_cat_frequency" value="1"
 <?php checked(false, isset($options['show_cat_frequency']) ? $options['show_cat_frequency'] : true);
            ?>>No</input></label>
</td>
</tr>
</table>
    <?php submit_button();?>
    </form>
<?php break;
        case 'tags':
            ?>
<th scope="row">Tags </th>
 <td>
 <?php
$post_tags = get_terms(array('taxonomy' => 'post_tag',
                'hide_empty' => false));
            $output = '';
            if ($post_tags) {

                foreach ($post_tags as $tag) {
                    if (!isset($options['tagselected'])) {
                        $selected = "";
                    } else {
                        $selected = (in_array($tag->term_id, $options['tagselected'])) ? " checked" : "";
                    }
                    $output .= '<div style="padding-bottom:6px;">';
                    $output .= '<label class="tag-checkbox"><input type="checkbox" value="' . esc_attr($tag->term_id) . '" ' . $selected . ' name=tagselected[]>';
                    $output .= '<span class="category-span"></span>';
                    $output .= '<strong> ' . $tag->name . '</strong></label>';
                    $output .= '<br>';
                    $desc_class = " tag_description " . esc_attr($tag->term_id);
                    $style = $selected ? "margin-top:12px;" : "margin-top:12px;display:none";
                    if (!isset($options['target_tag_desc'])) {
                        $desc_value = $tag->description;
                    } else {
                        if (isset($options['target_tag_desc'][$tag->term_id])) {
                            $desc_value = $options['target_tag_desc'][$tag->term_id];
                        } else {
                            $desc_value = $tag->description;
                        }
                    }
                    if (!isset($options['target_tag_frequency'])) {
                        $frequency_value = 'Weekly';
                    } else {
                        if (isset($options['target_tag_frequency'][$tag->term_id])) {
                            $frequency_value = $options['target_tag_frequency'][$tag->term_id];
                        } else {
                            $frequency_value = 'Weekly';
                        }
                    }
                    $output .= '<input style="' . $style . '" size="60" class="' . $desc_class . '" type="text" value="' . $frequency_value . '" name="target_tag_frequency[' . esc_attr($tag->term_id) . ']"/>';
                    $output .= '<br>';
                    $output .= '<input style="' . $style . '" size="60" class="' . $desc_class . '" type="text" value="' . $desc_value . '" name="target_tag_desc[' . esc_attr($tag->term_id) . ']"/>';
                    $output .= '<br>';
                    $output .= '</div>';
                }
                echo $output;
            } else {
                echo 'No tags found'
                ;
            }
            ?>
</td>
</tr>
<tr style="vertical-align:bottom">
 <th scope="row">Show descriptions </th>
 <td>
 <label  style="margin-right: 4em;">
 <input type="radio" name="show_tag_descriptions" value="0"
 <?php checked(true, isset($options['show_tag_descriptions']) ? $options['show_tag_descriptions'] : true);
            ?>>Yes</input></label>
    <label ><input type="radio" name="show_tag_descriptions" value="1"
 <?php checked(false, isset($options['show_tag_descriptions']) ? $options['show_tag_descriptions'] : true);
            ?>>No</input></label>
</td>
</tr>
<tr style="vertical-align:bottom">
 <th scope="row">Show frequency </th>
 <td>
 <label  style="margin-right: 4em;">
 <input type="radio" name="show_tag_frequency" value="0"
 <?php checked(true, isset($options['show_tag_frequency']) ? $options['show_tag_frequency'] : true);
            ?>>Yes</input></label>
    <label ><input type="radio" name="show_tag_frequency" value="1"
 <?php checked(false, isset($options['show_tag_frequency']) ? $options['show_tag_frequency'] : true);
            ?>>No</input></label>
</td>
</tr>

</table>
    <?php submit_button();
            ?>
    </form>
    <?php break;
        case 'integrations':
            target_selection_submenu_integrations();
            break;
    }
    ?>
    </div>
</div>
<?php

}
function target_categ_tree($catId, $depth)
{
    $depth .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
    if ($catId == 0) {
        $depth = '';
    }

    $output = '';
    $args = 'hierarchical=1&taxonomy=category&hide_empty=0&parent=';
    $categories = get_categories($args . $catId);

    if (count($categories) > 0) {
        $options = get_option('target_options');
        foreach ($categories as $category) {
            if (!isset($options['catselected'])) {
                $selected = "";
            } else {
                $selected = (in_array($category->term_id, $options['catselected'])) ? " checked" : "";
            }

            $output .= '<div  style="padding-bottom:6px;">';
            $output .= $depth . '<label class="cat-checkbox"><input type="checkbox" value="' . esc_attr($category->cat_ID) . '" ' . $selected . ' name=catselected[]>';
            $output .= '<span class="category-span"></span>';
            //$r .= '<label for="' . esc_attr($cat->slug) . '">';
            $output .= '<strong> ' . $category->cat_name . '</strong></label>';
            $output .= '<br>';
            $desc_class = " cat_description " . esc_attr($category->cat_ID);
            $style = $selected ? "margin-top:12px;" : "margin-top:12px;display:none";
            if (!isset($options['target_cat_desc'])) {
                $desc_value = $category->description;
            } else {
                if (isset($options['target_cat_desc'][$category->cat_ID])) {
                    $desc_value = $options['target_cat_desc'][$category->cat_ID];
                } else {
                    $desc_value = $category->description;
                }
            }
            if (!isset($options['target_cat_frequency'])) {
                $frequency_value = 'Weekly';
            } else {
                if (isset($options['target_cat_frequency'][$category->cat_ID])) {
                    $frequency_value = $options['target_cat_frequency'][$category->cat_ID];
                } else {
                    $frequency_value = 'Weekly';
                }
            }
            $output .= '<input style="' . $style . '" size="60" class="' . $desc_class . '" type="text" value="' . $desc_value . '" name="target_cat_desc[' . esc_attr($category->cat_ID) . ']"/>';
            $output .= '<br>';
            $output .= '<input style="' . $style . '" size="20" class="' . $desc_class . '" type="text" value="' . $frequency_value . '" name="target_cat_frequency[' . esc_attr($category->cat_ID) . ']"/>';
            $output .= '<br>';
            $output .= '</div>';
            $output .= target_categ_tree($category->cat_ID, $depth);
        }
    }
    return $output;
}
function process_target_categories_options()
{
    // Check that user has proper security level
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    // Check that nonce field created in configuration form
    // is present
    check_admin_referer('target_catoptions_form');
    // Retrieve original plugin options array
    $options = get_option('target_options');
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    if (isset($_POST['target_get_tab'])) {
        $tab = $_POST['target_get_tab'];
    } else {
        $tab = 'categories';
    }

    switch ($tab) {
        case 'categories':
            foreach (array('catselected', 'target_cat_desc', 'target_cat_frequency') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = filter_var_array($_POST[$option_name], FILTER_SANITIZE_STRING);
                    //$options[$option_name] = $_POST[$option_name];
                }
            }
            foreach (array('show_cat_descriptions', 'show_cat_frequency') as $option_name) {
                if (isset($_POST[$option_name]) && $_POST[$option_name] === '0') {
                    $options[$option_name] = true;
                } else {
                    $options[$option_name] = false;
                }
            }
            break;
        case 'tags':
            foreach (array('tagselected', 'target_tag_desc', 'target_tag_frequency') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = filter_var_array($_POST[$option_name], FILTER_SANITIZE_STRING);
                    //$options[$option_name] = $_POST[$option_name];
                }
            }
            foreach (array('show_tag_descriptions', 'show_tag_frequency') as $option_name) {
                if (isset($_POST[$option_name]) && $_POST[$option_name] === '0') {
                    $options[$option_name] = true;
                } else {
                    $options[$option_name] = false;
                }
            }
            break;
        case 'integrations':
            foreach (array('target_form_integration') as $option_name) {
                if (isset($_POST[$option_name])) {
                    $options[$option_name] = sanitize_text_field($_POST[$option_name]);
                }

            }
            break;
    }
    update_option('target_options', $options);
    if (isset($options['use_mailchimp']) && $options['use_mailchimp'] == true) {
        //start batch
        $api_key = $options['mailchimp_key'];
        $dc = substr($options['mailchimp_key'], strpos($options['mailchimp_key'], '-') + 1);
        $list_id = array_keys($options['mailchimp_list'])[0];
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
            ),
            'body' => json_encode([
                'title' => "tn_post_categories2",
                'type' => "hidden",
            ]),
        );
        $operations = [];
        $body_args = new stdClass();
        $body_args->operations = array();
        $interest_category_id = '';
        //if category group created(store), don't, else add post new cat to operation
        if (!isset($options['mailchimp_group_created']) || empty($options['mailchimp_group_created'])) {
            $group_response = wp_remote_post('https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $list_id . '/interest-categories', $args);
            $group_body = json_decode($group_response['body']);
            if ($group_response['response']['code'] == 200) {
                $interest_category_id = $group_body->id;
                $options['mailchimp_group_created'] = $group_body->id;

                update_option('target_options', $options);
            }
        } else {
            $interest_category_id = $options['mailchimp_group_created'];
        }
        if (!empty($interest_category_id)) {
            foreach ($options['catselected'] as $key => $value) {
                $batch = new stdClass();
                $batch->method = 'POST';
                $batch->path = 'lists/' . $list_id . '/interest-categories/' . $interest_category_id . '/interests';
                // $batch->operation_id = $key;
                $batch->body = json_encode(array(
                    'name' => get_category($value)->name,
                    //'name' => 'hello',

                ));
                $body_args->operations[] = $batch;
            }
            $batch_args = array(
                'method' => 'POST',
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                ),
                'body' => json_encode($body_args),
            );
            $response = wp_remote_post('https://' . $dc . '.api.mailchimp.com/3.0/batches/', $batch_args);
            $body = json_decode($response['body']);
            if ($response['response']['code'] == 200) {
                $options['mailchimp_update_saved'] = false;
                update_option('target_options', $options);
            }
        }
    }
    // Redirect the page to the configuration form that was
    // processed
    // wp_redirect(add_query_arg(array('page' => 'target-sub-menu-select', 'message' => '1'),
    //     admin_url('admin.php')));
    $url_parameters = isset($_POST['target_get_tab']) ? 'message=1&tab=' . $_POST['target_get_tab'] : 'message=1';
    wp_redirect(admin_url('admin.php?page=target-menu-select&' . $url_parameters));
    exit;
}