<?php
/**
 * @package       OneAll Social Login
 * @copyright     Copyright 2011-2017 http://www.oneall.com
 * @license       GNU/GPL 2 or later
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 */

/**
 * Return plugin description
 * @return array plugin description
 */
function oa_social_login_info()
{
    return [
        'name' => 'OneAll Social Login',
        'description' => 'Allow your visitors to comment, login and register with 30+ Social Networks like Twitter, Facebook, LinkedIn, Instagram, Вконтакте, Google or Yahoo.',
        'website' => 'http://www.oneall.com',
        'author' => 'OneAll',
        'authorsite' => 'http://www.oneall.com',
        'version' => '2.6.0',
        'compatibility' => '16*,18*'
    ];
}

/**
 * Return plugin info
 * @param string info to get
 * @return string plugin info
 */
function oa_social_login_get_info($what)
{
    $oa_social_login_info = oa_social_login_info();

    if (isset($oa_social_login_info[$what]))
    {
        return $oa_social_login_info[$what];
    }

    return null;
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

    // Is desactivated ?
    $query = $db->simple_select("settinggroups", "gid", "name = 'oa_social_login'", array("limit" => 1));
    $is_existing_group = $db->fetch_field($query, "gid");

    // Case : deactivated -> activated
    if (empty($is_existing_group))
    {
        // Get group id of existing settings (only group is deleted in deactivation)
        $query = $db->simple_select("settings", "gid", "name = 'oa_social_login_subdomain'", array("limit" => 1));
        $current_gid = $db->fetch_field($query, "gid");

        // Get max position
        $query = $db->simple_select("settinggroups", "disporder", "", array("limit" => 1, "order_by" => 'disporder', "order_dir" => 'DESC'));
        $max_disporder = (int) $db->fetch_field($query, "disporder");

        // Reinsert plugin settings
        $save_settinggroups = array();

        // Is the current gid used (if user modify manually gids)
        $query = $db->simple_select("settinggroups", "gid", "gid = '" . $current_gid . "'", array("limit" => 1));
        $is_existing_gid = $db->fetch_field($query, "gid");

        // Unused
        if (empty($is_existing_gid))
        {
            $save_settinggroups['gid'] = $current_gid;
        }
        // Already existing gid
        else
        {
            // Get max gid
            $query = $db->simple_select("settinggroups", "gid", "", array("limit" => 1, "order_by" => 'gid', "order_dir" => 'DESC'));
            $max_gid = (int) $db->fetch_field($query, "gid");
            $max_gid = $max_gid + 1;

            // Update all settings
            $db->update_query("settings", array("gid" => $max_gid), "gid='" . $is_existing_gid . "'");

            // Use new gid
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
    }
}

/**
 * Check if Social Login Plugin is installed
 * @return boolean
 */
function oa_social_login_is_installed()
{
    global $cache;

    // Read cache
    $installed_plugins = $cache->read('cached_plugins');

    // Check if installed

    return ((!empty($installed_plugins[oa_social_login_get_info('name')])) ? true : false);
}

/**
 * Adds the Social Login Plugin tags to the templates
 * @return void
 */
function oa_social_login_add_to_templates()
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    // Library
    find_replace_templatesets('headerinclude', '#' . preg_quote('{$stylesheets}') . '#i', '{$stylesheets}{$oneall_social_login_library}');

    // Social Login
    find_replace_templatesets('header_welcomeblock_guest', '#' . preg_quote('</table>') . '#i', '{$oa_login_login_page}</table>');
    find_replace_templatesets('header', '#' . preg_quote('{$pm_notice}') . '#i', '{$oa_login_other_page}{$pm_notice}');
    find_replace_templatesets('member_register', '#' . preg_quote('{$header}') . '#i', '{$header}{$oa_login_registration_page}');
    find_replace_templatesets('index', '#' . preg_quote('{$header}') . '#i', '{$header}{$oa_login_main_page}');
    find_replace_templatesets('error_nopermission', '#' . preg_quote('</table>') . '#i', '<!-- oa_login_member_page --></table>');
    find_replace_templatesets('member_login', '#' . preg_quote('</table>') . '#i', '{$oa_login_member_page}</table>');

    // Social Link
    find_replace_templatesets('usercp_profile', '#' . preg_quote('{$customtitle}') . '#i', '{$oa_social_link}{$customtitle}');
}

/**
 * Remove the Social Login Plugin tags from the templates
 * @return void
 */
function oa_social_login_cleanup_templates()
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    // Library
    find_replace_templatesets('headerinclude', '#' . preg_quote('{$oneall_social_login_library}') . '#i', '');

    // Social Login
    find_replace_templatesets('header_welcomeblock_guest', '#' . preg_quote('{$oa_login_login_page}') . '#i', '');
    find_replace_templatesets('header', '#' . preg_quote('{$oa_login_other_page}') . '#i', '');
    find_replace_templatesets('member_register', '#' . preg_quote('{$oa_login_registration_page}') . '#i', '');
    find_replace_templatesets('index', '#' . preg_quote('{$oa_login_main_page}') . '#i', '');
    find_replace_templatesets('error_nopermission', '#' . preg_quote('<!-- oa_login_member_page -->') . '#i', '');
    find_replace_templatesets('member_login', '#' . preg_quote('{$oa_login_member_page}') . '#i', '');

    // Social Link
    find_replace_templatesets('usercp_profile', '#' . preg_quote('{$oa_social_link}') . '#i', '');
}

/**
 * Social Login Plugin Setup
 * @return void
 */
function oa_social_login_install()
{
    global $db, $lang, $mybb, $cache;

    // Load language
    if (!$lang->oa_social_login)
    {
        $lang->load('oa_social_login');
    }

    // Add plugin settings
    oa_social_login_add_settings();

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
            utid INT(10) NOT NULL,
            identity_token CHAR(36) NOT NULL,
            provider CHAR(36) NOT NULL,
            date_creation INT(10)
            ) ENGINE=MyISAM{$collation};");
    }

    // Add our templates
    oa_social_login_add_templates();

    // Add our CSS
    oa_social_login_add_stylesheet();

    // Create cache
    $info = oa_social_login_info();
    $shadePlugins = $cache->read('cached_plugins');
    $shadePlugins[$info['name']] = [
        'title' => $info['name'],
        'version' => $info['version']
    ];
    $cache->update('cached_plugins', $shadePlugins);

    // Place tags in templates
    oa_social_login_add_to_templates();

    // Rebuild the settings file.
    rebuild_settings();
}

/**
 * Social Login Plugin Uninstall
 * @return void
 */
function oa_social_login_uninstall()
{
    global $db, $cache;

    // Delete settings.
    $query = $db->simple_select('settings', 'sid', "name LIKE 'oa_social_login_%'");
    while ($sid = $db->fetch_field($query, 'sid'))
    {
        $db->delete_query('settings', "sid='" . intval($sid) . "'");
    }

    // Delete settings group.
    $db->delete_query('settinggroups', "name='oa_social_login'");

    // Remove User Token table.
    if ($db->table_exists('oa_social_login_user_token'))
    {
        // Keep this table, so that relationshops are not lost when re-installing
        // $collation = $db->drop_table('oa_social_login_user_token');
    }

    // Remove Identity Token table.
    if ($db->table_exists('oa_social_login_identity_token'))
    {
        // Keep this table, so that relationshops are not lost when re-installing
        // $collation = $db->drop_table('oa_social_login_identity_token');
    }

    // Delete templates.
    oa_social_login_delete_templates();

    // Delete stylesheet.
    oa_social_login_delete_stylesheet();

    // Remove plugin from templates.
    oa_social_login_cleanup_templates();

    // Delete plugin from cache
    $cached_plugins = $cache->read('cached_plugins');
    unset($cached_plugins[oa_social_login_get_info('name')]);

    // Update cache.
    $cache->update('cached_plugins', $cached_plugins);

    // Rebuild the settings file.
    rebuild_settings();
}

/**
 * Add stylesheets to plugin. All stylsheets will be merged
 * @return void
 */
function oa_social_login_add_stylesheet()
{
    global $db, $mybb;
    require_once MYBB_ROOT . $mybb->config['admin_dir'] . "/inc/functions_themes.php";

    // Read our CSS
    $css_file_name = 'oa_social_login.css';
    $css_file_path = (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . $css_file_name);
    $css_file_contents = file_get_contents($css_file_path);

    // myBB Main Style, global.css
    $themestylesheets_tid = 1;

    // Add stylesheet to the master template.
    $css_stylesheet = array(
        'name' => $css_file_name,
        'tid' => $themestylesheets_tid,
        'attachedto' => '',
        'stylesheet' => $css_file_contents,
        'cachefile' => $css_file_name,
        'lastmodified' => TIME_NOW
    );

    // Add identifier
    $css_stylesheet['sid'] = $db->insert_query('themestylesheets', $css_stylesheet);

    cache_stylesheet($css_stylesheet['tid'], $css_stylesheet['cachefile'], $css_stylesheet['stylesheet']);
    update_theme_stylesheet_list($themestylesheets_tid);
}

/**
 * Remove stylesheet
 * @param string name
 * @return void
 */
function oa_social_login_delete_stylesheet($name = 'oa_social_login.css')
{
    global $mybb, $db;
    require_once MYBB_ROOT . $mybb->config['admin_dir'] . "/inc/functions_themes.php";

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
function oa_social_login_add_templates($prefix = 'oasociallogin')
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
function oa_social_login_delete_templates($prefix = 'oasociallogin')
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
 * Add OneAll Social Login Settings
 * @param array $setting_list array of all settings
 * @return void
 */
function oa_social_login_add_settings()
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

    $setting_list = oa_social_login_get_settings();

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
