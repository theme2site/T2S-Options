<?php
/**
 * T2S Options
 *
 * Plugin Name: T2S Options
 * Description: Provide custom parameter configuration for your website.
 * Author: Theme2Site
 * Author URI: http://www.theme2site.com/
 * Version: 1.1.0
 * Text Domain: t2s-options
 * Domain Path: languages
*/

define('T2S_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('T2S_PLUGIN_NAME', trim(dirname(T2S_PLUGIN_BASENAME), '/'));
define('T2S_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . T2S_PLUGIN_NAME);
define('T2S_PLUGIN_URL', WP_PLUGIN_URL . '/' . T2S_PLUGIN_NAME);
define('T2S_OPTIONS_PREFIX', 't2so_');
define('T2S_PLUGIN_VERSION', '1.0.0');

global $wpdb, $T2S_TABLE;
define('T2S_TABLE', $wpdb->prefix . 't2s_options');

$T2S_TABLE = T2S_TABLE;

//Create a table in MySQL database when activate plugin
function t2so_setup()
{
    global $T2S_TABLE;

    $sql = "CREATE TABLE IF NOT EXISTS $T2S_TABLE (
            `id` int(5) NOT NULL AUTO_INCREMENT,
            `label` varchar(100) NOT NULL,
            `name` varchar(80) NOT NULL,
            `value` text NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);

    update_option(T2S_OPTIONS_PREFIX . 'version', T2S_PLUGIN_VERSION);
}
register_activation_hook(__FILE__, 't2so_setup');


// Add settings link to Plugins Page
function t2so_plugin_add_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=t2s_options">' . __('Settings') . '</a>';
    array_push($links, $settings_link);

    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 't2so_plugin_add_settings_link');


add_action('admin_menu', 't2so_add_menu');
function t2so_add_menu()
{
    global $my_plugin_hook;
    $my_plugin_hook = add_options_page('T2S Options', 'T2S Options', 'manage_options', 't2s_options', 't2so_options_adm');
}

function t2so_insert($row)
{
    global $wpdb;

    $row['label'] = stripslashes_deep(filter_var($row['label'], FILTER_SANITIZE_SPECIAL_CHARS));
    $row['name'] = stripslashes_deep(filter_var($row['name'], FILTER_SANITIZE_SPECIAL_CHARS));
    $row['value'] = stripslashes_deep(filter_var($row['value'], FILTER_UNSAFE_RAW));

    return $wpdb->insert(
        T2S_TABLE,
        array(
            'label' => $row['label'],
            'name' => $row['name'],
            'value' => stripslashes($row['value'])
        ),
        array('%s', '%s', '%s')
    );
}

function t2so_update($row)
{
    global $wpdb;

    $row['id'] = filter_var($row['id'], FILTER_VALIDATE_INT);
    $row['label'] = stripslashes_deep(filter_var($row['label'], FILTER_SANITIZE_SPECIAL_CHARS));
    $row['name'] = stripslashes_deep(filter_var($row['name'], FILTER_SANITIZE_SPECIAL_CHARS));
    $row['value'] = stripslashes_deep(filter_var($row['value'], FILTER_UNSAFE_RAW));


    return $wpdb->update(
        T2S_TABLE,
        array(
            'label' => $row['label'],
            'name' => $row['name'],
            'value' => stripslashes($row['value'])
        ),
        array('id' => $row['id']),
        array('%s', '%s', '%s'),
        array('%d')
    );
}

function t2so_delete($id)
{
    global $wpdb, $T2S_TABLE;

    return $wpdb->query($wpdb->prepare("DELETE FROM $T2S_TABLE WHERE id = %d ", $id));
}

// Get all options from Database
function t2so_get_options()
{
    global $wpdb, $T2S_TABLE;

    return $wpdb->get_results("SELECT id, label, name, value FROM $T2S_TABLE ORDER BY label ASC");
}

// Get single option from Database
function t2so_get_option($id)
{
    global $wpdb, $T2S_TABLE;

    return $wpdb->get_row($wpdb->prepare("SELECT id, label, name, value FROM $T2S_TABLE WHERE id = %d", $id));
}


// Panel Admin
function t2so_options_adm()
{
    global $wpdb, $my_plugin_hook;

    $id = '';
    $label = '';
    $name = '';
    $value = '';

    $message = '';

    if (isset($_GET['del']) && $_GET['del'] > 0) :
        if (t2so_delete($_GET['del'])) :
            $message = '<div class="updated"><p><strong>' . __('Settings saved.') . '</strong></p></div>';
        endif;
    elseif (isset($_POST['id'])) :

        if ($_POST['id'] == '') :
            t2so_insert($_POST);
            $message = '<div class="updated"><p><strong>' . __('Settings saved.') . '</strong></p></div>';
        elseif ($_POST['id'] > 0) :
            t2so_update($_POST);
            $message = '<div class="updated"><p><strong>' . __('Settings saved.') . '</strong></p></div>';
        endif;
    elseif (isset($_GET['id']) && $_GET['id'] > 0) :

        $option = t2so_get_option($_GET['id']);

        $id    = $option->id;
        $label = $option->label;
        $name  = $option->name;
        $value = $option->value;

    endif;

    $options = t2so_get_options(); ?>

    <div class="wrap">
        <div id="icon-tools" class="icon32"></div>
        <h2>
            <?php _e( 'Settings', 't2s-options' ) ?>
            <a href="<?php echo preg_replace('/\\&.*/', '', $_SERVER['REQUEST_URI']); ?>#t2s-option-form" class="add-new-h2">
                <?php _e( 'Add New', 't2s-options' ) ?>
            </a>
        </h2>

        <?php echo $message; ?>
        <br />
        <?php if (count($options) > 0) : ?>
            <div class="wpbody-content">
                <table class="wp-list-table widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column " style="min-width: 100px"><?php _e( 'Title', 't2s-options' ) ?></th>
                            <th scope="col" class="manage-column column-title"><?php _e( 'Field Name', 't2s-options' ) ?></th>
                            <th scope="col" class="manage-column column-title"><?php _e( 'Value', 't2s-options' ) ?></th>
                            <th scope="col" class="manage-column column-title"><?php _e( 'PHP Code', 't2s-options' ) ?></th>
                            <th scope="col" class="manage-column column-title"><?php _e( 'Shortcode', 't2s-options' ) ?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th scope="col" class="manage-column column-title"><?php _e( 'Title', 't2s-options' ) ?></th>
                            <th scope="col" class="manage-column column-title"><?php _e( 'Field Name', 't2s-options' ) ?></th>
                            <th scope="col" class="manage-column column-title"><?php _e( 'Value', 't2s-options' ) ?></th>
                            <th scope="col" class="manage-column column-title"><?php _e( 'PHP Code', 't2s-options' ) ?></th>
                            <th scope="col" class="manage-column column-title"><?php _e( 'Shortcode', 't2s-options' ) ?></th>
                        </tr>
                    </tfoot>
                    <tbody id="the-list">
                        <?php $trclass = 'class="alternate"';
                        foreach ($options as $option) :
                        ?>
                            <tr <?php echo $trclass; ?> rowspan="2">
                                <td>
                                    <?php echo $option->label; ?>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo preg_replace('/\\&.*/', '', $_SERVER['REQUEST_URI']); ?>&amp;id=<?php echo $option->id; ?>#t2s-option-form">
                                                <?php _e( 'Edit', 't2s-options' ) ?>
                                            </a>
                                        </span>
                                        |
                                        <span class="delete">
                                            <a onclick="return confirm('Confirm Delete?')" class="submitdelete" title="Delete <?php echo $option->label; ?>" href="<?php echo preg_replace('/\\&.*/', '', $_SERVER['REQUEST_URI']); ?>&del=<?php echo $option->id; ?>">
                                                <?php _e( 'Delete', 't2s-options' ) ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <input style="font-size:12px;" type="text" onclick="this.select();" onfocus="this.select();" readonly="readonly"
                                    class="shortcode-in-list-table wp-ui-text-highlight code" value="<?php echo $option->name; ?>" />
                                </td>
                                <td>
                                    <div style="overflow:auto;"><?php echo $option->value; ?></div>
                                </td>
                                <td>
                                    <div style="overflow:auto;"><code><?php echo t2s_option('<?php echo $option->name; ?>'); ?></code></div>
                                </td>
                                <td>
                                    <div style="overflow:auto;"><code>[t2s_option field="<?php echo $option->name; ?>"]</code></div>
                                </td>
                            </tr>
                        <?php
                            $trclass = $trclass == 'class="alternate"' ? '' : 'class="alternate"';
                        endforeach; ?>
                    </tbody>
                </table>
            </div>
            <br />
        <?php endif; ?>

        <hr>
        <form method="post" action="<?php echo preg_replace('/\\&.*/', '', $_SERVER['REQUEST_URI']); ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>" />
            <h3 id="t2s-option-form"><?php _e( 'Add Option', 't2s-options'  ) ?></h3>
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="label">*<?php _e( 'Title', 't2s-options' ) ?>:</label>
                            </td>
                        <td>
                            <input name="label" required="required" type="text" id="label" value="<?php echo $label; ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="name">*<?php _e( 'Field Name', 't2s-options' ) ?>:</label>
                            </td>
                        <td>
                            <input name="name" required="required" type="text" id="name" value="<?php echo $name; ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="value">*<?php _e( 'Value', 't2s-options' ) ?>:</label>
                            </td>
                        <td>
                            <textarea required="required" name="value" rows="7" cols="40" type="text" id="value" class="regular-text code"><?php echo $value; ?></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes'); ?>">
            </p>
        </form>

    </div>
<?php
}


/**
 * @param string $name
 *
 * @return bool|null|string
 */
function t2s_option($name)
{
    global $wpdb, $T2S_TABLE;

    if ('' != $name) :
        return $wpdb->get_var($wpdb->prepare("SELECT value FROM $T2S_TABLE WHERE name = %s LIMIT 1", $name));
    else :
        return false;
    endif;
}

/**
 * @param $name
 * @param bool $collection (optional)
 *
 * @return array(string|int, string|int...) or array('label' => string|int, 'value' => string|int);
 */
function t2s_options($name, $collection = false)
{
    global $wpdb, $T2S_TABLE;
    if ('' != $name) :
        $list = $wpdb->get_results($wpdb->prepare("SELECT label, value FROM $T2S_TABLE WHERE name = %s ", $name), ARRAY_A);
        $array = array();
        foreach ($list as $key => $name) :
            if ($collection) :
                $array[] = array('label' => $name['label'], 'value' => $name['value']);
            else :
                $array[] = $name['value'];
            endif;
        endforeach;

        return $array;
    else :
        return false;
    endif;
}

// Tutorial on Help Button
function t2so_plugin_help($screen)
{
    $screen = get_current_screen();
    $help_content = '<br>'
        . __('Single Option', 't2s-options')
        . '<br /><code>'
        . htmlentities('echo t2s_option(\'key\');')
        . '</code><br /><br />'
        . __('The same field can retrieve an array.', 't2s-options')
        . '<br><code>'
        . htmlentities('foreach (t2s_options(\'key\') as $value ) : ')
        . '<br />    echo $value; <br /> '
        . htmlentities('endforeach;')
        . '</code><br /><br /> '
        . __('Adding a second parameter could obtaining the title.', 't2s-options')
        . ' <br><code>'
        . htmlentities('foreach (t2s_options(\'key\', true) as $output ) : ')
        . '<br />    echo $output["label"] . " - " . $output["value"]; <br /> '
        . htmlentities('endforeach;')
        . '</code> <br /><br /><br />';

    $screen->add_help_tab(array(
        'id'      => 't2so_plugin_help_screen',
        'title'   => __('Help', 't2s-options'),
        'content' => $help_content,
    ));
}
add_action('current_screen', 't2so_plugin_help', 10, 3);

function t2so_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'field' => '',
        'echo' => true
    ), $atts);

    $atts['field'] = stripslashes_deep(filter_var($atts['field'], FILTER_SANITIZE_SPECIAL_CHARS));

    $output = $atts['before'] . t2s_option($atts['field']) . $atts['after'];

    if ($atts['echo']) :
        echo $output;
    else :
        return $output;
    endif;

}
add_shortcode('t2s_option', 't2so_shortcode');
