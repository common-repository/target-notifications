<?php

add_action('widgets_init', 'target_create_widgets');
add_action('wp_ajax_add_subscription', 'add_subscription');
add_action('wp_ajax_nopriv_add_subscription', 'add_subscription');


function target_create_widgets()
{
    register_widget('Target_Notifications_Widget');
}

class Target_Notifications_Widget extends WP_Widget
{
    // Construction function
    public function __construct()
    {
        parent::__construct('target_notifications', 'Target Notifications',
            array('description' =>
                'Displays subscription form'));
    }

    public function form($instance)
    {
        // Retrieve previous values from instance
        // or set default values if not present
        $render_widget = (!empty($instance['render_widget']) ?
            $instance['render_widget'] : 'true');
        $widget_title = (!empty($instance['widget_title']) ?
            esc_attr($instance['widget_title']) :
            'Newsletter');
        $widget_introduction = (!empty($instance['widget_introduction']) ?
            esc_attr($instance['widget_introduction']) :
            '');
        $widget_button = (!empty($instance['widget_button']) ?
            esc_attr($instance['widget_button']) :
            'Subscribe');
        $textbox_size = (!empty($instance['textbox_size']) ?
            $instance['textbox_size'] : 30);
        ?>
    <!-- Display fields  -->
    <p>
    <label for="<?php echo $this->get_field_id('render_widget'); ?>">
    <?php echo 'Display subscription form'; ?>
    <select id="<?php echo $this->get_field_id('render_widget'); ?>"
        name="<?php echo $this->get_field_name('render_widget'); ?>">
    <option value="true" <?php selected($render_widget, 'true');?>>
    Yes</option>
    <option value="false" <?php selected($render_widget, 'false');?>>
    No</option>
    </select>
    </label>
    </p>
    <p>
    <label for="<?php echo $this->get_field_id('widget_title'); ?>">
    <?php echo 'Title:'; ?>
    <input type="text" id="<?php echo $this->get_field_id('widget_title'); ?>"
    name="<?php echo $this->get_field_name('widget_title'); ?>"
    value="<?php echo $widget_title; ?>" />
    </label>
    </p>
    <p>
    <label for="<?php echo
        $this->get_field_id('widget_introduction'); ?>">
    <?php echo 'Introduction:'; ?>
    <input type="text"
    id="<?php echo $this->get_field_id('widget_introduction'); ?>"
    name="<?php echo $this->get_field_name('widget_introduction'); ?>"
    value="<?php echo $widget_introduction; ?>" />
    </label>
    </p>
    <p>
    <label for="<?php echo
        $this->get_field_id('widget_button'); ?>">
    <?php echo 'Button text:'; ?>
    <input type="text"
    id="<?php echo $this->get_field_id('widget_button'); ?>"
    name="<?php echo $this->get_field_name('widget_button'); ?>"
    value="<?php echo $widget_button; ?>" />
    </label>
    </p>
    <p>
    <label for="<?php echo
        $this->get_field_id('textbox_size'); ?>">
    <?php echo 'Textbox size:'; ?>
    <input type="text"
    id="<?php echo $this->get_field_id('textbox_size'); ?>"
    name="<?php echo $this->get_field_name('textbox_size'); ?>"
    value="<?php echo $textbox_size; ?>" />
    </label>
    </p>
    
    <?php
}

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        // Only allow numeric values
        if (is_numeric($new_instance['textbox_size'])) {
            $instance['textbox_size'] = intval($new_instance['textbox_size']);
        } else {
            $instance['textbox_size'] = $instance['textbox_size'];
        }

        $instance['widget_title'] = strip_tags($new_instance['widget_title']);
        $instance['render_widget'] = strip_tags($new_instance['render_widget']);
        $instance['widget_introduction'] = strip_tags($new_instance['widget_introduction']);
        $instance['render_widget'] = strip_tags($new_instance['render_widget']);
        return $instance;
    }

    public function widget($args, $instance)
    {
        if ($instance['render_widget'] == 'true') {
            // Extract members of args array as individual variables
            extract($args);
            // Retrieve widget configuration options
            $textbox_size = (!empty($instance['textbox_size']) ? $instance['textbox_size'] : 30);
            $widget_title = (!empty($instance['widget_title']) ? esc_attr($instance['widget_title']) :
                'Newsletter');
            $widget_button = (!empty($instance['widget_button']) ? esc_attr($instance['widget_button']) :
                'Subscribe');
            $widget_introduction = (!empty($instance['widget_introduction']) ?
                esc_attr($instance['widget_introduction']) :
                '');
            // Display widget title
            echo $before_widget;
            echo $before_title;
            echo apply_filters('widget_title', $widget_title);
            echo $after_title;
            $input_form_action = '<input  id="subscribe_btn" type="submit" name="subscribe" value="' . $widget_button . '" />';
            $form = '<div class="subscription_form">';
            $form .= '<form method="post" id="targetwidget" action="';
            $form .= admin_url('admin-ajax.php') . '">';
            $form .= wp_nonce_field('target_ajax', 'security');


            $form .= '<p>' . $widget_introduction . '<br/><label for="target_email">' . 'Email' . '</label><br /><input type="email" name="email" id="target_email" placeholder="Enter your email address..." size="' . $textbox_size . '" /></p>';
            $form .= '<input type="hidden" name="action" value="add_subscription">';
            $form .= $input_form_action;
            $form .= '</form>';
            $form .= '</div>';

            $form .= "<script type='text/javascript'>";

            $form .= "jQuery(document).ready(function($){";
            $form .= "$('#targetwidget').ajaxForm({";
            $form .= "success:function(response){";
            $form .= "console.log(response);" .
                "jQuery('.subscription_form').html( response )";
            $form .= "},error:function(response){";
            $form .= "console.log(response);}";
            $form .= "});";
            $form .= "});";
            $form .= "</script>";

            echo $form;
            echo $after_widget;
        }
    }
}



function add_subscription()
{
    //wp_send_json_success($_POST["email"]);
    check_ajax_referer('target_ajax', 'security');
    if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        global $wpdb;
        $email = sanitize_text_field($_POST["email"]);
        $tableName = $wpdb->get_blog_prefix() . 'target_subscribers';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tableName WHERE email = %s", $_POST['email']
        ));
        if (!$exists) {
            $insert_row = $wpdb->insert(
                $tableName,
                array(
                    'email' => $email,
                    'created' => date('Y-m-d'),
                )
            );
// if row inserted in table
            if ($insert_row) {
                $output = ' <div style="margin: 8px; ">
                             Your subscription has been confirmed!
                             </div>';
                echo $output;
                //echo json_encode(array('res' => true, 'message' => __('New row has been inserted.')));
            } else {
                $output = ' <div style="margin: 8px; ">
            Something went wrong. Please try again later.
                             </div>';
                echo $output;
                //echo json_encode(array('res' => false, 'message' => __('Something went wrong. Please try again later.')));
            }
        } else {
            $output = ' <div style="margin: 8px; ">
                             You\'re already subscribed with this email!
                             </div>';
            echo $output;
        }
        wp_die();
    }
}
?>