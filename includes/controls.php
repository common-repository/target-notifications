<?php

defined('ABSPATH') || exit;

function target_button($action, $label, $function, $data = '')
{
    if ($function != null) {
        $form = '<form method="post" id="' . $data . '" action="';
        $form .= admin_url("admin-post.php");
        $form .= '" style="display: inline-block;">';
        $form .= '<input type="hidden" name="action" value="' . esc_attr($function) . '">';
        $form .= '<input type="hidden" name="' . esc_attr($action) . '" >';
        $form .= '<input type="submit" class="button-secondary" value="' . esc_attr($label) . '"></form>';
        echo $form;
    }
}

function target_text($name, $size = 20, $value = '', $placeholder = '')
{

    echo '<input form="' . $name . '" placeholder="' . esc_attr($placeholder) . '" name="' . $name . '" type="text" ';
    if (!empty($size)) {
        echo 'size="' . $size . '" ';
    }
    echo 'value="', esc_attr($value), '">';

}
