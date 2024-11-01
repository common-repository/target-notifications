<?php

defined('ABSPATH') || exit;
function target_selection_submenu_integrations()
{
    ?>
<tr style="vertical-align:bottom">
 <th scope="row">Integrations </th>
 <td>
 <select name="target_form_integration">
 <?php $options = get_option('target_options');
    $target_form_integration = isset($options['target_form_integration']) ? $options['target_form_integration'] : 'none';
    ?>
    <option value="none" <?php selected($target_form_integration, 'none');?>>None</option>
 <option value="cf7" <?php selected($target_form_integration, 'cf7');?>>Contact form 7</option>
 </select>
</td>
</tr>
</table>
    <?php submit_button();
    ?>
    </form>
 <?php

}
