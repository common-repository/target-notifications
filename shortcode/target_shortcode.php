<?php

add_action('wp_ajax_add_form_selection', 'add_form_selection');
add_action('wp_ajax_nopriv_add_form_selection', 'add_form_selection');
add_shortcode('target-categories-form', 'target_select_interests');
add_shortcode('target-tags-form', 'target_select_tags');
add_action('wp_enqueue_scripts', 'target_adding_scripts_sh');
function target_adding_scripts_sh()
{
    //wp_enqueue_style('selection_style', plugins_url('stylesheet.css', __FILE__));
    $custom_css_ver = date("ymd-Gis", filemtime(plugin_dir_path(__FILE__) . 'stylesheet.css'));
    wp_enqueue_style('selection_style', plugin_dir_url(__FILE__) . 'stylesheet.css', array(), $custom_css_ver);
}
function target_select_interests($atts)
{
    // begin output buffering
    ob_start();
    $cats = '';
    $cats = hierarchical_term_tree();

    ?>
    <div class="subscription_selection_form" style="max-width: 100% !important;">
    <form method="post" id="targetshortcode" action="<?php echo admin_url('admin-ajax.php'); ?>">
    <?php wp_nonce_field('target_ajax_form', 'target_user_form');?>
<?php echo $cats; ?>
    <!-- <label for="target_email"> Email</label>
    <br /> -->

    <input type="email" name="email" id="target_email" placeholder="Enter your email address..." size="30"/>
<input type="hidden" name="action" value="add_form_selection">
<input  id="subscribe_button" type="submit" name="subscribe" value="Subscribe" style="margin-top:16px;"/>
</form>
</div>
<script type='text/javascript'>

jQuery(document).ready(function($){
$('#targetshortcode').ajaxForm({
success:function(response){
console.log("restponse",response);
    jQuery('.subscription_selection_form').html( response );
},error:function(response){
console.log("res",response);}
});
});
</script>

 <?php
// end output buffering, grab the buffer contents, and empty the buffer
    return ob_get_clean();
}
function target_select_tags($atts)
{
    // begin output buffering
    ob_start();
    $tags = '';
    $tags = tags_form();

    ?>
    <div class="subscription_selection_form" style="max-width: 100% !important;">
    <form method="post" id="targetshortcode" action="<?php echo admin_url('admin-ajax.php'); ?>">
    <?php wp_nonce_field('target_ajax_form', 'target_user_form');?>
<?php echo $tags; ?>
    <!-- <label for="target_email"> Email</label>
    <br /> -->

    <input type="email" name="email" id="target_email" placeholder="Enter your email address..." size="30"/>
<input type="hidden" name="action" value="add_form_selection">
<input  id="subscribe_button" type="submit" name="subscribe" value="Subscribe" />
</form>
</div>
<script type='text/javascript'>

jQuery(document).ready(function($){
$('#targetshortcode').ajaxForm({
success:function(response){
console.log("restponse",response);
    jQuery('.subscription_selection_form').html( response );
},error:function(response){
console.log("res",response);}
});
});
</script>

 <?php
// end output buffering, grab the buffer contents, and empty the buffer
    return ob_get_clean();
}

function tags_form()
{
    $r = '';
    $next = get_terms(array('taxonomy' => 'post_tag',
        'hide_empty' => false));

    if ($next) {
        //$r .= '<ul>';
        $options = get_option('target_options');
        $numOfCols = 2;
        $rowCount = 0;
        //$bootstrapColWidth = 12 / $numOfCols;
        $bootstrapColWidth = 6;
        //$r .= '<div style="background-color:orange; display: grid;grid-template-columns:  1fr 1fr ;  grid-gap: 10px;">';
        //$r .= '<ul class="cat_body" style="margin:2px;float:left;">';
        $r .= '<ul class="cat_body" style="margin:2px;display:flex;flex-wrap: wrap;">';
        $tagselected = isset($options['tagselected']) ? $options['tagselected'] : target_extract_terms(get_terms(array('taxonomy' => 'post_tag',
            'hide_empty' => false)));
        foreach ($next as $tag) {
            if (in_array($tag->term_id, $tagselected)) {
                // if ($rowCount % $numOfCols == 0) {
                //     $r .= '<div style="display: flex;
                //     justify-content: center;background-color:green;">';
                //     $r .= '<div class="left" style="width: 50%;background-color:orange;">';
                // } else {
                //     $r .= '<div class="right" style="width: 50%;background-color:gold;">';
                // }
                // $r .= '<div style="display: flex;
                //      justify-content: center;background-color:green;">';
                // if ($rowCount % $numOfCols !== 0) {
                //     $r .= '<br>';
                // }

                //$r .= '<div style="border: blue 3px dashed;  padding: 25px;">';
                $rowCount++;
                //$r .= '  <div class="col-md-' . $bootstrapColWidth . '">';
                //$r .= '<li class="catitem" style=" float:left;width: 50%;list-style-type:none;border-bottom: 1px solid #696969;">';
                $r .= '<li class="catitem" style=" flex: 1 0 45%;list-style-type:none;margin:10px;">';
                $r .= '<label>';
                $r .= '<input class="category-checkbox" type="checkbox" id="' . esc_attr($tag->slug) . '" name="interests[]" value="' . esc_attr($tag->term_id) . '">';
                $r .= '<span class="category-span"></span>';
                //$r .= '<label for="' . esc_attr($cat->slug) . '">';
                $r .= '<strong style="font-size:28px;"> ' . $tag->name . '</strong></label>';
                if (!isset($options['show_tag_frequency']) || $options['show_tag_frequency'] === true) {

                    if (!isset($options['target_tag_frequency'])) {
                        $freq_value = 'Weekly';
                    } else {
                        if (isset($options['target_tag_frequency'][$tag->term_id])) {
                            $freq_value = $options['target_tag_frequency'][$tag->term_id];
                        } else {
                            $freq_value = 'Weekly';
                        }
                    }
                    $r .= '<div style="color:#ec0101;font-size:17px;font-weight:bold;">' . $freq_value . '</div>';

                }

                if (!isset($options['show_tag_descriptions']) || $options['show_tag_descriptions'] === true) {

                    if (!isset($options['target_tag_desc'])) {
                        $desc_value = $tag->description;
                    } else {
                        if (isset($options['target_tag_desc'][$tag->term_id])) {
                            $desc_value = $options['target_tag_desc'][$tag->term_id];
                        } else {
                            $desc_value = $tag->description;
                        }
                    }
                    $r .= '<div>' . $desc_value . '</div>';
                }

                //$r .= '</div>';
                //$r .= '<br>';
                // if ($rowCount % $numOfCols !== 0) {
                //     $r .= '</div>';
                // }

                $parent = true;
                //$r .= $cat->term_id !== 0 ? hierarchical_term_tree($cat->term_id) : null;
            } else {

                $parent = false;
            }
        }
        $r .= '</ul>';
        //$r .= '</div>';
        //$r .= '<br>';

        // $r .= '</ul>';
    }
    return $r;
}
function hierarchical_term_tree($category = 0, $depth = '', $parent = true)
{
    $r = '';
    $next = get_terms(array('taxonomy' => 'category',
        'parent' => $category,
        'hide_empty' => false));
    $depth .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
    //$depth .= 'ss';
    if ($category == 0) {
        $depth = '';
    }

    if ($parent == false) {
        $pos = stripos($depth, "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp");

        if ($pos !== false && is_numeric($pos)) {
            $depth = substr_replace($depth, '', $pos, strlen('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp'));
        }
    }
    if ($next) {
        //$r .= '<ul>';
        $options = get_option('target_options');
        $numOfCols = 2;
        $rowCount = 0;
        //$bootstrapColWidth = 12 / $numOfCols;
        $bootstrapColWidth = 6;
        //$r .= '<div style="background-color:orange; display: grid;grid-template-columns:  1fr 1fr ;  grid-gap: 10px;">';
        //$r .= '<ul class="cat_body" style="margin:2px;float:left;">';
        $r .= '<ul class="cat_body" style="margin:2px;display:flex;flex-wrap: wrap;">';

        $catselected = isset($options['catselected']) ? $options['catselected'] : target_extract_terms(get_terms(
            'category',
            array('parent' => 0)
        ));
        foreach ($next as $cat) {
            if (in_array($cat->term_id, $catselected)) {
                // if ($rowCount % $numOfCols == 0) {
                //     $r .= '<div style="display: flex;
                //     justify-content: center;background-color:green;">';
                //     $r .= '<div class="left" style="width: 50%;background-color:orange;">';
                // } else {
                //     $r .= '<div class="right" style="width: 50%;background-color:gold;">';
                // }
                // $r .= '<div style="display: flex;
                //      justify-content: center;background-color:green;">';
                // if ($rowCount % $numOfCols !== 0) {
                //     $r .= '<br>';
                // }

                //$r .= '<div style="border: blue 3px dashed;  padding: 25px;">';
                $rowCount++;
                //$r .= '  <div class="col-md-' . $bootstrapColWidth . '">';
                //$r .= '<li class="catitem" style=" float:left;width: 50%;list-style-type:none;border-bottom: 1px solid #696969;">';
                $r .= '<li class="catitem" style=" flex: 1 0 45%;list-style-type:none;margin:10px;">';
                $r .= $depth . '<label>';
                $r .= '<input class="category-checkbox" type="checkbox" id="' . esc_attr($cat->slug) . '" name="interests[]" value="' . esc_attr($cat->term_id) . '">';
                $r .= '<span class="category-span"></span>';
                //$r .= '<label for="' . esc_attr($cat->slug) . '">';
                $r .= '<strong style="font-size:28px;"> ' . $cat->name . '</strong></label>';
                if (!isset($options['show_cat_frequency']) || $options['show_cat_frequency'] === true) {

                    if (!isset($options['target_cat_frequency'])) {
                        $freq_value = 'Weekly';
                    } else {
                        if (isset($options['target_cat_frequency'][$cat->term_id])) {
                            $freq_value = $options['target_cat_frequency'][$cat->term_id];
                        } else {
                            $freq_value = 'Weekly';
                        }
                    }
                    $r .= '<div style="color:#ec0101;font-size:17px;font-weight:bold;">' . $freq_value . '</div>';
                }
                if (!isset($options['show_cat_descriptions']) || $options['show_cat_descriptions'] === true) {

                    if (!isset($options['target_cat_desc'])) {
                        $desc_value = $cat->description;
                    } else {
                        if (isset($options['target_cat_desc'][$cat->term_id])) {
                            $desc_value = $options['target_cat_desc'][$cat->term_id];
                        } else {
                            $desc_value = $cat->description;
                        }
                    }
                    $r .= '<div>' . $desc_value . '</div>';
                }

                //$r .= '</div>';
                //$r .= '<br>';
                // if ($rowCount % $numOfCols !== 0) {
                //     $r .= '</div>';
                // }

                $parent = true;
                //$r .= $cat->term_id !== 0 ? hierarchical_term_tree($cat->term_id) : null;
            } else {

                $parent = false;
            }
            $r .= hierarchical_term_tree($cat->term_id, $depth, $parent);
        }
        $r .= '</ul>';
        //$r .= '</div>';
        //$r .= '<br>';

        // $r .= '</ul>';
    }
    return $r;
}

function add_form_selection()
{
    //wp_send_json_success($_POST["email"]);
    check_ajax_referer('target_ajax_form', 'target_user_form');
    $options = get_option('target_options');
    if (isset($options['use_mailchimp']) && $options['use_mailchimp'] == true) {
        if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $api_key = $options['mailchimp_key'];
            $dc = substr($api_key, strpos($api_key, '-') + 1);
            $list_id = array_keys($options['mailchimp_list'])[0];
            $email = $_POST['email'];
            $status = 'subscribed'; // subscribed, cleaned, pending, unsubscribed
            if (isset($options['mailchimp_update_saved']) && $options['mailchimp_update_saved'] == false) {
                $interest_category_id = $options['mailchimp_group_created'];
                $args = array(
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode('user:' . $options['mailchimp_key']),
                    ),
                );
                $response = wp_remote_get('https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $list_id . '/interest-categories/' . $interest_category_id . '/interests', $args);
                $body = json_decode($response['body']);
                $mailchimp_interests = target_convert_mailchimp($body->interests);
                $options['mailchimp_interests'] = $mailchimp_interests;
                $options['mailchimp_update_saved'] = true;
                update_option('target_options', $options);
            }
            $interests_selected = $_POST['interests'];
            $interests = isset($options['mailchimp_interests']) ? target_interest_format($options['mailchimp_interests'], $interests_selected) : '';
            $args = array(
                'method' => 'PUT',
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                ),
                'body' => json_encode(array(
                    'email_address' => $email,
                    'status' => $status,
                    'interests' => $interests,
                )),
            );
            $response = wp_remote_post('https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($email)), $args);
            $body = json_decode($response['body']);

            if ($response['response']['code'] == 200 && $body->status == $status) {
                // target_log_file('The user has been successfully ' . $status . '.', 'response');
                $output = ' <div style="margin: 8px; ">
                             Your subscription has been updated!
                             </div>';
                echo $output;
            } else {
                $output = ' <div style="margin: 8px; ">
                Something went wrong. Please try again later.
                                 </div>';
                echo $output;
                //target_log_file('<b>' . $response['response']['code'] . $body->title . ':</b> ' . $body->detail, 'else');
            }
        }
        wp_die();
    } else {
        if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            global $wpdb;
            $email = sanitize_text_field($_POST["email"]);
            $tableName = $wpdb->get_blog_prefix() . 'target_subscribers';
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tableName WHERE email = %s", $_POST['email']
            ));
            if (!$exists) {
                $insert_row = $wpdb->insert(
                    $tableName,
                    array(
                        'email' => $email,
                        'created' => date('Y-m-d'),
                        'registered_user_id' => get_current_user_id(),
                    )
                );

// if row inserted in table
                if ($insert_row) {
                    $id = $wpdb->insert_id;
                    if (!empty($_POST['interests'])) {
                        $interests_selected = $_POST['interests'];
                        $tableNameCategories = $wpdb->get_blog_prefix() . 'target_subscriber_selection';
                        foreach ($interests_selected as $interest_selected) {
                            $insert_category_row = $wpdb->insert(
                                $tableNameCategories,
                                array(
                                    'subscriber_id' => $id,
                                    'term_id' => $interest_selected,
                                )
                            );
                        }
                    }
                    $output = ' <div style="margin: 8px; ">
                             Your subscription has been confirmed!
                             </div>';
                    echo $output;
                    //echo "Your subscription has been confirmed!";
                    //echo json_encode(array('res' => true, 'message' => __('New row has been inserted.')));
                } else {
                    $output = ' <div style="margin: 8px; ">
                Something went wrong. Please try again later.
                                 </div>';
                    echo $output;
                    //echo json_encode(array('res' => false, 'message' => __('Something went wrong. Please try again later.')));
                }
            } else {
                //target_log_file($exists);
                $id = $exists;
                if (!empty($_POST['interests'])) {
                    $interests_selected = $_POST['interests'];
                    $tableNameCategories = $wpdb->get_blog_prefix() . 'target_subscriber_selection';
                    foreach ($interests_selected as $interest_selected) {
                        $insert_category_row = $wpdb->replace(
                            $tableNameCategories,
                            array(
                                'subscriber_id' => $id,
                                'term_id' => $interest_selected,
                            )
                        );
                    }
                }
                $output = ' <div style="margin: 8px; ">
                             Your subscription has been updated!
                             </div>';
                echo $output;
            }
            wp_die();
        }
    }
}
