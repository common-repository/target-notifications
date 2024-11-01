<?php

defined('ABSPATH') || exit;

function target_cron_bundle_mail()
{

    // If some other task is already running, stop
    if (get_transient('target_cron_send_action_semaphore') !== false) {
        return;
    }

// Set semaphore for 5 minutes
    // set_transient('target_cron_send_action_semaphore', true, 300); //!300 interval
    $options = get_option('target_options');
    if (isset($options['use_mailchimp']) && $options['use_mailchimp'] == true) {
        return;
    }

    if ($options['target_automatic_mail'] == false) {
        $interval = $options['target_email_schedule_number'];
        if ($options['target_email_schedule'] === 'day') {
            $interval = floor(1440 / $options['target_email_schedule_number']);
        } elseif ($options['target_email_schedule'] === 'week') {
            $interval = floor(10080 / $options['target_email_schedule_number']);
        } elseif ($options['target_email_schedule'] === 'month') {
            $interval = floor(43200 / $options['target_email_schedule_number']);
        }
        set_transient('target_cron_send_action_semaphore', true, $interval * 60); //!300 interval
        if (!isset($options['target_bundle_done']) || $options['target_bundle_done'] === '1') {
            $posts = get_posts(array(
                'numberposts' => -1,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
                'date_query' => array(
                    'after' => $interval + 1 . ' minutes ago', //+5
                    'inclusive' => true,
                ),
            ));
        }

        if (count($posts) > 0) {
            target_select_emails($posts);
        }

    }

    delete_transient('target_cron_send_action_semaphore');
}

function target_select_emails($posts, $last_id_sent = 0, $ids = false)
{
    $max = $options['target_max_emails'];
    if (!is_numeric($max)) {
        $max = 100;
    }
    $max_emails = max(floor($max / 12), 1);
    //$max_emails = 1;
    global $wpdb;
    $email_query = 'select * from ' . $wpdb->get_blog_prefix();
    $email_query .= 'target_subscribers ';
    $email_query .= "WHERE id > %d ";
    $email_query .= 'ORDER by id ASC ';
    $email_query .= "LIMIT %d ";
    $results = $wpdb->get_results($wpdb->prepare($email_query, $last_id_sent, $max_emails),
        ARRAY_A);
    $options = get_option('target_options');
    if (isset($options['target_last_sub_id_sent_bundle'])) {
        $last_id = $options['target_last_sub_id_sent_bundle'];
    }

    if (!empty($results)) {
        foreach ($results as $user) {
            foreach ($posts as $post) {
                $content = '';
                if (!isset($options['target_title_search']) || $options['target_title_search']) {
                    $content .= $ids === false ? $post->post_title : get_the_title($post);
                }

                if (!isset($options['target_body_search']) || $options['target_body_search']) {
                    $content .= $ids === false ? wp_strip_all_tags($post->post_content) : wp_strip_all_tags(get_post($post)->post_content);
                }
                $last_id = $user['id'];
                if ($user['registered_user_id'] > 0) {
                    $user_info = get_user_meta($user['registered_user_id'], 'target_user_search_queries', true);

                    if (!empty($user_info)) {
                        foreach ($user_info as $search_query) {
                            if (stripos($content, $search_query) !== false) {

                                $email_array[$user['email']][] = $ids === false ? $post : get_post($post);
                                break;
                                continue;
                            }
                        }
                    }
                }
                $selection_query = 'select * from ' . $wpdb->get_blog_prefix();
                $selection_query .= 'target_subscriber_selection ';
                $selection_query .= "WHERE subscriber_id = %d ";
                $selection_results = $wpdb->get_results($wpdb->prepare($selection_query, $user['id']),
                    ARRAY_A);
                $poid = $ids === false ? $post->ID : $post;
                //$post_categories = get_the_terms($poid, 'category');
                $post_terms = wp_get_post_terms($poid, array('category', 'post_tag'));
                if (!empty($post_terms) && !is_wp_error($post_terms)) {
                    $term_ids = wp_list_pluck($post_terms, 'term_id');
                    foreach ($selection_results as $selection_result) {
                        if (in_array($selection_result['term_id'], $term_ids)) {
                            $email_array[$user['email']][] = $ids == false ? $post : get_post($post);
                            break;
                        }
                    }
                }
            }
        }
    }
    $options['target_last_sub_id_sent_bundle'] = $last_id;
    $options['target_bundle_done'] = '0';
    $last_user_query = 'select max(id) from ' . $wpdb->get_blog_prefix();
    $last_user_query .= 'target_subscribers ';
    // $last_user_results = $wpdb->get_results($last_user_query, ARRAY_A);
    $last_user_results = $wpdb->get_var($last_user_query);
    $last_user_sbuscribed = $last_user_results;

    if ($last_id === $last_user_results) {
        $options['target_bundle_done'] = '1';
    }
    $emails_sent = sizeof($email_array);
    if (!empty($options['target_sb_emails_sent'])) {
        $options['target_sb_emails_sent'] = $options['target_sb_emails_sent'] + $emails_sent;
    }
    $options['target_sb_emails_sent'] = $options['target_sb_emails_sent'] + $emails_sent;
    if ($ids === false) {
        $post_ids = target_extract_ids($posts);
        $options['bundle_posts_sending'] = $post_ids;
        $options['target_sb_emails_sent'] = $emails_sent;
    }
    update_option('target_options', $options);
    target_send_posts($email_array);
}
function target_extract_terms($cats)
{
    $res = array();
    foreach ($cats as $k => $v) {
        $res[] = $v->term_id;
    }
    return $res;
}
function target_extract_ids($cats)
{
    $res = array();
    foreach ($cats as $k => $v) {
        $res[] = $v->ID;
    }
    return $res;
}
function target_send_posts($email_array)
{
    ignore_user_abort(true);

    $options = get_option('target_options');
    //$emails_sent = 0;
    if ($options['target_sender_mail']) {
        $sender_mail = $options['target_sender_mail'];
    } else {
        $sender_mail = get_option('admin_email');
    }
    $from = $options['target_sender_name'];
    $headers = array('From: ' . $from . ' <' . $sender_mail . '>');
    $headers[] = 'Content-type: text/html';
    if (!isset($options['target_email_body_bulk']) || empty($options['target_email_body_bulk'])) {
        $message = 'Check out these new posts. ';
        $message .= '<br />';

    } else {

        $message = stripslashes($options['target_email_body_bulk']);
    }
    foreach ($email_array as $email => $posts) {
        $iterator = 0;
        foreach ($posts as $post) {
            if (!isset($options['target_email_body_bulk']) || empty($options['target_email_body_bulk'])) {
                $message .= 'Post Title: ' . $post->post_title;
                $message .= '<br />';
                $message .= '<br />';
                $message .= '<a href="';
                $message .= get_permalink($post->ID);
                $message .= '">Check the post</a>';
            } else {
                $message = target_replace_placeholders_itr($message, $post, $iterator + 1, count($posts));
            }
            if (!isset($options['target_email_subject']) || empty($options['target_email_subject'])) {
                $email_title = htmlspecialchars_decode(get_bloginfo(),
                    ENT_QUOTES)
                . " - " . $post->post_title;
            } else {
                $email_title = $options['target_email_subject'];

            }
            $iterator++;
            if ($iterator == 4) {
                break;

            }
        }
        if ($iterator !== 0) {
            $mail = wp_mail($email, $email_title, $message, $headers);
            //$emails_sent++;
        }
        // if (empty($options['target_sb_emails_sent']) || $options['target_bundle_done'] === '0') {
        //     $options['target_sb_emails_sent'] = $emails_sent;
        // } else {
        //     $options['target_sb_emails_sent'] = $options['target_sb_emails_sent'] + $emails_sent;
        // }
        // update_option('target_options', $options);

    }

}
function target_replace_placeholders_itr($mail_content, $post, $i, $count)
{
    $to_replace = array(
        '%%BLOG_URL%%' => get_option('siteurl'),
        '%%HOME_URL%%' => get_option('home'),
        '%%BLOG_NAME%%' => get_option('blogname'),
        '%%BLOG_DESCRIPTION%%' => get_option('blogdescription'),
        '%%POST_TITLE%%' => $post->post_title,
        '%%POST_URL%%' => get_permalink($post->$ID),
        '%%CONTENT_FIRST_PARAGRAPH%%' => get_first_paragraph($post->$ID),
        '%%CONTENT%%' => $post->post_content,
        '%%POST_TITLE_' . $i . '%%' => $post->post_title,
        '%%POST_URL_' . $i . '%%' => get_permalink($post->$ID),
        '%%CONTENT_FIRST_PARAGRAPH_' . $i . '%%' => get_first_paragraph($post->$ID),
        '%%CONTENT_' . $i . '%%' => $post->post_content,
        '%%POST_THUMBNAIL_' . $i . '%%' => get_the_post_thumbnail($post->$ID),
    );
    if ($count < 4) {
        for ($k = $count + 1; $k <= 4; $k++) {
            $to_replace += array(
                '%%POST_TITLE_' . $k . '%%' => '',
                '%%POST_URL_' . $k . '%%' => '',
                '%%CONTENT_FIRST_PARAGRAPH_' . $k . '%%' => '',
                '%%CONTENT_' . $k . '%%' => '',
            );
        }
    }
    foreach ($to_replace as $placeholder => $value) {
        $mail_content = str_replace($placeholder, $value, $mail_content);
    }
    return $mail_content;
}
function target_convert_mailchimp($interests)
{
    $result = array();
    // $term_id = get_term_by('slug', esc_attr('summer'), 'category')->term_id;
    foreach ($interests as $interest) {
        $term_id = get_term_by('name', esc_attr($interest->name), 'post_tag')->term_id;
        if (empty($term_id)) {
            $term_id = get_term_by('name', esc_attr($interest->name), 'category')->term_id;
        }
        $result[$term_id] = array($interest->id, $interest->name);
    }
    return $result;
}
function target_interest_format($interests, $selected)
{
    $result = array();
    foreach ($interests as $key => $value) {
        if (in_array($key, $selected)) {
            $result[$value[0]] = true;
        } else {
            $result[$value[0]] = false; //if we want to remove interest
        }
    }
    return $result;
}
