<?php

defined('ABSPATH') || exit;

function target_help_tabs()
{
    $screen = get_current_screen();
    $screen->add_help_tab(array(
        'id' => 'target-configure',
        'title' => 'Configure mail options',
        'content' => '<p>This screen displays the parameters you need to set up to start using this plugin.</p>
        <p>The sender name and mail are the name your subscribers/members will see on their mailbox and the email address you want to use. You should be using an email address with your domain name. SMTP plugins can overwrite these to allow you to send emails from other services.</p>
        <p>You can set a mail delivery speed your server can handle in the "emails sent by hour" field. Emails will be sent every five minutes until this value is reached. </p>
        <p>You can choose any of the placeholders listed at the end of the page in the email subject and body. You can save 2 versions of email body depending on whether the mail will be sent after individual posts or bulk posts.</p>
        <p>You must click the Save Changes button for new settings to take effect.</p>',
    ));

    $screen->add_help_tab(array(
        'id' => 'target-send-each',
        'title' => 'Send automatically after each post',
        'content' => '<p><b>Send automatically after publishing</b> option allows you to send automated email newletters to registered users or subscibers immediately after a post is published.</p>
        <p><b>Send to emails saved through</b> subscription if you want to send the email to subscribers collected through the subscription form shortcode, or through registration to send it to the email address your users signed up with.</p>',
    ));
    $screen->add_help_tab(array(
        'id' => 'target-send-group',
        'title' => 'Send email digest of published post',
        'content' => '<p>You check no on <b>send automatically after publishing</b> to create a newsletter digest of you posts. Select how many time per day, week or month you want to send the emails. </p>
        <p><b>Send to emails saved through</b> subscription if you want to send the email to subscribers collected through the subscription form shortcode, or through registration to send it to the email address your users signed up with.</p>',
    ));
    $screen->add_help_tab(array(
        'id' => 'target-test',
        'title' => 'Test email',
        'content' => '<p>Press <b>Test Email</b> to send a test email to the address entered in the field. An email will be sent for the last post published.</p>',
    ));
    $screen->add_help_tab(array(
        'id' => 'target-reset',
        'title' => 'Reset post',
        'content' => '<p>The <b>Reset last post</b> button cancels the emails waiting to be sent for all previously published posts.</p>',
    ));

}

function target_cat_help_tabs()
{
    $screen = get_current_screen();
    $screen->add_help_tab(array(
        'id' => 'target-shortcode-options',
        'title' => 'Configure shortcode options',
        'content' => '<p>You can add the shortcodes [target-categories-form] and [target-tags-form] on any page in your blog to display a form with categories or tags your users can subscribe to.</p>
        <p>Select categories or tags you wish to add to the form users see, and add descriptions to overwrite their original ones.</p>
       ',
    ));
    $screen->add_help_tab(array(
        'id' => 'target-save-search',
        'title' => 'Save user search',
        'content' => '<p><b>Save registered user\'s searches</b> lets you choose how many inputs registered users make in the search box you want saved.</p>
        <p>You can enter the value 0 if you don\'t want to save any.</p>
       ',
    ));

}
