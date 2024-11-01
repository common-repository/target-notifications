<?php

//wp_unschedule_hook('target_cron_send_action');
//delete_transient('target_cron_send_action_semaphore');

add_action('target_cron_send_action', 'target_cron_send_mail');
add_action('target_cron_bundle_action', 'target_cron_bundle_mail');

function target_add_custom_cron_schedule($schedules)
{
    $options = get_option('target_options');
    if (isset($options['use_mailchimp']) && $options['use_mailchimp'] == true) {
        return $schedules;
    }
    $schedules['target-notifications'] = array(
        //'interval' => 900,
        'interval' => 300,
        'display' => 'Target every 5 minutes',
    );

    $interval = $options['target_email_schedule_number'];
    if (isset($interval) && isset($options['target_email_schedule']) && $interval != 0) {
        if ($options['target_email_schedule'] === 'day') {
            $interval = floor(86400 / $options['target_email_schedule_number']);
        } elseif ($options['target_email_schedule'] === 'week') {
            $interval = floor(604800 / $options['target_email_schedule_number']);
        } elseif ($options['target_email_schedule'] === 'month') {
            $interval = floor(2592000 / $options['target_email_schedule_number']);
        }

        $schedules['target-notifications-bundle'] = array(
            'interval' => $interval,
            'display' => 'Target custom schedule',
        );
    }
    return $schedules;
}

function target_cron_send_mail()
{

    // If some other task is already running, stop
    if (get_transient('target_cron_send_action_semaphore') !== false) {
        return;
    }

    // Set semaphore for 5 minutes
    set_transient('target_cron_send_action_semaphore', true, 300);

    $options = get_option('target_options');
    if (isset($options['use_mailchimp']) && $options['use_mailchimp'] == true) {
        return;
    }
    if ($options['target_automatic_mail'] == true) {
        // if(1){

        // }
        if ($options['target_last_post'] && !empty($options['target_last_post'])) {
            $post_id = $options['target_last_post'][0];
            if ($options['recipients'] === 'registered') {
                $last_id_sent = $options['target_last_id_sent'];
                if ($last_id_sent == null) {
                    $last_id_sent = 0;
                }

                $emails_selected = target_get_relevant_emails(get_post($post_id), $last_id_sent, false);
            } else if ($options['recipients'] === 'subscribed') {
                $last_id_sent = $options['target_last_subscriber_id_sent'];
                if ($last_id_sent == null) {
                    $last_id_sent = 0;
                }

                $emails_selected = target_get_subscribed_relevant_emails(get_post($post_id), $last_id_sent, false);
            }

            if (!empty($emails_selected)) {
                target_send($emails_selected, get_post($post_id));
            }
        }
    } else {
        if ($options['target_bundle_done'] === '0') {
            $last_id_sent = $options['target_last_sub_id_sent_bundle'];
            if ($last_id_sent > 0) {
                target_select_emails($options['bundle_posts_sending'], $last_id_sent, true);
            }

        }
    }
    delete_transient('target_cron_send_action_semaphore');
}

add_action('transition_post_status', 'target_send_mail', 10, 3);

function target_send_mail($new_status, $old_status, $post)
{
    if (get_option('target_options')['target_automatic_mail'] == true) {
        if ($new_status === 'publish') {
            //$last_id_sent = get_option('target_options')['target_last_id_sent'];
            // if($last_id_sent==5000){
            $options = get_option('target_options');
            $options['target_last_post'][] = $post->ID;
            update_option('target_options', $options);
            // }
            // else
            // {
            //     $options['target_next_post'] = $post;
            // update_option('target_options', $options);
            // }
            if (isset($options['use_mailchimp']) && $options['use_mailchimp'] == true) {
                target_mailchimp_send($post);
            } else {

                if ($options['recipients'] === 'registered') {
                    $emails_selected = target_get_relevant_emails($post, 0, true);
                } else if ($options['recipients'] === 'subscribed') {
                    $emails_selected = target_get_subscribed_relevant_emails($post, 0, true);
                }

                if (!empty($emails_selected)) {
                    target_send($emails_selected, $post);
                }
            }
        }

    }
}
function target_get_relevant_emails($post, $last_id_sent = 0, $onPublish = false)
{
    $options = get_option('target_options');
    if (empty($options['target_last_post'])) {
        return array();
    }

    $max = $options['target_max_emails'];
    if (!is_numeric($max)) {
        $max = 100;
    }
    $max_emails = max(floor($max / 12), 1);
    //$max_emails = max(floor($max / 4), 1);
    if ($onPublish) {
        $max_emails = 1;
    }

    $users_query = new WP_User_Query(array(
        // 'role' => 'subscriber',
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => array('ID', 'user_email'),
        'number' => $max_emails,
        'exclude' => range(0, $last_id_sent),
    ));
    $results = $users_query->get_results();
    if (!empty($results)) {
        $content = '';
        if (!isset($options['target_title_search']) || $options['target_title_search']) {
            $content .= $post->post_title;
        }

        if (!isset($options['target_body_search']) || $options['target_body_search']) {
            $content .= wp_strip_all_tags($post->post_content);
        }
        $emails_sent = 0;
        foreach ($results as $user) {
            $user_info = get_user_meta($user->ID, 'target_user_search_queries', true);
            $last_id = $user->ID;
            if (!empty($user_info)) {
                foreach ($user_info as $search_query) {
                    if (stripos($content, $search_query) !== false) {
                        $emails_selected[] = $user->user_email;
                        $emails_sent++;
                        break;
                    }
                }
            }

        }
    }

    $options['target_last_id_sent'] = $last_id;
    $options['target_r_emails_sent'] = $emails_sent;

    $args = array(
        'orderby' => 'registered', // registered date
        'order' => 'DESC', // last registered goes first
        'number' => 1, // limit to the last one, not required
    );

    $users = get_users($args);

    $last_user_registered = $users[0];
    if ($last_id == $last_user_registered->ID) {
        //$options['target_last_post']= get_option('target_options')['target_last_post'];
        $removed = array_shift($options['target_last_post']);
        if (isset($removed) && !empty($removed)) {
            $options['target_last_post_completed'] = $removed;
        }

    }
    update_option('target_options', $options);
    $emails_selected = array_unique($emails_selected);
    return $emails_selected;
}

function target_get_subscribed_relevant_emails($post, $last_id_sent = 0, $onPublish = false)
{
    $options = get_option('target_options');
    if (empty($options['target_last_post'])) {
        return array();
    }

    $max = $options['target_max_emails'];
    if (!is_numeric($max)) {
        $max = 100;
    }
    $max_emails = max(floor($max / 12), 1);
    //$max_emails = max(floor($max / 4), 1);
    if ($onPublish) {
        $max_emails = 1;
    }
    global $wpdb;
    $email_query = 'select * from ' . $wpdb->get_blog_prefix();
    $email_query .= 'target_subscribers ';
    $email_query .= "WHERE id > %d ";
    $email_query .= 'ORDER by id ASC ';
    $email_query .= "LIMIT %d ";

    $results = $wpdb->get_results($wpdb->prepare($email_query, $last_id_sent, $max_emails),
        ARRAY_A);
    $emails_sent = 0;
    if (!empty($results)) {
        $content = '';
        if (!isset($options['target_title_search']) || $options['target_title_search']) {
            $content .= $post->post_title;
        }

        if (!isset($options['target_body_search']) || $options['target_body_search']) {
            $content .= wp_strip_all_tags($post->post_content);
        }

        foreach ($results as $user) {
            $last_id = $user['id'];
            if ($user['registered_user_id'] > 0) {
                $user_info = get_user_meta($user['registered_user_id'], 'target_user_search_queries', true);

                if (!empty($user_info)) {
                    foreach ($user_info as $search_query) {
                        if (stripos($content, $search_query) !== false) {
                            $emails_selected[] = $user['email'];
                            $emails_sent++;
                            break;
                        }
                    }
                }
            }
            $selection_query = 'select * from ' . $wpdb->get_blog_prefix();
            $selection_query .= 'target_subscriber_selection ';
            $selection_query .= "WHERE subscriber_id = %d ";
            $selection_results = $wpdb->get_results($wpdb->prepare($selection_query, $user['id']),
                ARRAY_A);
            //$post_categories = get_the_terms($post->ID, 'category');
            $post_terms = get_the_terms($post->ID, array('category', 'post_tag'));
            if (!empty($post_terms) && !is_wp_error($post_terms)) {
                $term_ids = wp_list_pluck($post_terms, 'term_id');
                foreach ($selection_results as $selection_result) {
                    if (in_array($selection_result['term_id'], $term_ids)) {
                        $emails_selected[] = $user['email'];
                        $emails_sent++;
                        break;
                    }
                }
            }

        }
    }

    $options['target_last_subscriber_id_sent'] = $last_id;
    if (empty($options['target_s_emails_sent']) || $last_id_sent === 0) {
        $options['target_s_emails_sent'] = $emails_sent;
    } else {
        $options['target_s_emails_sent'] = $options['target_s_emails_sent'] + $emails_sent;
    }
    // $args = array(
    //     'orderby' => 'registered', // registered date
    //     'order' => 'DESC', // last registered goes first
    //     'number' => 1, // limit to the last one, not required
    // );

    // $users = get_users($args);
    $last_user_query = 'select max(id) from ' . $wpdb->get_blog_prefix();
    $last_user_query .= 'target_subscribers ';
    $last_user_results = $wpdb->get_results($last_user_query, ARRAY_A);
    //$last_user_registered = $users[0];
    $last_user_registered = $last_user_results[0]["max(id)"];

    if ($last_id == $last_user_registered) {

        $removed = array_shift($options['target_last_post']);
        if (isset($removed) && !empty($removed)) {
            $options['target_last_post_completed'] = $removed;
        }

    }
    update_option('target_options', $options);

    $emails_selected = array_unique($emails_selected);
    return $emails_selected;
}

function target_send($emails_selected, $post)
{
    ignore_user_abort(true);
    $options = get_option('target_options');
    if ($options['target_sender_mail']) {
        $sender_mail = $options['target_sender_mail'];
    } else {
        $sender_mail = get_option('admin_email');
    }

    $from = $options['target_sender_name'];
    $headers = array('From: ' . $from . ' <' . $sender_mail . '>');
    $headers[] = 'Content-type: text/html';
    $headers[] = 'Reply-To: ' . $from . ' <' . $options['target_reply_to'] . '>';
    $headers[] = 'Return-Path: <' . $options['target_return_path'] . '>';
    if (!isset($options['target_email_body']) || empty($options['target_email_body'])) {
        $message = 'A new post you might be interested in has been added. ';
        $message .= '<br />';
        $message .= 'Post Title: ' . $post->post_title;
        $message .= '<br />';
        $message .= '<br />';
        $message .= '<a href="';
        $message .= get_permalink($post->ID);
        $message .= '">Check the post</a>';
    } else {

        $message = stripslashes($options['target_email_body']);
        $message = target_replace_placeholders($message, $post->ID);
    }
    if (!isset($options['target_email_subject']) || empty($options['target_email_subject'])) {
        $email_title = htmlspecialchars_decode(get_bloginfo(),
            ENT_QUOTES)
        //  - New results for one of your searches: "
         . " - " . $post->post_title;
    } else {
        $email_title = $options['target_email_subject'];
        $email_title = target_replace_placeholders($email_title, $post->ID);

    }
// Send e-mail
    foreach ($emails_selected as $to) {
        $mail = wp_mail($to, $email_title, $message, $headers);
    }

}

function target_replace_placeholders($mail_content, $ID)
{
    $to_replace = array(
        '%%BLOG_URL%%' => get_option('siteurl'),
        '%%HOME_URL%%' => get_option('home'),
        '%%BLOG_NAME%%' => get_option('blogname'),
        '%%BLOG_DESCRIPTION%%' => get_option('blogdescription'),
        '%%POST_TITLE%%' => get_the_title($ID),
        '%%POST_URL%%' => get_permalink($ID),
        '%%CONTENT_FIRST_PARAGRAPH%%' => get_first_paragraph($ID),
        '%%CONTENT%%' => get_post($ID)->post_content,
        '%%POST_THUMBNAIL%%' => get_the_post_thumbnail($ID),
    );
    foreach ($to_replace as $placeholder => $value) {
        $mail_content = str_replace($placeholder, $value, $mail_content);
    }
    return $mail_content;
}
function get_first_paragraph($ID)
{
    $str = apply_filters('the_content', get_post($ID)->post_content);
    $str = substr($str, 0, strpos($str, '</p>') + 4);
    $str = strip_tags($str, '<a><strong><em>');
    return '<p>' . $str . '</p>';
}

add_action('template_redirect', 'target_save_search_query');
function target_save_search_query()
{
    if (!is_user_logged_in()) {;
        return;
    }
    global $current_user;
    $search_query = get_search_query();

    if (!empty($search_query)) {
        $options = get_option('target_options');
        if ($options['target_search_number'] == 0) {
            return;
        }

        $search_query = trim($search_query);
        $search_query = preg_replace('/\s+/', ' ', $search_query);
        if (strlen($search_query) > 3) {
            // get current user info
            $current_user = wp_get_current_user();
            $old_queries = get_user_meta($current_user->ID, 'target_user_search_queries', true);
            //echo $old_queries;
            if (isset($old_queries) && is_array($old_queries)) {
                //if we saved already more the one search
                $old_queries[] = $search_query;
                $old_queries = array_unique($old_queries);
                if (sizeof($old_queries) > $options['target_search_number']) {
                    $removed = array_shift($old_queries);
                }
                update_user_meta($current_user->ID, 'target_user_search_queries', $old_queries);
            }
            if (isset($old_queries) && !is_array($old_queries)) {
                //if we saved only one search before
                $new_queries = array($old_queries, $search_query);
                update_user_meta($current_user->ID, 'target_user_search_queries', $new_queries);
            }
            if (!isset($old_queries)) {
                //first query we are saving fr this user
                update_user_meta($current_user->ID, 'target_user_search_queries', $search_query);
            }
        }
    }

}
function target_mailchimp_send($post, $test = false, $test_email = "")
{
    $options = get_option('target_options');
    $api_key = $options['mailchimp_key'];
    $dc = substr($options['mailchimp_key'], strpos($options['mailchimp_key'], '-') + 1);
    $list_id = array_keys($options['mailchimp_list'])[0];

    if (!isset($options['target_email_subject']) || empty($options['target_email_subject'])) {
        $email_title = htmlspecialchars_decode(get_bloginfo(),
            ENT_QUOTES)
        //  - New results for one of your searches: "
         . " - " . $post->post_title;
    } else {
        $email_title = $options['target_email_subject'];
        $email_title = target_replace_placeholders($email_title, $post->ID);

    }
    if ($options['target_sender_mail']) {
        $sender_mail = $options['target_sender_mail'];
    } else {
        $sender_mail = get_option('admin_email');
    }
    if ($test == false) {

        $post_terms = get_the_terms($post->ID, array('category', 'post_tag'));
        $interest_value = array();
        if (!empty($post_terms) && !is_wp_error($post_terms)) {
            $term_ids = wp_list_pluck($post_terms, 'term_id');
            foreach ($term_ids as $term_id) {
                if (in_array($term_id, array_keys($options['mailchimp_interests']))) {

                    $interest_value[] = $options['mailchimp_interests'][$term_id][0];
                }
            }
        }
        $field = isset($options['mailchimp_group_created']) ? $options['mailchimp_group_created'] : "";
        if (isset($options['mailchimp_interests'])) {
            $conditions = array(array(
                "op" => "interestcontains",
                "value" => $interest_value,
                "field" => "interests-" . $field));
            $segment_opts = array(
                "match" => "any",
                "conditions" => $conditions,
            );
            $recipients = array(
                "list_id" => $list_id,
                "segment_opts" => $segment_opts,
            );
        } else {
            $recipients = array(
                "list_id" => $list_id,
            );
        }

    } else {
        $segment_opts = array(
            "conditions" => array(array(
                "condition_type" => "EmailAddress", "op" => "is", "value" => $test_email, "field" => "merge0",
            )),
        );
        $recipients = array("list_id" => $list_id,
            "segment_opts" => $segment_opts,
        );
    }
    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
        ),
        'body' => json_encode([
            'type' => "regular",
            'settings' => array(
                "subject_line" => $email_title,
                "title" => $email_title,
                "from_name" => $sender_mail,
                "reply_to" => $options['target_reply_to'],
            ),
            'recipients' => $recipients,
        ]),
    );
    $response = wp_remote_post('https://' . $dc . '.api.mailchimp.com/3.0/campaigns', $args);
    $body = json_decode($response['body']);
    if ($response['response']['code'] == 200) {
        $campaign_id = $body->id;
        if (!isset($options['target_email_body']) || empty($options['target_email_body'])) {
            $message = 'A new post you might be interested in has been added. ';
            $message .= '<br />';
            $message .= 'Post Title: ' . $post->post_title;
            $message .= '<br />';
            $message .= '<br />';
            $message .= '<a href="';
            $message .= get_permalink($post->ID);
            $message .= '">Check the post</a>';
        } else {

            $message = stripslashes($options['target_email_body']);
            $message = target_replace_placeholders($message, $post->ID);
        }
        $content_args = array(
            'method' => 'PUT',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
            ),
            'body' => json_encode([
                'plain_text' => $message,
                'html' => $message,
            ]),
        );
        $content_response = wp_remote_post('https://' . $dc . '.api.mailchimp.com/3.0/campaigns/' . $campaign_id . '/content', $content_args);
        if ($content_response['response']['code'] == 200) {
            $send_args = array(
                'method' => 'POST',
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                ),
            );
            $send_response = wp_remote_post('https://' . $dc . '.api.mailchimp.com/3.0/campaigns/' . $campaign_id . '/actions/send', $send_args);
            return '1';
        } else {
            return '0';
        }
        return '0';
    } else {

        //target_log_file('<b>' . $response['response']['code'] . $body->title . ':</b> ' . $body->detail, 'else else');
        return '0';
    }

}
