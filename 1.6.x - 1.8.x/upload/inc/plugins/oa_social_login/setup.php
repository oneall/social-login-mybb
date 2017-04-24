<?php

// **************************************************
// Plugin Setup / Uninstall / Update
// **************************************************

/**
 * Plugin description
 * @return array plugin description
 */
function oa_social_login_info()
{
    return [
        'name' => 'OneAll Social Login',
        'description' => 'Allow your visitors to comment, login and register with 25+ social networks like Twitter, Facebook, LinkedIn, Instagram, Вконтакте, Google or Yahoo.',
        'website' => 'http://www.oneall.com',
        'author' => 'Damien ZARA',
        'authorsite' => 'http://www.oneall.com',
        'version' => '1.0',
        'compatibility' => '16*,18*'
    ];
}

/**
 * Deactivate Social Login
 * @return void
 */
function oa_social_login_deactivate()
{
    global $db;

    // Delete Plugin settings
    $db->delete_query("settinggroups", "name='oa_social_login'");

    // Rebuild the settings file.
    rebuild_settings();
}

/**
 * Activate Social Login
 * @return void
 */
function oa_social_login_activate()
{
    global $db, $lang;

    // Load language
    if (!$lang->oa_social_login)
    {
        $lang->load('oa_social_login');
    }

    // is desactivated ?
    $query = $db->simple_select("settinggroups", "gid", "name = 'oa_social_login'", array("limit" => 1));
    $is_existing_group = $db->fetch_field($query, "gid");

    // case : deactivated -> activated
    if (empty($is_existing_group))
    {
        // get group id of existing settings (only group is deleted in deactivation)
        $query = $db->simple_select("settings", "gid", "name = 'oa_social_login_enabled'", array("limit" => 1));
        $current_gid = $db->fetch_field($query, "gid");

        // get max position
        $query = $db->simple_select("settinggroups", "disporder", "", array("limit" => 1, "order_by" => 'disporder', "order_dir" => 'DESC'));
        $max_disporder = (int) $db->fetch_field($query, "disporder");

        //reinsert plugin settings
        $save_settinggroups = array();

        // is the current gid used (if user modify manually gids)
        $query = $db->simple_select("settinggroups", "gid", "gid = '" . $current_gid . "'", array("limit" => 1));
        $is_existing_gid = $db->fetch_field($query, "gid");

        // unused
        if (empty($is_existing_gid))
        {
            $save_settinggroups['gid'] = $current_gid;
        }
        else //already existing gid
        {
            //get max gid
            $query = $db->simple_select("settinggroups", "gid", "", array("limit" => 1, "order_by" => 'gid', "order_dir" => 'DESC'));
            $max_gid = (int) $db->fetch_field($query, "gid");
            $max_gid = $max_gid + 1;

            // Update all settings
            $db->update_query("settings", array("gid" => $max_gid), "gid='" . $is_existing_gid . "'");

            $save_settinggroups['gid'] = $max_gid;
        }

        $save_settinggroups['name'] = 'oa_social_login';
        $save_settinggroups['title'] = $db->escape_string($lang->setting_group_oa_social_login);
        $save_settinggroups['description'] = $db->escape_string($lang->setting_group_oa_social_login_desc);
        $save_settinggroups['disporder'] = $max_disporder++;
        $save_settinggroups['isdefault'] = 0;
        $db->insert_query("settinggroups", $save_settinggroups);

        // Rebuild the settings file.
        rebuild_settings();

        // case : install & activation
    }
    else
    {
        // nothing to do
    }
}

/**
 * Check if Social Login Plugin is installed
 * @return boolean
 */
function oa_social_login_is_installed()
{
    global $cache;

    $oa_plugin_info = oa_social_login_info();
    $installed_plugins = $cache->read("cached_plugins");
    if ($installed_plugins[$oa_plugin_info['name']])
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * Add Social Login Plugin tags in templates
 * @return void
 */
function oa_social_login_set_plugin()
{
    global $mybb;

    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    // 1.8 has jQuery, not Prototype
    if ($mybb->version_code >= 1700)
    {
        //plugin
        find_replace_templatesets('header_welcomeblock_guest', '#' . preg_quote('{$lang->remember_me}</label>') . '#i', '{$lang->remember_me}</label>{$oa_login_login_page}');
        find_replace_templatesets('header', '#' . preg_quote('{$pm_notice}') . '#i', '{$oa_login_other_page}{$pm_notice}');
        find_replace_templatesets('member_register', '#' . preg_quote('{$header}') . '#i', '{$header}{$oa_login_registration_page}');
        find_replace_templatesets('index', '#' . preg_quote('{$header}') . '#i', '{$header}{$oa_login_main_page}');
        find_replace_templatesets('error_nopermission', '#' . preg_quote('</table>') . '#i', '{$oa_login_member_page}</table>');
        find_replace_templatesets('member_login', '#' . preg_quote('</table>') . '#i', '{$oa_login_member_page}</table>');
    }
    else
    {
        //plugin
        find_replace_templatesets('header_welcomeblock_guest', '#' . preg_quote('{$lang->welcome_register}</a>)</span>') . '#i', '{$lang->welcome_register}</a>)</span>{$oa_login_login_page}');
        find_replace_templatesets('header', '#' . preg_quote('{$pm_notice}') . '#i', '{$oa_login_other_page}{$pm_notice}');
        find_replace_templatesets('member_register', '#' . preg_quote('{$header}') . '#i', '{$header}{$oa_login_registration_page}');
        find_replace_templatesets('index', '#' . preg_quote('{$header}') . '#i', '{$header}{$oa_login_main_page}');
        find_replace_templatesets('error_nopermission', '#' . preg_quote('</table>') . '#i', '{$oa_login_member_page}</table>');
        find_replace_templatesets('member_login', '#' . preg_quote('</table>') . '#i', '{$oa_login_member_page}</table>');
    }
}

/**
 * Remove Social Login Plugin tags of templates
 * @return void
 */
function oa_social_login_remove_plugin()
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    // library
    find_replace_templatesets('headerinclude', '#' . preg_quote('{$oa_library}') . '#i', '');

    // plugin
    find_replace_templatesets('header_welcomeblock_guest', '#' . preg_quote('{$oa_login_login_page}') . '#i', '');
    find_replace_templatesets('header', '#' . preg_quote('{$oa_login_other_page}') . '#i', '');
    find_replace_templatesets('member_register', '#' . preg_quote('{$oa_login_registration_page}') . '#i', '');
    find_replace_templatesets('index', '#' . preg_quote('{$oa_login_main_page}') . '#i', '');
    find_replace_templatesets('error_nopermission', '#' . preg_quote('{$oa_login_member_page}') . '#i', '');
    find_replace_templatesets('member_login', '#' . preg_quote('{$oa_login_member_page}') . '#i', '');
}

/**
 * Social Login Plugin Setup
 * @return void
 */
function oa_social_login_install()
{
    global $db, $lang, $mybb, $cache, $oa_settings;

    // Load language
    if (!$lang->oa_social_login)
    {
        $lang->load('oa_social_login');
    }

    // Add plugin settings
    add_settings($oa_settings);

    // Add User Token table
    if (!$db->table_exists('oa_social_login_user_token'))
    {
        $collation = $db->build_create_table_collation();
        $db->write_query("CREATE TABLE " . TABLE_PREFIX . "oa_social_login_user_token(
            id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            uid INT(10) NOT NULL,
            user_token CHAR(36) NOT NULL,
            date_creation INT(10)
            ) ENGINE=MyISAM{$collation};");
    }

    // Add identity Token table
    if (!$db->table_exists('oa_social_login_identity_token'))
    {
        $collation = $db->build_create_table_collation();
        $db->write_query("CREATE TABLE " . TABLE_PREFIX . "oa_social_login_identity_token(
            id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            mybb_oa_social_login_user_tokenid INT(10) NOT NULL,
            identity_token CHAR(36) NOT NULL,
            identity_provider CHAR(36) NOT NULL,
            date_creation INT(10)
            ) ENGINE=MyISAM{$collation};");
    }

    // Insert our templates
    add_templates();

    // Insert our css
    add_stylesheet();

    // Create cache
    $info = oa_social_login_info();
    $shadePlugins = $cache->read('cached_plugins');
    $shadePlugins[$info['name']] = [
        'title' => $info['name'],
        'version' => $info['version']
    ];
    $cache->update('cached_plugins', $shadePlugins);

    // Place tags in templates
    oa_social_login_set_plugin();

    // Add Social Login Library
    find_replace_templatesets('headerinclude', '#' . preg_quote('{$stylesheets}') . '#i', '{$stylesheets}{$oa_library}');

    // Rebuild the settings file.
    rebuild_settings();
}

/**
 * Social Login Plugin Uninstall
 * @return void
 */
function oa_social_login_uninstall()
{
    global $db, $cache, $lang;

    if (!$lang->oa_social_login)
    {
        $lang->load('oa_social_login');
    }

    // Delete Group settings
    $db->delete_query('settinggroups', "name='oa_social_login'");

    // Safety delete (if plugin was desactivated before)
    $name = $db->escape_string('oa_social_login');
    $where = "name LIKE '{$name}_%'";

    // Delete all settings.
    $query = $db->simple_select('settings', 'sid', $where);
    while ($sid = $db->fetch_field($query, 'sid'))
    {
        $db->delete_query('settings', "sid='{$sid}'");
    }

    // Remove User Token table
    if ($db->table_exists('oa_social_login_user_token'))
    {
        $collation = $db->drop_table('oa_social_login_user_token');
    }

    // Remove identity Token table
    if ($db->table_exists('oa_social_login_identity_token'))
    {
        $collation = $db->drop_table('oa_social_login_identity_token');
    }

    // Delete templates
    delete_templates();

    // Delete stylesheet
    delete_stylesheet();

    // Remove plugin of templates
    oa_social_login_remove_plugin();

    // Delete plugin from cache
    $info = oa_social_login_info();
    $shadePlugins = $cache->read('cached_plugins');
    unset($shadePlugins[$info['name']]);
    $cache->update('cached_plugins', $shadePlugins);

    // Rebuild the settings file.
    rebuild_settings();
}

/**
 * Add stylesheets to plugin. All steelsheet of directory css will be merged
 * @return void
 */
function add_stylesheet()
{
    global $db;
    require_once MYBB_ROOT . "admin/inc/functions_themes.php";

    $name = 'oa_social_login.css';

    // Insert our css
    $css_directory = new DirectoryIterator(dirname(__FILE__) . '/css');
    $stylesheet_content = "";
    foreach ($css_directory as $file)
    {
        $is_file = !$file->isDot() and !$file->isDir();

        if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'css' and $is_file)
        {
            $stylesheet_content .= file_get_contents($file->getPathName());
        }
    }

    // myBB Main Style
    $tid = 1;

    // Add stylesheet to the master template so it becomes inherited.
    $oa_stylesheet = array(
        'sid' => null,
        'name' => $name,
        'tid' => $tid,
        'stylesheet' => $db->escape_string($stylesheet_content),
        'cachefile' => $name,
        'lastmodified' => TIME_NOW
    );
    $sid = $db->insert_query('themestylesheets', $oa_stylesheet);
    $oa_stylesheet['sid'] = intval($sid);

    cache_stylesheet($oa_stylesheet['tid'], $oa_stylesheet['cachefile'], $oa_stylesheet['stylesheet']);
    update_theme_stylesheet_list($tid); // includes all children
}

/**
 * Remove stylesheet
 * @param string name
 * @return void
 */
function delete_stylesheet($name = 'oa_social_login.css')
{
    global $db;
    require_once MYBB_ROOT . "admin/inc/functions_themes.php";

    // Delete stylesheets.
    $where = "name='" . $db->escape_string($name) . "'";
    $query = $db->simple_select('themestylesheets', 'tid,name', $where);

    while ($stylesheet = $db->fetch_array($query))
    {
        @unlink(MYBB_ROOT . "cache/themes/{$stylesheet['tid']}_{$stylesheet['name']}");
        @unlink(MYBB_ROOT . "cache/themes/theme{$stylesheet['tid']}/{$stylesheet['name']}");

        update_theme_stylesheet_list($stylesheet['tid']);
    }

    $db->delete_query('themestylesheets', $where);
}

/**
 * Create and update template group and templates.
 *
 * @param string Prefix for the template group
 * @param string Title for the template group
 * @param array List of templates to be added to this group.
 */
function add_templates($prefix = 'oasociallogin')
{
    global $db;

    // Get our templates
    $directory = new DirectoryIterator(dirname(__FILE__) . '/templates');
    $templates = [];
    foreach ($directory as $file)
    {
        $is_file = !$file->isDot() and !$file->isDir();

        if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html' and $is_file)
        {
            $templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
        }
    }

    // create template group:
    $group_title = 'OneAll Social Login';
    $db->insert_query('templategroups', array('prefix' => $db->escape_string($prefix), 'title' => $db->escape_string($group_title)));

    // Update or create templates.
    foreach ($templates as $name => $html)
    {
        $template = array('title' => $db->escape_string("{$prefix}_{$name}"),
            'template' => $db->escape_string($html),
            'version' => 1,
            'sid' => -2,
            'dateline' => TIME_NOW);

        // Create
        $db->insert_query('templates', $template);

        // Remove this template from the earlier queried list.
        unset($templates[$name]);
    }
}

/**
 * Delete template and group
 *
 * @return void
 */
function delete_templates($prefix = 'oasociallogin')
{
    global $db;

    // Query the template groups
    $group_where = "prefix='" . $db->escape_string($prefix) . "'";
    $tpl_where = "title LIKE '" . $db->escape_string($prefix) . "%'";

    // Delete template groups.
    $db->delete_query('templategroups', $group_where);

    // Delete templates belonging to template groups.
    $db->delete_query('templates', $tpl_where);
}

/**
 * Delete Settings
 *
 * @return void
 */
function delete_settings()
{
    global $db;

    // Delete Group settings
    $db->delete_query('settinggroups', "name='oa_social_login'");

    // Safety delete (if plugin was desactivated before)
    $name = $db->escape_string('oa_social_login');
    $where = "name LIKE '{$name}_%'";

    // Delete all settings.
    $query = $db->simple_select('settings', 'sid', $where);
    while ($sid = $db->fetch_field($query, 'sid'))
    {
        $db->delete_query('settings', "sid='{$sid}'");
    }
}

/**
 * Add OneAll Social Login Settings
 * @param array $setting_list array of all settings
 * @return void
 */
function add_settings($setting_list)
{
    global $db, $lang;

    $name = 'oa_social_login';
    $title = $lang->setting_group_oa_social_login;
    $description = $lang->setting_group_oa_social_login_desc;

    // Group array for inserts/updates.
    $group = array();
    $group['name'] = $db->escape_string($name);
    $group['title'] = $db->escape_string($title);
    $group['description'] = $db->escape_string($description);

    //get max position
    $query = $db->simple_select("settinggroups", "MAX(disporder) AS max");
    $row = $db->fetch_array($query);
    $group['disporder'] = $row['max'] + 1;

    // Create Group
    $gid = $db->insert_query("settinggroups", $group);

    // Create settings.
    foreach ($setting_list as $key => $setting)
    {
        // Filter valid entries.
        $setting = array_intersect_key($setting,
            array(
                'title' => 0,
                'description' => 0,
                'optionscode' => 0,
                'value' => 0
            ));

        // Escape input values.
        $setting = array_map(array($db, 'escape_string'), $setting);

        // Add missing default values.
        $disporder += 1;

        $setting = array_merge(
            array('description' => '',
                'optionscode' => 'yesno',
                'value' => '0',
                'disporder' => $disporder),
            $setting);
        $setting['name'] = $db->escape_string($name . '_' . $key);
        $setting['gid'] = $gid;

        // It doesn't exist, create it.
        $db->insert_query("settings", $setting);
    }

    // Rebuild the settings file.
    rebuild_settings();
}
