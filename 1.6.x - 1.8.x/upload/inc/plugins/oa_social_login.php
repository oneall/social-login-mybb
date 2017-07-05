<?php
/**
 * @package   	OneAll Social Login
 * @copyright 	Copyright 2011-2017 http://www.oneall.com
 * @license   	GNU/GPL 2 or later
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
if (!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

global $mybb;

require_once MYBB_ROOT . "inc/plugins/oa_social_login/settings.php";
require_once MYBB_ROOT . "inc/plugins/oa_social_login/setup.php";
require_once MYBB_ROOT . "inc/plugins/oa_social_login/communication.php";
require_once MYBB_ROOT . "inc/plugins/oa_social_login/tools.php";

// All pages
$plugins->add_hook('global_start', 'oa_social_login_load_library');

// 1.8 has jQuery, not Prototype
if ($mybb->version_code >= 1700)
{
    $plugins->add_hook('global_intermediate', 'oa_social_login_load_plugin_hook_any');
}
else
{
    $plugins->add_hook('global_start', 'oa_social_login_load_plugin_hook_any');
}

// No permission page
$plugins->add_hook('no_permission', 'oa_social_login_load_plugin_hook_error_no_permission');

// Callback handler
$plugins->add_hook('global_end', 'oa_social_login_callback');

// Social Link
$plugins->add_hook('usercp_profile_start', 'oa_social_login_social_link', 25);

// Admin CP
if (defined('IN_ADMINCP'))
{
    // CSS
    $plugins->add_hook('admin_page_output_header', 'oa_social_login_admin_header');

    // JavaScript
    $plugins->add_hook('admin_page_output_footer', 'oa_social_login_admin_footer');

    // Ajax
    $plugins->add_hook('admin_load', 'oa_social_login_admin_ajax');

    // Settings changed
    $plugins->add_hook('admin_config_settings_change', 'oa_social_login_settings_change');
}

// **************************************************
// General Hooks
// **************************************************

/**
 * Display Social Link in the user's Edit Profile page.
 * @return void
 */
function oa_social_login_social_link()
{
     global $mybb, $templates, $theme, $oa_social_link;

    // Contents to display
    $contents = '';

    // Is the plugin setup?
    $is_setup = false;

    // Enabled Providers
    $providers = array();

    // Make sure it's enabled
    if ( ! empty ($mybb->settings['oa_social_login_link_user_profile']))
    {
        foreach ($mybb->settings as $setting_name => $value)
        {
            // Is this a provider setting?
            if (strpos($setting_name, 'oa_social_login_provider_') !== false)
            {
                // Is the provider enabled?
                if (!empty($value))
                {
                    $providers[] = str_replace('oa_social_login_provider_', '', $setting_name);
                }
            }
            // Is this the subdomain setting?
            elseif ($setting_name == 'oa_social_login_subdomain')
            {
                // Has the subdomain been specified?
                if (!empty($value))
                {
                    $is_setup = true;
                }
            }
        }
    }

    // If it's not enabled, do not display it
    if ($is_setup && count ($providers) > 0)
    {
        // Make sure we have a user
        if (is_object($mybb) && isset($mybb->user) && !empty($mybb->user['uid']))
        {
            // Template Varsd
            global $oneall_social_login_cfg;
            $oneall_social_login_cfg = array(
                'caption' => $mybb->settings['oa_social_login_link_user_profile_caption'],
                'user_token' => oa_social_login_get_user_token_by_userid($mybb->user['uid']),
                'position' => 'oneall_social_login_link_' . mt_rand(1000, 9999),
                'providers' => implode("','", $providers),
                'callback_uri' => oa_social_login_get_current_url(),
                'css_uri' => '');

            // Read Template
            $contents = $templates->get('oasociallogin_plugin_social_link');
        }
    }

    eval("\$oa_social_link .= \"" . $contents . "\";");
}

/**
 * Display Social Login in the no permission template
 * @return void
 */
function oa_social_login_load_plugin_hook_error_no_permission()
{
    global $mybb, $templates;

    // User is not logged in
    if (empty ($mybb->user['uid']))
    {
        $enabled_top = ( ! empty ($mybb->settings['oa_social_login_other_page']) ? true : false);
        $enabled_current = ( ! empty ($mybb->settings['oa_social_login_member_page']) ? true : false);

        // Is Social Login enabled on this page?
        if ($enabled_current && ! $enabled_top)
        {
            // We can't use variables in this template, so we need to change it's code directly
            if(!$templates->cache['error_nopermission'])
            {
                $templates->cache('error_nopermission');
            }
            $templates->cache['error_nopermission'] = str_replace('<!-- oa_login_member_page -->', oa_social_login_load_plugin ('member_page', true), $templates->cache['error_nopermission']);
        }
    }
}

/**
 * Integrate Social Login into templates
 * @return void
 */
function oa_social_login_load_plugin_hook_any()
{
    global $mybb;

    // User is not logged in
    if (empty ($mybb->user['uid']))
    {
        // Available positions
        $positions = array();
        $positions[] = 'other_page';
        $positions[] = 'main_page';
        $positions[] = 'login_page';
        $positions[] = 'registration_page';

        // Add
        foreach ($positions as $position)
        {
            $enabled_top = ( ! empty ($mybb->settings['oa_social_login_other_page']) ? true : false);
            $enabled_current = ( ! empty ($mybb->settings['oa_social_login_' . $position]) ? true : false);

            // Display only once per page
            if (in_array ($position, array ('main_page', 'registration_page')))
            {
                if ($enabled_current && ! $enabled_top)
                {
                    oa_social_login_load_plugin ($position);
                }
            }
            else
            {
                if ($enabled_current)
                {
                    oa_social_login_load_plugin ($position);
                }
            }
        }
    }
}

// **************************************************
// Admin Hooks
// **************************************************

/**
 * Add additional files to admin <head>...</head>
 * @return void
 */
function oa_social_login_admin_header()
{
    global $mybb, $page;

    // Are we in our settings?
    if ( ! empty ($mybb->input['action']) && $mybb->input['action'] == "change")
    {
        if ( ! empty ($mybb->input['gid']) && $mybb->input['gid'] == oa_social_login_settings_gid ())
        {
            // Stylesheet
            $page->extra_header .= '<link rel="stylesheet" type="text/css" href="/inc/plugins/oa_social_login/css/oa_social_login.css" />';

            // Ajax
            $page->extra_header .= '<script src="/inc/plugins/oa_social_login/js/admin.js"></script>';

            // MyBB < 1.8 has no jQuery
            if ($mybb->version_code < 1800)
            {
                $page->extra_header .= '<script src="/inc/plugins/oa_social_login/js/jquery.min.js"></script>';
            }
        }
    }
}

/**
 * Add JS into Admin Section
 * @return void
 */
function oa_social_login_admin_footer()
{
    global $mybb, $lang, $templates;

    // Load language
    $lang->load('oa_social_login');

    // Are we in our settings?
    if ( ! empty ($mybb->input["action"]) && $mybb->input["action"] == "change")
    {
        if ( ! empty ($mybb->input["gid"]) && $mybb->input["gid"] == oa_social_login_settings_gid ())
        {
            eval("\$oa_social_login_admin_footer  = \"" . $templates->get('oasociallogin_plugin_admin_js') . "\";");
            echo $oa_social_login_admin_footer ;
        }
    }
}

// **************************************************
// Load OneAll Plugin
// **************************************************

/**
 * Integrate Social Login library into header
 * @return void
 */
function oa_social_login_load_library()
{
    global $mybb, $templates, $oneall_social_login_library;

    // Subdomain
    $subdomain = oa_social_login_get_subdomain();

    // Plugin setup
    if (!empty($subdomain))
    {
        // Template Vars
        global $oneall_social_login_cfg;
        $oneall_social_login_cfg = array(
            'subdomain' => $subdomain
        );

        // Get template
        eval("\$oneall_social_login_library  = \"" . $templates->get('oasociallogin_plugin_library') . "\";");
    }
}

/**
 * Integrate Social Login into templates
 * @param  string $position page where social login is integrated
 * @param  boolean $return return the generated code or add as variabled
 * @return void
 */
function oa_social_login_load_plugin($position, $return = false)
{
    global $mybb, $templates, $theme, $oa_login_main_page, $oa_login_login_page, $oa_login_registration_page, $oa_login_other_page, $oa_login_member_page;

    // Contents to display
    $contents = '';

    // Is the plugin setup?
    $is_setup = false;

    // Get buttons
    $providers = array();
    foreach ($mybb->settings as $setting_name => $value)
    {
        // Is this a provider setting?
        if (strpos($setting_name, 'oa_social_login_provider_') !== false)
        {
            // Is the provider enabled?
            if (!empty($value))
            {
                $providers[] = str_replace('oa_social_login_provider_', '', $setting_name);
            }
        }
        // Is this the subdomain setting?
        elseif ($setting_name == 'oa_social_login_subdomain')
        {
            // Has the subdomain been specified?
            if (!empty($value))
            {
                $is_setup = true;
            }
        }
    }

    // If it's not enabled, do not display it
    if ($is_setup && ! empty ($position))
    {
        // Template Vars
        global $oneall_social_login_cfg;
        $oneall_social_login_cfg = array(
            'caption' => $mybb->settings['oa_social_login_' . $position . '_caption'],
            'position' => 'oneall_social_login_link_' . mt_rand(1000, 9999),
            'providers' => implode("','", $providers),
            'callback_uri' => oa_social_login_get_current_url(),
            'css_uri' => '');

        // Get template
        eval("\$oa_login_" . $position . " = \"" . $templates->get('oasociallogin_plugin_' . $position) . "\";");

        // Return value
        if ($return)
        {
            return ${'oa_login_' . $position};
        }
    }
}

/**
 * Generate a random password
 * @return string password
 */
function oa_social_login_generate_password()
{
    global $mybb;

    // Length?
    $password_length =  ( ! empty ($mybb->settings['maxpasswordlength']) ? max (8, $mybb->settings['maxpasswordlength']) : 8);

    // Generate
    return random_str($password_length, $mybb->settings['requirecomplexpasswords']);
}

/**
 * Custom Social Login redirection
 * @param  boolean $use_current_page_redirection Use current page for redirection
 * @param  string  $redirection_text             Text shown in page when user is redirected
 * @param  string  $redirection                  url for redirection
 * @return void
 */
function oa_social_login_redirect($use_current_page_redirection = true, $redirection_text = '', $redirection = null)
{
    global $mybb;

    $redirection_link = !$use_current_page_redirection && !empty($redirection) ? $redirection : oa_social_login_get_current_url();

    // User added a redirection link
    if (!empty($mybb->settings['oa_social_login_redirection']))
    {
        redirect($mybb->settings['oa_social_login_redirection'], $redirection_text);
    }
    else
    {
        redirect($redirection_link, $redirection_text);
    }
    exit();
}

/**
 * Group id of OneAll Social settings group.
 * @return int settings group id
 */
function oa_social_login_settings_gid()
{
    global $db;

    // Read group
    $query = $db->simple_select("settinggroups", "gid", "name = 'oa_social_login'", array("limit" => 1));
    $gid = $db->fetch_field($query, "gid");

    // Done
    return (! is_numeric ($gid) ? null : $gid);
}

/**
 * Hook called when updating the settings in the administration area
 * @return void
 */
function oa_social_login_settings_change ()
{
    global $mybb;

    // Make sure we are saving our settings
    if (isset ($mybb->input['upsetting']) && is_array ($mybb->input['upsetting']))
    {
        // Update the subdomain
        if (! empty ($mybb->input['upsetting']['oa_social_login_subdomain']))
        {
            // The full domain has been entered.
            if (preg_match("/([a-z0-9\-]+)\.api\.oneall\.com/i", $mybb->input['upsetting']['oa_social_login_subdomain'], $matches))
            {
                // Only keep the first part
                $mybb->input['upsetting']['oa_social_login_subdomain'] = $matches[1];
            }
        }

        // Update the Public Key
        if (! empty ($mybb->input['upsetting']['oa_social_login_public_key']))
        {
            $mybb->input['upsetting']['oa_social_login_public_key'] = trim ($mybb->input['upsetting']['oa_social_login_public_key']);
        }

        // Update the Private Key
        if (! empty ($mybb->input['upsetting']['oa_social_login_private_key']))
        {
            $mybb->input['upsetting']['oa_social_login_private_key'] = trim ($mybb->input['upsetting']['oa_social_login_private_key']);
        }
    }
}

/**
 * Hook for Autodetect/Verify Ajax in the administration area
 * @return void
 */
function oa_social_login_admin_ajax ()
{
    global $mybb;

    // Ajax call
    if (!empty($mybb->input['oa_social_login_task']))
    {
        switch ($mybb->input['oa_social_login_task'])
        {
            case 'verify':
                return oa_social_login_admin_ajax_verify_api_settings();
                break;

            case 'autodetect':
                return oa_social_login_autodetect_api_connection_handler();
                break;
        }
    }
}


/**
 * Autodetect API Connection Handler
 * @return string result test
 */
function oa_social_login_autodetect_api_connection_handler()
{
    global $mybb, $lang;

    // Make sure the user has the right to do this
    if ( ! $mybb->usergroup['cancp'])
    {
        die ('error|no_permission_to_access');
    }

    // Load language
    $lang->load('oa_social_login');

    // Check if CURL is available
    if (oa_social_login_check_curl_available())
    {
        // Check CURL HTTPS - Port 443
        if (oa_social_login_check_curl(true))
        {
            die ('success|curl_443|'.$lang->oa_social_login_api_curl_443);
        }

        // Check CURL HTTP - Port 80
        if (oa_social_login_check_curl(false))
        {
            die ('success|curl_80'.$lang->oa_social_login_api_curl_80);
        }

        // CURL ok, but ports blocked
        die ('error|curl_ports_blocked|'.$lang->oa_social_login_api_curl_blocked);
    }

    // Check if FSOCKOPEN is available
    if (check_fsockopen_available())
    {
        // Check FSOCKOPEN HTTPS - Port 443
        if (check_fsockopen(true))
        {
            die ('success|fsockopen_443|'.$lang->oa_social_login_api_fsockopen_443);
        }

        // Check FSOCKOPEN HTTP - Port 80
        if (check_fsockopen(false))
        {
            die ('success|fsockopen_80|'.$lang->oa_social_login_api_fsockopen_80);
        }

        // FSOCKOPEN ok, but ports blocked
        die ('success|fsockopen_ports_blocked|'.$lang->oa_social_login_api_fsockopen_blocked);
    }

    // No working handler found
    die('error|no_connection|'.$lang->oa_social_login_api_error);
}

/**
 * Check API Settings
 * @return void
 */
function oa_social_login_admin_ajax_verify_api_settings()
{
    global $mybb, $lang;

    // Make sure the user has the right to do this
    if ( ! $mybb->usergroup['cancp'])
    {
        die ('error|no_permission_to_access');
    }

    // Load language
    $lang->load('oa_social_login');

    // Read arguments.
    $api_subdomain = trim(strtolower($mybb->input['api_subdomain']));
    $api_key = trim($mybb->input['api_key']);
    $api_secret = trim($mybb->input['api_secret']);
    $api_connection_port = $mybb->input['api_connection_port'];
    $api_connection_handler = $mybb->input['api_connection_handler'];

    // Init status message.
    $status_message = null;

    // Check if all fields have been filled out.
    if (strlen($api_subdomain) == 0 || strlen($api_key) == 0 || strlen($api_secret) == 0)
    {
        die ('error|'.$lang->oa_social_login_api_fill_credentials );
    }

    // Check the handler
    $api_connection_handler = ($api_connection_handler == 'fs' ? 'fsockopen' : 'curl');
    $api_connection_use_https = ($api_connection_port == 443 ? true : false);

    // FSOCKOPEN
    if ($api_connection_handler == 'fsockopen')
    {
        if (!check_fsockopen($api_connection_use_https))
        {
            die ('error|' . $lang->oa_social_login_api_error_use_auto);
        }
    }
    // CURL
    else
    {
        if (!oa_social_login_check_curl($api_connection_use_https))
        {
            die ('error|' . $lang->oa_social_login_api_error_use_auto);
        }
    }

    // The full domain has been entered.
    if (preg_match("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
    {
        $api_subdomain = $matches[1];
    }

    // Check format of the subdomain.
    if (!preg_match("/^[a-z0-9\-]+$/i", $api_subdomain))
    {
        die ('error|' . $lang->oa_social_login_api_subdomain_not_found);
    }

    // Construct full API Domain.
    $api_domain = $api_subdomain . '.api.oneall.com';
    $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_domain . '/tools/ping.json';

    // API Credentialls.
    $api_credentials = array();
    $api_credentials['api_key'] = $api_key;
    $api_credentials['api_secret'] = $api_secret;

    // Try to establish a connection.
    $result = oa_social_login_do_api_request($api_connection_handler, $api_resource_url, $api_credentials);

    // Parse result.
    if (is_object($result) && property_exists($result, 'http_code') && property_exists($result, 'http_data'))
    {
        switch ($result->http_code)
        {
            // Connection successfull.
            case 200:
                die ('success|' . $lang->oa_social_login_api_connection_ok);

            // Authentication Error.
            case 401:
                die ('error|' . $lang->oa_social_login_api_credentials_wrong);

            // Wrong Subdomain.
            case 404:
                die ('error|' . $lang->oa_social_login_api_subdomain_not_found);
        }
    }

    // Other error.
    die ('error|' . $lang->oa_social_login_api_check_com);
}

/**
 * Return the OneAll subdomain from the settings
 * @return string subdomain
 */
function oa_social_login_get_subdomain()
{
    global $db;

    // Read subdomain
    $query = $db->simple_select('settings', 'value', "name='oa_social_login_subdomain'", array('limit' => 1));
    $data = $db->fetch_array($query);

    // Done
    return ((is_array($data) && !empty($data['value'])) ? $data['value'] : null);
}

/**
 * Return a user_token by user id
 * @param  int $uid user id
 * @return string   User token
 */
function oa_social_login_get_user_token_by_userid($uid)
{
    global $db;

    // Read user token
    $query = $db->simple_select("oa_social_login_user_token", "user_token", "uid='" . (int) $db->escape_string($uid) . "'", array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'desc'));
    $data = $db->fetch_array($query);

    // Done
    return ((is_array($data) && !empty($data['user_token'])) ? $data['user_token'] : null);
}


/**
 * Return User id from his token
 * @param  string $user_token User Token
 * @return string user id
 */
function oa_social_login_get_userid_by_user_token($user_token, $check_orphan = true)
{
    global $db;

    // Result
    $userid = null;

    // Read user id for token
    $query = $db->simple_select("oa_social_login_user_token", "uid", "user_token='" . $db->escape_string($user_token) . "'", array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'asc'));
    $data = $db->fetch_array($query);

    // User id for this user_token found
    if (is_array($data) && !empty($data['uid']))
    {
        // Remove orphan tokens if the uid does not exist
        if ($check_orphan)
        {
            $query = $db->simple_select("users", "COUNT(*) AS tot", "uid='" . intval($data['uid']) . "'", array('limit' => 1));
            if ($db->fetch_field($query, 'tot') != 1)
            {
                oa_social_login_remove_tokens_for_uid ($data['uid']);
            }
            // Valid user
            else
            {
                $userid = $data['uid'];
            }
        }
        else
        {
            $userid = $data['uid'];
        }
    }

    // Done
    return $userid;
}

/**
 * USer id from email
 * @param  string $email email
 * @return string        user id
 */
function oa_social_login_get_userid_for_email($email)
{
    global $db;

    // Read user having the given email
    $query = $db->simple_select("users", "uid", "email='" . $db->escape_string($email) . "'", array('limit' => 1, 'order_by' => 'uid', 'order_dir' => 'asc'));
    $data = $db->fetch_array($query);

    // Done
    return ((is_array($data) && !empty($data['uid'])) ? $data['uid'] : null);
}

/**
 * Remove user_token/identity_token for a given uid
 * @param  string $uid        user id
 */
function oa_social_login_remove_tokens_for_uid($uid)
{
    global $db;

    // Read entries for uid
    $query = $db->simple_select('oa_social_login_user_token', 'id', "uid='" . intval($uid) . "'");
    while ($data = $db->fetch_array($query))
    {
        // Remove user_token
        $db->delete_query("oa_social_login_user_token", "id='" . intval($data['id']) . "'");

        // Remove identity_token
        $db->delete_query("oa_social_login_identity_token", "utid='" . intval($data['id']) . "'");
    }
}

/**
 * Invert CamelCase -> camel_case
 * @param string $input CamelCase String
 */
function oa_social_login_undo_camel_case ($input)
{
    preg_match_all ('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
    $ret = $matches [0];
    foreach ($ret as &$match)
    {
        $match = ($match == strtoupper ($match) ? strtolower ($match) : lcfirst ($match));
    }
    return implode ('_', $ret);
}

/**
 * Extracts the social network data from a result-set returned by the OneAll API.
 * @param  object $data Data received from the OneAll API
 */
function oa_social_login_extract_social_network_profile ($data)
{
    // Check API result.
    if (is_object($data) && property_exists($data, 'http_code') && $data->http_code == 200 && property_exists($data, 'http_data'))
    {
        // Decode the social network profile Data.
        $social_data = json_decode($data->http_data);

        // Make sur that the data has beeen decoded properly
        if (is_object($social_data))
        {
            // Provider may report an error inside message:
            if (!empty($social_data->response->result->status->flag) && $social_data->response->result->status->code >= 400)
            {
                error_log($social_data->response->result->status->info . ' (' . $social_data->response->result->status->code . ')');
                return false;
            }

            // Container for user data
            $data = array();

            // Parse plugin data.
            if (isset($social_data->response->result->data->plugin))
            {
                // Plugin.
                $plugin = $social_data->response->result->data->plugin;

                // Add plugin data.
                $data['plugin_key'] = $plugin->key;
                $data['plugin_action'] = (isset($plugin->data->action) ? $plugin->data->action : null);
                $data['plugin_operation'] = (isset($plugin->data->operation) ? $plugin->data->operation : null);
                $data['plugin_reason'] = (isset($plugin->data->reason) ? $plugin->data->reason : null);
                $data['plugin_status'] = (isset($plugin->data->status) ? $plugin->data->status : null);
            }

            // Do we have a user?
            if (isset($social_data->response->result->data->user) && is_object($social_data->response->result->data->user))
            {
                // User.
                $user = $social_data->response->result->data->user;

                // Add user data.
                $data['user_token'] = $user->user_token;

                // Do we have an identity list ?
                $data['user_identites'] = array();
                if (isset($user->identities) && is_array($user->identities))
                {
                    foreach ($user->identities as $identity)
                    {
                        $data['user_identites'][$identity->identity_token] = $identity->provider;
                    }
                }

                // Do we have an identity ?
                if (isset($user->identity) && is_object($user->identity))
                {
                    // Identity.
                    $identity = $user->identity;

                    // Add identity data.
                    $data['identity_token'] = $identity->identity_token;
                    $data['identity_provider'] = !empty($identity->source->name) ? $identity->source->name : '';

                    $data['user_password_rand'] = oa_social_login_generate_password();
                    $data['user_first_name'] = !empty($identity->name->givenName) ? $identity->name->givenName : '';
                    $data['user_last_name'] = !empty($identity->name->familyName) ? $identity->name->familyName : '';
                    $data['user_formatted_name'] = !empty($identity->name->formatted) ? $identity->name->formatted : '';
                    $data['user_location'] = !empty($identity->currentLocation) ? $identity->currentLocation : '';
                    $data['user_constructed_name'] = trim($data['user_first_name'] . ' ' . $data['user_last_name']);
                    $data['user_picture'] = !empty($identity->pictureUrl) ? $identity->pictureUrl : '';
                    $data['user_thumbnail'] = !empty($identity->thumbnailUrl) ? $identity->thumbnailUrl : '';
                    $data['user_current_location'] = !empty($identity->currentLocation) ? $identity->currentLocation : '';
                    $data['user_about_me'] = !empty($identity->aboutMe) ? $identity->aboutMe : '';
                    $data['user_note'] = !empty($identity->note) ? $identity->note : '';

                    // Birthdate - MM/DD/YYYY
                    if (!empty($identity->birthday) && preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $identity->birthday, $matches))
                    {
                        $data['user_birthdate'] = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $data['user_birthdate'] .= '/' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $data['user_birthdate'] .= '/' . str_pad($matches[3], 4, '0', STR_PAD_LEFT);
                    }
                    else
                    {
                        $data['user_birthdate'] = '';
                    }

                    // Fullname.
                    if (!empty($identity->name->formatted))
                    {
                        $data['user_full_name'] = $identity->name->formatted;
                    }
                    elseif (!empty($identity->name->displayName))
                    {
                        $data['user_full_name'] = $identity->name->displayName;
                    }
                    else
                    {
                        $data['user_full_name'] = $data['user_constructed_name'];
                    }

                    // Preferred Username.
                    if (!empty($identity->preferredUsername))
                    {
                        $data['user_login'] = $identity->preferredUsername;
                    }
                    elseif (!empty($identity->displayName))
                    {
                        $data['user_login'] = $identity->displayName;
                    }
                    else
                    {
                        $data['user_login'] = $data['user_full_name'];
                    }

                    // myBB does not like spaces here
                    $data['user_login'] = str_replace(' ', '', trim($data['user_login']));

                    // Website/Homepage.
                    $data['user_website'] = '';
                    if (!empty($identity->profileUrl))
                    {
                        $data['user_website'] = $identity->profileUrl;
                    }
                    elseif (!empty($identity->urls[0]->value))
                    {
                        $data['user_website'] = $identity->urls[0]->value;
                    }

                    // Gender.
                    $data['user_gender'] = '';
                    if (!empty($identity->gender))
                    {
                        switch ($identity->gender)
                        {
                            case 'male':
                                $data['user_gender'] = 'm';
                                break;

                            case 'female':
                                $data['user_gender'] = 'f';
                                break;
                        }
                    }

                    // Email Addresses.
                    $data['user_emails'] = array();
                    $data['user_emails_simple'] = array();

                    // Email Address.
                    $data['user_email'] = '';
                    $data['user_email_is_verified'] = false;
                    $data['user_email_is_random'] = false;

                    // Extract emails.
                    if (property_exists($identity, 'emails') && is_array($identity->emails))
                    {
                        // Loop through emails.
                        foreach ($identity->emails as $email)
                        {
                            // Add to simple list.
                            $data['user_emails_simple'][] = $email->value;

                            // Add to list.
                            $data['user_emails'][] = array(
                                'user_email' => $email->value,
                                'user_email_is_verified' => $email->is_verified);

                            // Keep one, if possible a verified one.
                            if (empty($data['user_email']) || $email->is_verified)
                            {
                                $data['user_email'] = $email->value;
                                $data['user_email_is_verified'] = $email->is_verified;
                            }
                        }
                    }

                    // Addresses.
                    $data['user_addresses'] = array();
                    $data['user_addresses_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'addresses') && is_array($identity->addresses))
                    {
                        // Loop through entries.
                        foreach ($identity->addresses as $address)
                        {
                            // Add to simple list.
                            $data['user_addresses_simple'][] = $address->formatted;

                            // Add to list.
                            $data['user_addresses'][] = array(
                                'formatted' => $address->formatted);
                        }
                    }

                    // Phone Number.
                    $data['user_phone_numbers'] = array();
                    $data['user_phone_numbers_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'phoneNumbers') && is_array($identity->phoneNumbers))
                    {
                        // Loop through entries.
                        foreach ($identity->phoneNumbers as $phone_number)
                        {
                            // Add to simple list.
                            $data['user_phone_numbers_simple'][] = $phone_number->value;

                            // Add to list.
                            $data['user_phone_numbers'][] = array(
                                'value' => $phone_number->value,
                                'type' => (isset($phone_number->type) ? $phone_number->type : null));
                        }
                    }

                    // URLs.
                    $data['user_interests'] = array();
                    $data['user_interests_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'interests') && is_array($identity->interests))
                    {
                        // Loop through entries.
                        foreach ($identity->interests as $interest)
                        {
                            // Add to simple list.
                            $data['user_interests_simple'][] = $interest->value;

                            // Add to list.
                            $data['users_interests'][] = array(
                                'value' => $interest->value,
                                'category' => (isset($interest->category) ? $interest->category : null));
                        }
                    }

                    // URLs.
                    $data['user_urls'] = array();
                    $data['user_urls_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'urls') && is_array($identity->urls))
                    {
                        // Loop through entries.
                        foreach ($identity->urls as $url)
                        {
                            // Add to simple list.
                            $data['user_urls_simple'][] = $url->value;

                            // Add to list.
                            $data['user_urls'][] = array(
                                'value' => $url->value,
                                'type' => (isset($url->type) ? $url->type : null));
                        }
                    }

                    // Certifications.
                    $data['user_certifications'] = array();
                    $data['user_certifications_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'certifications') && is_array($identity->certifications))
                    {
                        // Loop through entries.
                        foreach ($identity->certifications as $certification)
                        {
                            // Add to simple list.
                            $data['user_certifications_simple'][] = $certification->name;

                            // Add to list.
                            $data['user_certifications'][] = array(
                                'name' => $certification->name,
                                'number' => (isset($certification->number) ? $certification->number : null),
                                'authority' => (isset($certification->authority) ? $certification->authority : null),
                                'start_date' => (isset($certification->startDate) ? $certification->startDate : null));
                        }
                    }

                    // Recommendations.
                    $data['user_recommendations'] = array();
                    $data['user_recommendations_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'recommendations') && is_array($identity->recommendations))
                    {
                        // Loop through entries.
                        foreach ($identity->recommendations as $recommendation)
                        {
                            // Add to simple list.
                            $data['user_recommendations_simple'][] = $recommendation->value;

                            // Build data.
                            $data_entry = array(
                                'value' => $recommendation->value);

                            // Add recommender
                            if (property_exists($recommendation, 'recommender') && is_object($recommendation->recommender))
                            {
                                $data_entry['recommender'] = array();

                                // Add recommender details
                                foreach (get_object_vars($recommendation->recommender) as $field => $value)
                                {
                                    $data_entry['recommender'][oa_social_login_undo_camel_case($field)] = $value;
                                }
                            }

                            // Add to list.
                            $data['user_recommendations'][] = $data_entry;
                        }
                    }

                    // Accounts.
                    $data['user_accounts'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'accounts') && is_array($identity->accounts))
                    {
                        // Loop through entries.
                        foreach ($identity->accounts as $account)
                        {
                            // Add to list.
                            $data['user_accounts'][] = array(
                                'domain' => (isset($account->domain) ? $account->domain : null),
                                'userid' => (isset($account->userid) ? $account->userid : null),
                                'username' => (isset($account->username) ? $account->username : null));
                        }
                    }

                    // Photos.
                    $data['user_photos'] = array();
                    $data['user_photos_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'photos') && is_array($identity->photos))
                    {
                        // Loop through entries.
                        foreach ($identity->photos as $photo)
                        {
                            // Add to simple list.
                            $data['user_photos_simple'][] = $photo->value;

                            // Add to list.
                            $data['user_photos'][] = array(
                                'value' => $photo->value,
                                'size' => $photo->size);
                        }
                    }

                    // Languages.
                    $data['user_languages'] = array();
                    $data['user_languages_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'languages') && is_array($identity->languages))
                    {
                        // Loop through entries.
                        foreach ($identity->languages as $language)
                        {
                            // Add to simple list
                            $data['user_languages_simple'][] = $language->value;

                            // Add to list.
                            $data['user_languages'][] = array(
                                'value' => $language->value,
                                'type' => $language->type);
                        }
                    }

                    // Educations.
                    $data['user_educations'] = array();
                    $data['user_educations_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'educations') && is_array($identity->educations))
                    {
                        // Loop through entries.
                        foreach ($identity->educations as $education)
                        {
                            // Add to simple list.
                            $data['user_educations_simple'][] = $education->value;

                            // Add to list.
                            $data['user_educations'][] = array(
                                'value' => $education->value,
                                'type' => $education->type);
                        }
                    }

                    // Organizations.
                    $data['user_organizations'] = array();
                    $data['user_organizations_simple'] = array();

                    // Extract entries.
                    if (property_exists($identity, 'organizations') && is_array($identity->organizations))
                    {
                        // Loop through entries.
                        foreach ($identity->organizations as $organization)
                        {
                            // At least the name is required.
                            if (!empty($organization->name))
                            {
                                // Add to simple list.
                                $data['user_organizations_simple'][] = $organization->name;

                                // Build entry.
                                $data_entry = array();

                                // Add all fields.
                                foreach (get_object_vars($organization) as $field => $value)
                                {
                                    $data_entry[oa_social_login_undo_camel_case($field)] = $value;
                                }

                                // Add to list.
                                $data['user_organizations'][] = $data_entry;
                            }
                        }
                    }
                }
            }
            return $data;
        }
    }
    return false;
}

function oa_social_login_synchronize_identities($userid, $user_token, $user_identities)
{
    global $db, $table_prefix;

    // Make sure that that the user exists.
    $query = $db->simple_select("users", "COUNT(*) AS tot", "uid='" . intval($userid) . "'", array(
        'limit' => 1));
    if ($db->fetch_field($query, 'tot') > 0)
    {
        $utid = null;

        // Cleanup the user_tokens
        $query = $db->simple_select('oa_social_login_user_token', 'id,uid,user_token', "uid = '" . intval($userid) . "' OR user_token = '" . $db->escape_string($user_token) . "'");
        while ($data = $db->fetch_array($query))
        {
            // Remove wrongly linked tokens
            if ($data['uid'] != $userid || $data['user_token'] != $user_token)
            {
                // Remove user_token
                $db->delete_query("oa_social_login_user_token", "id='" . intval($data['id']) . "'");

                // Remove identity_token
                $db->delete_query("oa_social_login_identity_token", "utid='" . intval($data['id']) . "'");
            }
            else
            {
                $utid = $data['id'];
            }
        }

        // Add new user_token if it does not exist.
        if (empty($utid))
        {
            // Add new user_token
            $sql_arr = array(
                'uid' => intval($userid),
                'user_token' => $user_token,
                'date_creation' => time());
            $utid = $db->insert_query("oa_social_login_user_token", $sql_arr);
        }

        // Identity Tokens
        $identity_tokens = array_keys($user_identities);

        // Cleanup the identity_tokens
        $query = $db->simple_select('oa_social_login_identity_token', 'id,utid,identity_token', "utid = '" . intval($utid) . "' OR identity_token IN ('" . implode("','", $identity_tokens) . "')");
        while ($data = $db->fetch_array($query))
        {
            // Correct identity
            if (in_array($data['identity_token'], $identity_tokens))
            {
                // Linked to wrong user
                if ($data['utid'] != $utid)
                {
                    $db->delete_query("oa_social_login_identity_token", "id='" . intval($data['id']) . "'");
                }
                else
                {
                    // Remove so that we don't need to re-add it below
                    unset($user_identities[$data['identity_token']]);
                }
            }
            // Wrongly linked identity
            else
            {
                $db->delete_query("oa_social_login_identity_token", "id='" . intval($data['id']) . "'");
            }
        }

        // Now add the missing ones
        if (is_array($user_identities) && count($user_identities) > 0)
        {
            foreach ($user_identities as $identity_token => $provider)
            {
                // Add new identity_token
                $sql_arr = array(
                    'utid' => intval($utid),
                    'identity_token' => $identity_token,
                    'provider' => $provider,
                    'date_creation' => time());
                $db->insert_query("oa_social_login_identity_token", $sql_arr);
            }
        }

        // Finished
        return true;
    }

    // An error occured.
    return false;
}

/**
 * Handle the callback
 * @return void
 */
function oa_social_login_callback()
{
    global $mybb, $lang, $plugins, $db, $templates;

    // Callback Handler
    if (isset($_POST) && !empty($_POST['oa_action']) && !empty($_POST['connection_token']))
    {
        // Plugin
        $action = ($_POST['oa_action'] == 'social_link' ? 'social_link' : 'social_login');

        // User Functions
        require_once MYBB_ROOT . "inc/functions_user.php";

        // Load Language
        $lang->load('oa_social_login');

        // OneAll Connection token
        $connection_token = trim($_POST['connection_token']);

        // Read arguments.
        $api_subdomain = trim(strtolower($mybb->settings['oa_social_login_subdomain']));
        $api_connection_port = $mybb->settings['oa_social_login_connection_port'];
        $api_connection_handler = $mybb->settings['oa_social_login_connection_handler'];

        // Check the handler
        $api_connection_handler = ($api_connection_handler == 'fs' ? 'fsockopen' : 'curl');
        $api_connection_use_https = ($api_connection_port == 443 ? true : false);

        // We cannot make a connection without a subdomain
        if (!empty($api_subdomain))
        {
            // See: http://docs.oneall.com/api/resources/connections/read-connection-details/
            $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . '.api.oneall.com/connections/' . $connection_token . '.json';

            // API Credentials
            $api_opts = array();
            $api_opts['api_key'] = $mybb->settings['oa_social_login_public_key'];
            $api_opts['api_secret'] = $mybb->settings['oa_social_login_private_key'];

            // Retrieve connection details
            $result = oa_social_login_do_api_request($api_connection_handler, $api_resource_url, $api_opts);

            // Check result
            if (is_object($result) && property_exists($result, 'http_code') && $result->http_code == 200)
            {
                // Extract data
                if (($user_data = oa_social_login_extract_social_network_profile($result)) !== false)
                {
                    // Get user id by user_token
                    $userid = oa_social_login_get_userid_by_user_token($user_data['user_token']);

                    // Social Link
                    if ($action == 'social_link')
                    {
                        // Make sure we have a user
                        if (is_object($mybb) && isset($mybb->user) && !empty($mybb->user['uid']))
                        {
                            // Logged in user
                            $userid_current = $mybb->user['uid'];

                            // Synchronize?
                            $synchronize_identities = true;

                            // There is already a userid for this user_token
                            if (!empty($userid))
                            {
                                // The existing user_id does not match the logged in user
                                if ($userid != $userid_current)
                                {
                                    // Show an error to the user.
                                    // TODO: ERROR

                                    // Do not update the tokens.
                                    $synchronize_identities = false;
                                }
                            }

                            // Synchronize
                            if ($synchronize_identities)
                            {
                                oa_social_login_synchronize_identities($userid_current, $user_data['user_token'], $user_data['user_identites']);
                            }
                        }
                    }
                    // Social Login
                    else
                    {
                        // No user for this user_token found
                        if (!is_numeric($userid))
                        {
                            // Automatic Link enabled?
                            if (!empty($mybb->settings['oa_social_login_link_verified_accounts']))
                            {
                                // Only if email is verified
                                if (!empty($user_data['user_email']) && $user_data['user_email_is_verified'])
                                {
                                    // Read existing user
                                    $userid_tmp = oa_social_login_get_userid_for_email($user_data['user_email']);

                                    // We have found a user id for this email
                                    if (!empty($userid_tmp))
                                    {
                                        // We can login the user
                                        $userid = $userid_tmp;

                                        // Does the user already got a user token ?
                                        $user_token_linked = oa_social_login_get_user_token_by_userid ($userid_tmp);

                                        // The user already has a different user_token
                                        if (!empty($user_token_linked))
                                        {
                                            // Linked identity_token to existing user_token
                                            $user_identites_linked = oa_social_login_relink_identity($user_data['identity_token'], $user_token_linked);

                                            // If the relink is successfully, update the identities
                                            if (is_array ($user_identites_linked))
                                            {
                                                // Use new user_token
                                                $user_data['user_token'] = $user_token_linked;

                                                // Use new identites
                                                $user_data['user_identites'] = $user_identites_linked;
                                            }
                                        }

                                        // Synchronize
                                        oa_social_login_synchronize_identities($userid, $user_data['user_token'], $user_data['user_identites']);
                                    }
                                }
                            }
                        }
                        // User found for this user_token
                        else
                        {
                            oa_social_login_synchronize_identities($userid, $user_data['user_token'], $user_data['user_identites']);
                        }

                        // New User
                        if (!is_numeric($userid))
                        {
                            // Username is mandatory
                            if (empty($user_data['user_login']))
                            {
                                $user_data['user_login'] = $user_identity_provider . 'User';
                            }

                            // Username must be unique
                            if (username_exists($user_data['user_login']))
                            {
                                $i = 1;
                                $user_login_tmp = $user_data['user_login'];
                                do
                                {
                                    $user_login_tmp = $user_data['user_login'] . ($i++);
                                }
                                while (username_exists($user_login_tmp));

                                $user_data['user_login'] = $user_login_tmp;
                            }

                            // Is this a random email?
                            $user_data['user_email_is_real'] = true;

                            // Email is required and must be unique
                            while (empty ($user_data['user_email']) || email_already_in_use($user_data['user_email']))
                            {
                            	// Generate random email
                            	$user_data['user_email'] = oa_social_login_create_rand_email();

                            	// This is a random email
                            	$user_data['user_email_is_real'] = false;
                            }


                            // Determine the usergroup
                            if ($user_data['user_email_is_real'] && ($mybb->settings['regtype'] == "verify" || $mybb->settings['regtype'] == "admin" || $mybb->settings['regtype'] == "both" || isset($mybb->cookies['coppauser'])))
                            {
                                // Awaiting Activation
                                $user_data['user_group'] = 5;
                            }
                            else
                            {
                                // Registered
                                $user_data['user_group'] = 2;
                            }


                            // Set up user handler.
                            require_once MYBB_ROOT . "inc/datahandlers/user.php";

                            // Set the data for the new user.
                            $mybb_user_data = array(
                                'username' => $user_data['user_login'],
                                'password' => $user_data['user_password_rand'],
                                'password2' => $user_data['user_password_rand'],
                                'email' => $user_data['user_email'],
                                'usergroup' => $user_data['user_group'],
                                'additionalgroups' => '',
                                'registration' => true,
                                'signature' => '',
                                'avatar' => '',
                                'avatartype' => '',
                                'profile_fields_editable' => true);

                            // Use avatar from Social Network profile?
                            if (!empty($mybb->settings['oa_social_login_avatar']))
                            {
                                // Picture found?
                                if (!empty($user_data['user_picture']))
                                {
                                    $mybb_user_data['avatar'] = $user_data['user_picture'];
                                    $mybb_user_data['avatartype'] = 'remote';
                                }
                                // Thumbnail found?
                                else
                                {
                                    if (!empty($user_data['user_thumbnail']))
                                    {
                                        $mybb_user_data['avatar'] = $user_data['user_thumbnail'];
                                        $mybb_user_data['avatartype'] = 'remote';
                                    }
                                }
                            }

                            // Set the data of the user in the datahandler.
                            $user_data_handler = new UserDataHandler('insert');
                            $user_data_handler->set_data($mybb_user_data);

                            // Validate the user data and check for errors
                            if (!$user_data_handler->validate_user())
                            {
                                $errors = $user_data_handler->get_friendly_errors();
                            }
                            // Valid user data, create user
                            else
                            {
                                // Other actions
                                $plugins->run_hooks('member_do_register_start');

                                // Add User
                                $mybb_new_user = $user_data_handler->insert_user();

                                // Synchronize
                                oa_social_login_synchronize_identities($mybb_new_user['uid'], $user_data['user_token'], $user_data['user_identites']);

                                // Register and redirect user
                                oa_social_login_register_user($mybb_new_user);
                            }
                        }
                        // Existing user
                        else
                        {
                            // Login
                            oa_social_login_login_userid($userid);
                        }

                        // An error occured
                        oa_social_login_redirect(true, $lang->oa_social_login_error);
                    }
                }
                else
                {
                    oa_social_login_redirect(true, $lang->oa_social_login_error);
                }
            }
            else
            {
                oa_social_login_redirect(true, $lang->oa_social_login_error);
            }
        }
        else
        {
            oa_social_login_redirect(true, $lang->oa_social_login_error);
        }
    }
}

/**
 *
 */
/**
 * Link new identity token to user token
 * @param  string $identity_token         identity token
 * @param  string $existing_user_token    user token
 * @param  string $user_identity_provider provider name
 * @return void
 */
function oa_social_login_relink_identity($identity_token, $user_token)
{
    global $mybb;

    // Subdomain
    $subdomain = oa_social_login_get_subdomain();

    // Plugin is setup
    if (!empty($subdomain))
    {
        // Check tokens
        if (!empty($user_token) && !empty($identity_token))
        {
            // Read arguments.
            $api_subdomain = trim(strtolower($mybb->settings['oa_social_login_subdomain']));
            $api_connection_port = $mybb->settings['oa_social_login_connection_port'];
            $api_connection_handler = $mybb->settings['oa_social_login_connection_handler'];

            // Check the handler
            $api_connection_handler = ($api_connection_handler == 'fs' ? 'fsockopen' : 'curl');
            $api_connection_use_https = ($api_connection_port == 443 ? true : false);

            // See: http://docs.oneall.com/api/resources/identities/relink-identity/
            $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . '.api.oneall.com/identities/' . $identity_token . '/link.json';

            // API Credentials
            $api_opts = array();
            $api_opts['api_key'] = trim($mybb->settings['oa_social_login_public_key']);
            $api_opts['api_secret'] = trim($mybb->settings['oa_social_login_private_key']);
            $api_opts['custom_request'] = 'PUT';

            // Message Structure
            $data = array(
                'request' => array(
                    'user' => array(
                        'user_token' => $user_token
                    )
                )
            );

            // Encode structure
            $json = json_encode($data);

            // Retrieve connection details
            $data = oa_social_login_do_api_request ($api_connection_handler, $api_resource_url, $api_opts, $json);

            // Check API result.
            if (is_object($data) && property_exists($data, 'http_code') && $data->http_code == 200 && property_exists($data, 'http_data'))
            {
                // Decode the social network profile data.
                $api_data = json_decode($data->http_data);

                // Make sure that the data has beeen decoded properly
                if (is_object($api_data))
                {
                    if (isset ($api_data->response->result->data->user->identities))
                    {
                        $identities = array ();

                        // Build Result
                        foreach ($api_data->response->result->data->user->identities as $identity)
                        {
                            $identities[$identity->identity_token] = $identity->provider;
                        }

                        // Success
                        return $identities;
                    }
                }
            }
        }
    }

    // Error
    return null;
}

/**
 * Login an user
 * @param  array $user_info user info
 * @return boolean
 */
function oa_social_login_login_userid($userid)
{
    global $mybb, $session, $db, $lang;

    // Load functions
    require_once MYBB_ROOT . '/inc/functions.php';

    // Load user
    if (($user_data = get_user($userid)) == true)
    {
        // Delete old session
        $db->delete_query('sessions', "ip='" . $db->escape_string($session->ipaddress) . "' and sid != '" . $session->sid . "'");

        // Create new session
        $db->update_query('sessions', array("uid" => $user_data['uid']), "sid='" . $session->sid . "'");

        // Set cookies
        my_setcookie('mybbuser', $user_data['uid'] . "_" . $user_data['loginkey'], null, true);
        my_setcookie('sid', $session->sid, -1, true);

        // Load languages
        $lang->load('member');

        // Redirect
        oa_social_login_redirect(true, $lang->redirect_loggedin);
    }

    // Error
    return false;
}

/**
 * Register an user
 * @param  array  $user_info user info
 * @return void
 */
function oa_social_login_register_user($user_info = array())
{
    global $mybb, $lang, $db, $plugins;

    require_once MYBB_ROOT . $mybb->config['admin_dir'] . "/inc/functions.php";

    $lang->load('member');

    if ($mybb->settings['regtype'] != "randompass" && !isset($mybb->cookies['coppauser']))
    {
        // Log them in
        my_setcookie("mybbuser", $user_info['uid'] . "_" . $user_info['loginkey'], null, true);
    }

    // MyBB Register methods (member.php)
    if (isset($mybb->cookies['coppauser']))
    {
        $lang->redirect_registered_coppa_activate = $lang->sprintf($lang->redirect_registered_coppa_activate, $mybb->settings['bbname'], htmlspecialchars_uni($user_info['username']));
        my_unsetcookie("coppauser");
        my_unsetcookie("coppadob");
        $plugins->run_hooks("member_do_register_end");
        oa_social_login_redirect(true, $lang->redirect_registered_coppa_activate);
    }
    else
        if ($mybb->settings['regtype'] == "verify")
        {
            $activationcode = random_str();
            $now = TIME_NOW;
            $activationarray = array(
                "uid" => $user_info['uid'],
                "dateline" => TIME_NOW,
                "code" => $activationcode,
                "type" => "r");
            $db->insert_query("awaitingactivation", $activationarray);
            $emailsubject = $lang->sprintf($lang->emailsubject_activateaccount, $mybb->settings['bbname']);
            switch ($mybb->settings['username_method'])
            {
                case 0:
                    $emailmessage = $lang->sprintf($lang->email_activateaccount, $user_info['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], $user_info['uid'], $activationcode);
                    break;
                case 1:
                    $emailmessage = $lang->sprintf($lang->email_activateaccount1, $user_info['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], $user_info['uid'], $activationcode);
                    break;
                case 2:
                    $emailmessage = $lang->sprintf($lang->email_activateaccount2, $user_info['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], $user_info['uid'], $activationcode);
                    break;
                default:
                    $emailmessage = $lang->sprintf($lang->email_activateaccount, $user_info['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], $user_info['uid'], $activationcode);
                    break;
            }
            my_mail($user_info['email'], $emailsubject, $emailmessage);

            $lang->redirect_registered_activation = $lang->sprintf($lang->redirect_registered_activation, $mybb->settings['bbname'], htmlspecialchars_uni($user_info['username']));

            $plugins->run_hooks("member_do_register_end");

            oa_social_login_redirect(true, $lang->redirect_registered_activation);
        }
        else
            if ($mybb->settings['regtype'] == "randompass")
            {
                $emailsubject = $lang->sprintf($lang->emailsubject_randompassword, $mybb->settings['bbname']);
                switch ($mybb->settings['username_method'])
                {
                    case 0:
                        $emailmessage = $lang->sprintf($lang->email_randompassword, $user['username'], $mybb->settings['bbname'], $user_info['username'], $user_info['password']);
                        break;
                    case 1:
                        $emailmessage = $lang->sprintf($lang->email_randompassword1, $user['username'], $mybb->settings['bbname'], $user_info['username'], $user_info['password']);
                        break;
                    case 2:
                        $emailmessage = $lang->sprintf($lang->email_randompassword2, $user['username'], $mybb->settings['bbname'], $user_info['username'], $user_info['password']);
                        break;
                    default:
                        $emailmessage = $lang->sprintf($lang->email_randompassword, $user['username'], $mybb->settings['bbname'], $user_info['username'], $user_info['password']);
                        break;
                }
                my_mail($user_info['email'], $emailsubject, $emailmessage);

                $plugins->run_hooks("member_do_register_end");

                oa_social_login_redirect(true, $lang->redirect_registered_passwordsent);
            }
            else
                if ($mybb->settings['regtype'] == "admin")
                {
                    $groups = $cache->read("usergroups");
                    $admingroups = array();
                    if (!empty($groups)) // Shouldn't be...
                    {
                        foreach ($groups as $group)
                        {
                            if ($group['cancp'] == 1)
                            {
                                $admingroups[] = (int) $group['gid'];
                            }
                        }
                    }

                    if (!empty($admingroups))
                    {
                        $sqlwhere = 'usergroup IN (' . implode(',', $admingroups) . ')';
                        foreach ($admingroups as $admingroup)
                        {
                            switch ($db->type)
                            {
                                case 'pgsql':
                                case 'sqlite':
                                    $sqlwhere .= " OR ','||additionalgroups||',' LIKE '%,{$admingroup},%'";
                                    break;
                                default:
                                    $sqlwhere .= " OR CONCAT(',',additionalgroups,',') LIKE '%,{$admingroup},%'";
                                    break;
                            }
                        }
                        $q = $db->simple_select('users', 'uid,username,email,language', $sqlwhere);
                        while ($recipient = $db->fetch_array($q))
                        {
                            // First we check if the user's a super admin: if yes, we don't care about permissions
                            $is_super_admin = is_super_admin($recipient['uid']);
                            if (!$is_super_admin)
                            {
                                // Include admin functions
                                if (!file_exists(MYBB_ROOT . $mybb->config['admin_dir'] . "/inc/functions.php"))
                                {
                                    continue;
                                }

                                require_once MYBB_ROOT . $mybb->config['admin_dir'] . "/inc/functions.php";

                                // Verify if we have permissions to access user-users
                                require_once MYBB_ROOT . $mybb->config['admin_dir'] . "/modules/user/module_meta.php";
                                if (function_exists("user_admin_permissions"))
                                {
                                    // Get admin permissions
                                    $adminperms = get_admin_permissions($recipient['uid']);

                                    $permissions = user_admin_permissions();
                                    if (array_key_exists('users', $permissions['permissions']) && $adminperms['user']['users'] != 1)
                                    {
                                        continue; // No permissions
                                    }
                                }
                            }

                            // Load language
                            if ($recipient['language'] != $lang->language && $lang->language_exists($recipient['language']))
                            {
                                $reset_lang = true;
                                $lang->set_language($recipient['language']);
                                $lang->load("member");
                            }

                            $subject = $lang->sprintf($lang->newregistration_subject, $mybb->settings['bbname']);
                            $message = $lang->sprintf($lang->newregistration_message, $recipient['username'], $mybb->settings['bbname'], $user['username']);
                            my_mail($recipient['email'], $subject, $message);
                        }

                        // Reset language
                        if (isset($reset_lang))
                        {
                            $lang->set_language($mybb->settings['bblanguage']);
                            $lang->load("member");
                        }
                    }

                    $lang->redirect_registered_admin_activate = $lang->sprintf($lang->redirect_registered_admin_activate, $mybb->settings['bbname'], htmlspecialchars_uni($user_info['username']));

                    $plugins->run_hooks("member_do_register_end");

                    oa_social_login_redirect(true, $lang->redirect_registered_admin_activate);
                }
                else
                    if ($mybb->settings['regtype'] == "both")
                    {
                        $groups = $cache->read("usergroups");
                        $admingroups = array();
                        if (!empty($groups)) // Shouldn't be...
                        {
                            foreach ($groups as $group)
                            {
                                if ($group['cancp'] == 1)
                                {
                                    $admingroups[] = (int) $group['gid'];
                                }
                            }
                        }

                        if (!empty($admingroups))
                        {
                            $sqlwhere = 'usergroup IN (' . implode(',', $admingroups) . ')';
                            foreach ($admingroups as $admingroup)
                            {
                                switch ($db->type)
                                {
                                    case 'pgsql':
                                    case 'sqlite':
                                        $sqlwhere .= " OR ','||additionalgroups||',' LIKE '%,{$admingroup},%'";
                                        break;
                                    default:
                                        $sqlwhere .= " OR CONCAT(',',additionalgroups,',') LIKE '%,{$admingroup},%'";
                                        break;
                                }
                            }
                            $q = $db->simple_select('users', 'uid,username,email,language', $sqlwhere);
                            while ($recipient = $db->fetch_array($q))
                            {
                                // First we check if the user's a super admin: if yes, we don't care about permissions
                                $is_super_admin = is_super_admin($recipient['uid']);
                                if (!$is_super_admin)
                                {
                                    // Include admin functions
                                    if (!file_exists(MYBB_ROOT . $mybb->config['admin_dir'] . "/inc/functions.php"))
                                    {
                                        continue;
                                    }

                                    require_once MYBB_ROOT . $mybb->config['admin_dir'] . "/inc/functions.php";

                                    // Verify if we have permissions to access user-users
                                    require_once MYBB_ROOT . $mybb->config['admin_dir'] . "/modules/user/module_meta.php";
                                    if (function_exists("user_admin_permissions"))
                                    {
                                        // Get admin permissions
                                        $adminperms = get_admin_permissions($recipient['uid']);

                                        $permissions = user_admin_permissions();
                                        if (array_key_exists('users', $permissions['permissions']) && $adminperms['user']['users'] != 1)
                                        {
                                            continue; // No permissions
                                        }
                                    }
                                }

                                // Load language
                                if ($recipient['language'] != $lang->language && $lang->language_exists($recipient['language']))
                                {
                                    $reset_lang = true;
                                    $lang->set_language($recipient['language']);
                                    $lang->load("member");
                                }

                                $subject = $lang->sprintf($lang->newregistration_subject, $mybb->settings['bbname']);
                                $message = $lang->sprintf($lang->newregistration_message, $recipient['username'], $mybb->settings['bbname'], $user['username']);
                                my_mail($recipient['email'], $subject, $message);
                            }

                            // Reset language
                            if (isset($reset_lang))
                            {
                                $lang->set_language($mybb->settings['bblanguage']);
                                $lang->load("member");
                            }
                        }

                        $activationcode = random_str();
                        $activationarray = array(
                            "uid" => $user_info['uid'],
                            "dateline" => TIME_NOW,
                            "code" => $activationcode,
                            "type" => "b");
                        $db->insert_query("awaitingactivation", $activationarray);
                        $emailsubject = $lang->sprintf($lang->emailsubject_activateaccount, $mybb->settings['bbname']);
                        switch ($mybb->settings['username_method'])
                        {
                            case 0:
                                $emailmessage = $lang->sprintf($lang->email_activateaccount, $user_info['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], $user_info['uid'], $activationcode);
                                break;
                            case 1:
                                $emailmessage = $lang->sprintf($lang->email_activateaccount1, $user_info['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], $user_info['uid'], $activationcode);
                                break;
                            case 2:
                                $emailmessage = $lang->sprintf($lang->email_activateaccount2, $user_info['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], $user_info['uid'], $activationcode);
                                break;
                            default:
                                $emailmessage = $lang->sprintf($lang->email_activateaccount, $user_info['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], $user_info['uid'], $activationcode);
                                break;
                        }
                        my_mail($user_info['email'], $emailsubject, $emailmessage);

                        $lang->redirect_registered_activation = $lang->sprintf($lang->redirect_registered_activation, $mybb->settings['bbname'], htmlspecialchars_uni($user_info['username']));

                        $plugins->run_hooks("member_do_register_end");

                        oa_social_login_redirect(true, $lang->redirect_registered_activation);
                    }
                    else
                    {
                        $lang->redirect_registered = $lang->sprintf($lang->redirect_registered, $mybb->settings['bbname'], htmlspecialchars_uni($user_info['username']));

                        $plugins->run_hooks("member_do_register_end");

                        oa_social_login_redirect(true, $lang->redirect_registered);
                    }
}
