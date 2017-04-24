<?php

/**
 * Allow your visitors to login and register with social networks like Twitter, Facebook, LinkedIn, Hyves, VKontakte, Google and Yahoo amongst others. Social Login increases your user registration rate by simplifying the registration process and provides permission-based social data retrieved from the social network profiles. Social Login integrates with your existing registration system so you and your users don't have to start from scratch.
 *
 * @package OneAll Social Login
 * @author  Damien ZARA <dzara@oneall.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt
 * @version 1.0
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

// All page
if ($mybb->settings['oa_social_login_enabled'])
{
    $plugins->add_hook("global_start", "oa_social_login_load_library");

    // Global
    // 1.8 has jQuery, not Prototype
    if ($mybb->version_code >= 1700)
    {
        $plugins->add_hook("global_intermediate", "oa_social_login_load_plugin_hook");
    }
    else
    {
        $plugins->add_hook("global_start", "oa_social_login_load_plugin_hook");
    }

    $plugins->add_hook("no_permission", "oa_social_login_load_plugin_hook_error_nopermission");
    $plugins->add_hook('global_end', 'oa_social_login_callback_hook');
}

// Admin CP
if (defined('IN_ADMINCP'))
{
    // Update routines and settings
    $plugins->add_hook("admin_page_output_header", "oa_social_login_admin_header");
    $plugins->add_hook("admin_page_output_footer", "oa_social_login_admin_footer");

    // Ajax call
    if (!empty($mybb->input["task"]))
    {
        switch ($mybb->input["task"])
        {
            case 'verify_api_settings':
                return admin_ajax_verify_api_settings();
                break;
            case 'autodetect_api_connection_handler':
                return oa_social_login_admin_autodetect_api_connection_handler();
                break;
        }
    }
}

// **************************************************
// General Hooks
// **************************************************

/**
 * Social Login hook
 * @return void
 */
function oa_social_login_callback_hook()
{
    oa_social_login_callback();
}

/**
 * Integrate Social Login into error no permission template (special case)
 * @return void
 */
function oa_social_login_load_plugin_hook_error_nopermission()
{
    global $mybb, $theme, $templates, $db, $lang, $plugins, $session;

    $time = TIME_NOW;

    $plugin = '';

    //user is not logged in
    if (!$mybb->user['uid'])
    {
        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

        $positions = array('member_page');

        foreach ($positions as $position)
        {
            $user_choice = $mybb->settings['oa_social_login_' . $position];

            if ($user_choice == 1)
            {
                $plugin = oa_social_login_load_plugin($position);
            }
        }
    }

    // get template and replace with values
    $tpl_error_nopermission = $templates->get('error_nopermission', false);
    eval("\$oa_login_member_page  = \"" . $plugin . "\";");

    $noperm_array = array(
        "nopermission" => '1',
        "location1" => 0,
        "location2" => 0
    );

    $db->update_query("sessions", $noperm_array, "sid='{$session->sid}'");

    if ($mybb->input['ajax'])
    {
        // Send our headers.
        header("Content-type: text/html; charset={$lang->settings['charset']}");
        echo "<error>{$lang->error_nopermission_user_ajax}</error>\n";
        exit;
    }

    if ($mybb->user['uid'])
    {
        $lang->error_nopermission_user_username = $lang->sprintf($lang->error_nopermission_user_username, $mybb->user['username']);
        eval("\$errorpage = \"" . $templates->get("error_nopermission_loggedin") . "\";");
    }
    else
    {
        // Redirect to where the user came from
        $redirect_url = $_SERVER['PHP_SELF'];
        if ($_SERVER['QUERY_STRING'])
        {
            $redirect_url .= '?' . $_SERVER['QUERY_STRING'];
        }

        $redirect_url = htmlspecialchars_uni($redirect_url);

        switch ($mybb->settings['username_method'])
        {
            case 0:
                $lang_username = $lang->username;
                break;
            case 1:
                $lang_username = $lang->username1;
                break;
            case 2:
                $lang_username = $lang->username2;
                break;
            default:
                $lang_username = $lang->username;
                break;
        }
        eval("\$errorpage = \"" . $templates->get("error_nopermission") . "\";");
    }

    error($errorpage);
}

/**
 * Integrate Social Login into templates
 * @return void
 */
function oa_social_login_load_plugin_hook()
{
    global $mybb, $templates;

    //user is not logged in
    if (!$mybb->user['uid'])
    {
        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

        $positions = array(
            'main_page',
            'other_page',
            'login_page',
            'registration_page'
        );

        foreach ($positions as $position)
        {
            $user_choice = $mybb->settings['oa_social_login_' . $position];

            $other_page = $mybb->settings['oa_social_login_other_page'] == 1;

            $add_plugin = ($position == 'main_page' || $position == 'registration_page') && $other_page ? false : true;

            if ($user_choice == 1 && $add_plugin)
            {
                oa_social_login_load_plugin($position);
            }
        }
    }
}

// **************************************************
// Admin Hooks
// **************************************************

/**
 * Add Css into Admin Section
 * @return void
 */
function oa_social_login_admin_header()
{
    global $mybb, $page;
    $page->extra_header .= '<link rel="stylesheet" type="text/css" href="/inc/plugins/oa_social_login/css/admin.css" />';
}

/**
 * Add JS into ADmin Section
 * @return void
 */
function oa_social_login_admin_footer()
{
    global $mybb, $db, $lang;

    if (!$lang->oa_social_login)
    {
        $lang->load('oa_social_login');
    }

    if ($mybb->input["action"] == "change" and $mybb->request_method != "post")
    {
        $gid = oa_social_login_settings_gid();

        if ($mybb->input["gid"] == $gid or !$mybb->input['gid'])
        {
            // 1.8 has jQuery, not Prototype
            if ($mybb->version_code >= 1700)
            {
                echo '<script type="text/javascript">
    $(document).ready(function() {

            /* Verify API Settings */
            $("#oa_social_login_test_api_settings").click(function() {

                var button = this;
                if ($(button).hasClass("working") === false) {
                    $(button).addClass("working");
                    var message_string;
                    var message_container;

                    var handler_val = $("#setting_oa_social_login_connection_handler").val();
                    var port_val = $("#setting_oa_social_login_connection_port").val();

                    var subdomain = $("#setting_oa_social_login_subdomain").val();
                    var key = $("#setting_oa_social_login_public_key").val();
                    var secret = $("#setting_oa_social_login_private_key").val();

                    /* Do not pass CURL in the URL, this is blocked by some hosts */
                    var handler = (handler_val === "fs" ? "fs" : "cr");
                    var port = (port_val === "443" ? 443 : 80);
                    // var sid = $("#sid").html();

                    var data = {
                      "api_subdomain" : subdomain,
                      "api_key" : key,
                      "api_secret" : secret,
                      "api_connection_port": port,
                      "api_connection_handler" : handler
                    };

                    var ajaxurl = "?i=-oneall-sociallogin-acp-sociallogin_acp_module&mode=settings&task=verify_api_settings";

                    message_container = $("#oa_social_login_api_test_result");
                    message_container.removeClass("success_message error_message").addClass("working_message");
                    message_container.html("");

                    $.post(ajaxurl, data, function(response_string) {

                        var response_parts = response_string.split("|");
                        var response_status = response_parts[0];
                        var response_text = response_parts[1];

                        message_container.removeClass("working_message");
                        message_container.html(response_text);

                        if (response_status == "success") {
                            message_container.addClass("success_message");
                        } else {
                            message_container.addClass("error_message");
                        }
                        $(button).removeClass("working");
                    });
                }
                return false;
            });


        /* Autodetect API Connection Handler */
        $("#oa_social_login_autodetect_api_connection_handler").click(function(){
            var message_string;
            var message_container;
            var is_success;

            var data = {
                    _ajax_nonce: "oa_social_login_ajax_nonce",
                    action: "oa_social_login_autodetect_api_connection_handler"
                };

            message_container = jQuery("#oa_social_login_api_connection_handler_result");
            message_container.removeClass("success_message error_message").addClass("working_message");
            message_container.html("' . $lang->oa_social_login_oa_admin_js_1 . '");

            var ajaxurl = "?i=-oneall-sociallogin-acp-sociallogin_acp_module&mode=settings&task=autodetect_api_connection_handler";

            jQuery.post(ajaxurl,data, function(response) {
                /* CURL/FSOCKOPEN Radio Boxs */
                var select_connection_handler = jQuery("#setting_oa_social_login_connection_handler");
                var select_connection_port = jQuery("#setting_oa_social_login_connection_port");

                /* CURL detected, HTTPS */
                if (response == "success_autodetect_api_curl_https")
                {
                    is_success = true;
                    select_connection_handler.val("cr");
                    select_connection_port.val("443");
                    message_string = "' . $lang->oa_social_login_oa_admin_js_201a . '";
                }
                /* CURL detected, HTTP */
                else if (response == "success_autodetect_api_curl_http")
                {
                    is_success = true;
                    select_connection_handler.val("cr");
                    select_connection_port.val("80");
                    message_string = "' . $lang->oa_social_login_oa_admin_js_201b . '";
                }
                /* CURL detected, ports closed */
                else if (response == "error_autodetect_api_curl_ports_blocked")
                {
                    is_success = false;
                    select_connection_handler.val("cr");
                    message_string = "' . $lang->oa_social_login_oa_admin_js_201c . '";
                }
                /* FSOCKOPEN detected, HTTPS */
                else if (response == "success_autodetect_api_fsockopen_https")
                {
                    is_success = true;
                    select_connection_handler.val("fs");
                    select_connection_port.val("443");
                    message_string = "' . $lang->oa_social_login_oa_admin_js_202a . '";
                }
                /* FSOCKOPEN detected, HTTP */
                else if (response == "success_autodetect_api_fsockopen_http")
                {
                    is_success = true;
                    select_connection_handler.val("fs");
                    select_connection_port.val("80");
                    message_string = "' . $lang->oa_social_login_oa_admin_js_202b . '";
                }
                /* FSOCKOPEN detected, ports closed */
                else if (response == "error_autodetect_api_fsockopen_ports_blocked")
                {
                    is_success = false;
                    select_connection_handler.val("fs");
                    message_string = "' . $lang->oa_social_login_oa_admin_js_202c . '";
                }
                /* No handler detected */
                else
                {
                    is_success = false;
                    message_string = "' . $lang->oa_social_login_oa_admin_js_211 . '";
                }

                message_container.removeClass("working_message");
                message_container.html(message_string);

                if (is_success){
                    message_container.addClass("success_message");
                } else {
                    message_container.addClass("error_message");
                }
            });

            return false;
        });

    });
    </script>';
            }
            else
            {
                echo '<script type="text/javascript">
                    Event.observe(window, "load", function() {
                        var autodetect_api_button = document.getElementById("oa_social_login_autodetect_api_connection_handler");
                        autodetect_api_button.addEventListener("click", autodetect_api_connection_handler);

                        var verify_api_button = document.getElementById("oa_social_login_test_api_settings");
                        verify_api_button.addEventListener("click", verify_api_settings);
                    });
                    function verify_api_settings()
                    {
                        var button = document.getElementById("oa_social_login_test_api_settings");
                        if (hasClass(button, "working") == false) {
                            button.className += " working";
                            var message_string;
                            var message_container;

                            var handler_val = document.getElementById("setting_oa_social_login_connection_handler").value;
                            var port_val = document.getElementById("setting_oa_social_login_connection_port").value;

                            var subdomain = document.getElementById("setting_oa_social_login_subdomain").value;
                            var key = document.getElementById("setting_oa_social_login_public_key").value;
                            var secret = document.getElementById("setting_oa_social_login_private_key").value;

                            /* Do not pass CURL in the URL, this is blocked by some hosts */
                            var handler = (handler_val === "fs" ? "fs" : "cr");
                            var port = (port_val === "443" ? 443 : 80);

                            var data = "api_subdomain="+subdomain+"&api_key="+key+"&api_secret="+secret+"&api_connection_port="+port+"&api_connection_handler="+handler;

                            var ajaxurl = "?i=-oneall-sociallogin-acp-sociallogin_acp_module&mode=settings&task=verify_api_settings";

                            message_container = document.getElementById("oa_social_login_api_test_result");
                            // removeClass("success_message error_message");
                            message_container.className = message_container.className.replace(new RegExp("(?:^|\\s)"+ "success_message error_message" + "(?:\\s|$)"), " ");
                            message_container.className += " working_message";
                            message_container.innerHTML = "";

                            var xhttp = new XMLHttpRequest();
                            xhttp.onreadystatechange = function() {
                                if (this.readyState == 4 && this.status == 200) {
                                    var response_parts = this.responseText.split("|");
                                    var response_status = response_parts[0];
                                    var response_text = response_parts[1];

                                    message_container.className = message_container.className.replace("working_message", " ");

                                    //message_container.removeClass("working_message");
                                    message_container.innerHTML = response_text;

                                    if (response_status == "success") {
                                        message_container.className += " success_message";
                                    } else {
                                        message_container.className += " error_message";
                                    }
                                    button.className = button.className.replace(new RegExp("(?:^|\\s)"+ "working" + "(?:\\s|$)"), " ");
                                }
                            };
                            xhttp.open("POST", ajaxurl, true);
                            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            xhttp.send(data);
                        }
                        return false;
                    }
                    function hasClass(element, cls) {
                        return (" " + element.className + " ").indexOf(" " + cls + " ") > -1;
                    }

                    function autodetect_api_connection_handler(){

                        var message_string;
                        var message_container;
                        var is_success;

                        var data = {
                            action: "oa_social_login_autodetect_api_connection_handler"
                        };

                        message_container = document.getElementById("oa_social_login_api_connection_handler_result");
                        message_container.className = message_container.className.replace(new RegExp("(?:^|\\s)"+ "success_message error_message" + "(?:\\s|$)"), " ");
                        message_container.className += " working_message";
                        message_container.innerHTML = "' . $lang->oa_social_login_oa_admin_js_1 . '";

                        var ajaxurl = "?i=-oneall-sociallogin-acp-sociallogin_acp_module&mode=settings&task=autodetect_api_connection_handler";

                        var xhttp = new XMLHttpRequest();
                        xhttp.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                                var response = this.responseText;

                                /* CURL/FSOCKOPEN Radio Boxs */
                                var select_connection_handler = document.getElementById("setting_oa_social_login_connection_handler");
                                var select_connection_port = document.getElementById("setting_oa_social_login_connection_port");

                                /* CURL detected, HTTPS */
                                if (response == "success_autodetect_api_curl_https")
                                {
                                    is_success = true;
                                    select_connection_handler.value = "cr";
                                    select_connection_port.value = "443";
                                    message_string = "' . $lang->oa_social_login_oa_admin_js_201a . '";
                                }
                                /* CURL detected, HTTP */
                                else if (response == "success_autodetect_api_curl_http")
                                {
                                    is_success = true;
                                    select_connection_handler.value = "cr";
                                    select_connection_port.value = "80";
                                    message_string = "' . $lang->oa_social_login_oa_admin_js_201b . '";
                                }
                                /* CURL detected, ports closed */
                                else if (response == "error_autodetect_api_curl_ports_blocked")
                                {
                                    is_success = false;
                                    select_connection_handler.value = "cr";
                                    message_string = "' . $lang->oa_social_login_oa_admin_js_201c . '";
                                }
                                /* FSOCKOPEN detected, HTTPS */
                                else if (response == "success_autodetect_api_fsockopen_https")
                                {
                                    is_success = true;
                                    select_connection_handler.value = "fs";
                                    select_connection_port.value = "443";
                                    message_string = "' . $lang->oa_social_login_oa_admin_js_202a . '";
                                }
                                /* FSOCKOPEN detected, HTTP */
                                else if (response == "success_autodetect_api_fsockopen_http")
                                {
                                    is_success = true;
                                    select_connection_handler.value = "fs";
                                    select_connection_port.value = "80";
                                    message_string = "' . $lang->oa_social_login_oa_admin_js_202b . '";
                                }
                                /* FSOCKOPEN detected, ports closed */
                                else if (response == "error_autodetect_api_fsockopen_ports_blocked")
                                {
                                    is_success = false;
                                    select_connection_handler.value = "fs";
                                    message_string = "' . $lang->oa_social_login_oa_admin_js_202c . '";
                                }
                                /* No handler detected */
                                else
                                {
                                    is_success = false;
                                    message_string = "' . $lang->oa_social_login_oa_admin_js_211 . '";
                                }

                                message_container.className = message_container.className.replace("working_message", " ");
                                message_container.innerHTML = message_string;

                                if (is_success){
                                    message_container.className += " success_message";
                                } else {
                                    message_container.className += " error_message";
                                }

                            }
                        };
                        xhttp.open("POST", ajaxurl, true);
                        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                        xhttp.send();

                        return false;
                    };


                    </script>';
            }
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
    global $oa_library, $mybb, $templates;

    // Library
    $url = '//' . oa_social_login_get_subdomain() . '.api.oneall.com/socialize/library.js';

    $find = array(
        "/oa.src = '.*'/i"
    );
    $replace = array(
        "oa.src = '" . $url . "'"
    );

    // get template and replace with values
    $oasociallogin_library = $templates->get('oasociallogin_library', false);
    $oasociallogin_library_replaced = preg_replace($find, $replace, $oasociallogin_library);
    $oasociallogin_library_replaced = str_replace("\\'", "'", addslashes($oasociallogin_library_replaced));
    eval("\$oa_library  = \"" . $oasociallogin_library_replaced . "\";");
}

/**
 * Integrate Social Login into template
 * @param  string $position page where social login is integrated
 * @return void
 */
function oa_social_login_load_plugin($position = null)
{
    global $oa_library, $mybb, $templates, $lang, $oa_login_main_page, $oa_login_login_page,
    $oa_login_registration_page, $oa_login_other_page, $oa_login_member_page;

    if ($position)
    {
        // Get buttons
        $providers = array();
        foreach ($mybb->settings as $setting_name => $value)
        {
            if (strpos($setting_name, 'oa_social_login_provider_') !== false)
            {
                if (!empty($value))
                {
                    $providers[] = str_replace('oa_social_login_provider_', '', $setting_name);
                }
            }
        }

        // Plugin Callback
        $callback_uri = oa_social_login_get_current_url();

        // Plugin Caption
        $position_caption = $mybb->settings['oa_social_login_' . $position . '_caption'];

        $find = array(
            '/login-title"></i',
            '/id="oneall_social_login_auth"/i',
            "/set_providers', \['(.*)'\]/i",
            "/set_callback_uri', '(.*)'/i",
            "/do_render_ui', '(.*)'/i"
        );
        $replace = array(
            'login-title">' . $position_caption . '<',
            'id="oa_login_' . $position . '"',
            "set_providers', ['" . implode("','", $providers) . "']",
            "set_callback_uri', '" . $callback_uri . "'",
            "do_render_ui', 'oa_login_" . $position . "'"
        );

        // get template and replace with values
        $oasociallogin_login_buttons = $templates->get('oasociallogin_plugin_' . $position, false);
        $oasociallogin_login_buttons_replaced = preg_replace($find, $replace, $oasociallogin_login_buttons);
        $oasociallogin_login_buttons_replaced = str_replace("\\'", "'", addslashes($oasociallogin_login_buttons_replaced));

        // set plugin into global template
        eval("\$oa_login_" . $position . " = \"" . $oasociallogin_login_buttons_replaced . "\";");

        return $oasociallogin_login_buttons_replaced;
    }
}

// **************************************************
// Functions
// **************************************************
/**
 * Generate random password
 * @return string password
 */
function oa_generate_password()
{
    global $mybb;

    // Generate a new password, then update it
    $password_length = (int) $mybb->settings['minpasswordlength'];

    if ($password_length < 8)
    {
        $password_length = min(8, (int) $mybb->settings['maxpasswordlength']);
    }

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
}

/**
 * Group id of OneAll Social settings group.
 * @return int settings group id
 */
function oa_social_login_settings_gid()
{
    global $db;

    $query = $db->simple_select("settinggroups", "gid", "name = 'oa_social_login'", array("limit" => 1));
    $gid = $db->fetch_field($query, "gid");

    return (int) $gid;
}

/**
 * Autodetect API Connection Handler
 * @return string result test
 */
function oa_social_login_admin_autodetect_api_connection_handler()
{
    //Check if CURL is available
    if (check_curl_available())
    {
        //Check CURL HTTPS - Port 443
        if (check_curl(true) === true)
        {
            echo 'success_autodetect_api_curl_https';
            die();
        }
        //Check CURL HTTP - Port 80
        elseif (check_curl(false) === true)
        {
            echo 'success_autodetect_api_curl_http';
            die();
        }
        else
        {
            echo 'error_autodetect_api_curl_ports_blocked';
            die();
        }
    }
    //Check if FSOCKOPEN is available
    elseif (check_fsockopen_available())
    {
        //Check FSOCKOPEN HTTPS - Port 443
        if (check_fsockopen(true) == true)
        {
            echo 'success_autodetect_api_fsockopen_https';
            die();
        }
        //Check FSOCKOPEN HTTP - Port 80
        elseif (check_fsockopen(false) == true)
        {
            echo 'success_autodetect_api_fsockopen_http';
            die();
        }
        else
        {
            echo 'error_autodetect_api_fsockopen_ports_blocked';
            die();
        }
    }

    //No working handler found
    echo 'error_autodetect_api_no_handler';
    die();
}

/**
 * Check API Settings - Ajax Call
 * @return void
 */
function admin_ajax_verify_api_settings()
{
    global $mybb, $lang;

    if (!$lang->oa_social_login)
    {
        $lang->load('oa_social_login');
    }

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
        $status_message = 'error_|' . $lang->oa_social_login_api_credentials_fill_out;
    }
    else
    {
        // Check the handler
        $api_connection_handler = ($api_connection_handler == 'fs' ? 'fsockopen' : 'curl');
        $api_connection_use_https = ($api_connection_port == 443 ? true : false);

        // FSOCKOPEN
        if ($api_connection_handler == 'fsockopen')
        {
            if (!check_fsockopen($api_connection_use_https))
            {
                $status_message = 'error|' . $lang->oa_social_login_api_credentials_use_auto;
            }
        }
        // CURL
        else
        {
            if (!check_curl($api_connection_use_https))
            {
                $status_message = 'error|' . $lang->oa_social_login_api_credentials_use_auto;
            }
        }

        // No errors until now.
        if (empty($status_message))
        {
            // The full domain has been entered.
            if (preg_match("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
            {
                $api_subdomain = $matches[1];
            }

            // Check format of the subdomain.
            if (!preg_match("/^[a-z0-9\-]+$/i", $api_subdomain))
            {
                $status_message = 'error|' . $lang->oa_social_login_api_credentials_subdomain_wrong;
            }
            else
            {
                // Construct full API Domain.
                $api_domain = $api_subdomain . '.api.oneall.com';
                $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_domain . '/tools/ping.json';

                // API Credentialls.
                $api_credentials = array();
                $api_credentials['api_key'] = $api_key;
                $api_credentials['api_secret'] = $api_secret;

                // Try to establish a connection.
                $result = do_api_request($api_connection_handler, $api_resource_url, $api_credentials);

                // Parse result.
                if (is_object($result) && property_exists($result, 'http_code') && property_exists($result, 'http_data'))
                {
                    switch ($result->http_code)
                    {
                        // Connection successfull.
                        case 200:
                            $status_message = 'success|' . $lang->oa_social_login_api_credentials_ok;
                            break;

                        // Authentication Error.
                        case 401:
                            $status_message = 'error|' . $lang->oa_social_login_api_credentials_keys_wrong;
                            break;

                        // Wrong Subdomain.
                        case 404:
                            $status_message = 'error|' . $lang->oa_social_login_api_credentials_subdomain_wrong;
                            break;

                        // Other error.
                        default:
                            $status_message = 'error|' . $lang->oa_social_login_api_credentials_check_com;
                            break;
                    }
                }
                else
                {
                    $status_message = 'error|' . $lang->oa_social_login_api_credentials_check_com;
                }
            }
        }
    }

    // Output for Ajax.
    die($status_message);
}

/**
 * Return User id from his token
 * @param  string $user_token User Token
 * @return string user id
 */
function oa_social_login_get_subdomain()
{
    global $db;

    $query = $db->simple_select("settings", "value", "name='oa_social_login_subdomain'", array('limit' => 1));
    $existing_token = $db->fetch_array($query);

    return !empty($existing_token['value']) ? $existing_token['value'] : false;
}

/**
 * Return a user_token by user id
 * @param  int $uid user id
 * @return string   User token
 */
function oa_social_login_get_user_token_by_uid($uid)
{
    global $db;

    $query = $db->simple_select("oa_social_login_user_token", "user_token", "uid='" . (int) $db->escape_string($uid) . "'", array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'desc'));
    $existing_token = $db->fetch_array($query);

    return !empty($existing_token['user_token']) ? $existing_token['user_token'] : false;
}

/**
 * Return  oa_social_login_user_tokenid from user token
 * @param  string $user_token user token
 * @return string             id
 */
function oa_social_login_get_id_by_user_token($user_token)
{
    global $db;

    $query = $db->simple_select("oa_social_login_user_token", "id", "user_token='" . $db->escape_string($user_token) . "'", array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'desc'));
    $existing_token = $db->fetch_array($query);

    return !empty($existing_token['id']) ? $existing_token['id'] : false;
}

/**
 * Return User id from his token
 * @param  string $user_token User Token
 * @return string user id
 */
function oa_social_login_get_userid_by_user_token($user_token)
{
    global $db;

    $query = $db->simple_select("oa_social_login_user_token", "uid", "user_token='" . $db->escape_string($user_token) . "'", array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'desc'));
    $existing_token = $db->fetch_array($query);

    return !empty($existing_token['uid']) ? $existing_token['uid'] : false;
}

/**
 * User id from identity token
 * @param  string $identity_token Identity Token
 * @return string                 User id
 */
function oa_social_login_get_userid_by_identity_token($identity_token)
{
    global $db;

    $query = $db->simple_select("oa_social_login_identity_token", "mybb_oa_social_login_user_tokenid", "identity_token='" . $db->escape_string($identity_token) . "'", array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'desc'));
    $existing_token = $db->fetch_array($query);

    if (!empty($existing_token['mybb_oa_social_login_user_tokenid']))
    {
        $query = $db->simple_select("oa_social_login_user_token", "uid", "id='" . $db->escape_string($existing_token['mybb_oa_social_login_user_tokenid']) . "'", array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'desc'));
        $existing_token = $db->fetch_array($query);

        return !empty($existing_token['uid']) ? $existing_token['uid'] : false;
    }

    return false;
}

/**
 * USer id from email
 * @param  string $email email
 * @return string        user id
 */
function oa_social_login_email_exists($email)
{
    global $db;

    $query = $db->simple_select("users", "uid", "email='" . $db->escape_string($email) . "'", array('limit' => 1, 'order_by' => 'uid', 'order_dir' => 'desc'));
    $existing_token = $db->fetch_array($query);

    return !empty($existing_token['uid']) ? $existing_token['uid'] : false;
}

/**
 * Save User token
 * @param  string $uid        user id
 * @param  string $user_token user token
 * @return int                inserted id
 */
function oa_social_login_add_user_token($uid, $user_token)
{
    global $db;

    $oa_user_token_fields = array();
    $oa_user_token_fields['uid'] = $uid;
    $oa_user_token_fields['user_token'] = $user_token;
    $oa_user_token_fields['date_creation'] = time();
    $inserted_id = $db->insert_query("oa_social_login_user_token", $oa_user_token_fields);

    return $inserted_id;
}

/**
 * Save Identity token
 * @param  string $oa_social_login_user_tokenid oa_social_login_user_token id
 * @param  string $identity_token               identity token
 * @param  string $identity_provider            provider name
 * @return int                inserted id
 */
function oa_social_login_add_identity_token($oa_social_login_user_tokenid, $identity_token, $identity_provider)
{
    global $db;

    $oa_identity_token_fields = array();
    $oa_identity_token_fields['mybb_oa_social_login_user_tokenid'] = $oa_social_login_user_tokenid;
    $oa_identity_token_fields['identity_token'] = $identity_token;
    $oa_identity_token_fields['identity_provider'] = $identity_provider;
    $oa_identity_token_fields['date_creation'] = time();
    $inserted_id = $db->insert_query("oa_social_login_identity_token", $oa_identity_token_fields);

    return $inserted_id;
}

/**
 * Handle the callback
 * @return void
 */
function oa_social_login_callback()
{
    global $mybb, $lang, $plugins, $db, $templates;

    //Callback Handler
    if (isset($_POST) and !empty($_POST['oa_action']) and $_POST['oa_action'] == 'social_login' and !empty($_POST['connection_token']))
    {
        $lang->load('oa_social_login');

        //OneAll Connection token
        $connection_token = trim($_POST['connection_token']);

        // Read arguments.
        $api_subdomain = trim(strtolower($mybb->settings['oa_social_login_subdomain']));
        $api_connection_port = $mybb->settings['oa_social_login_connection_port'];
        $api_connection_handler = $mybb->settings['oa_social_login_connection_handler'];

        // Check the handler
        $api_connection_handler = ($api_connection_handler == 'fs' ? 'fsockopen' : 'curl');
        $api_connection_use_https = ($api_connection_port == 443 ? true : false);

        //We cannot make a connection without a subdomain
        if (!empty($api_subdomain))
        {
            //See: http://docs.oneall.com/api/resources/connections/read-connection-details/
            $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . '.api.oneall.com/connections/' . $connection_token . '.json';

            //API Credentials
            $api_opts = array();
            $api_key = trim($mybb->settings['oa_social_login_public_key']);
            $api_secret = trim($mybb->settings['oa_social_login_private_key']);
            $api_opts['api_key'] = $api_key;
            $api_opts['api_secret'] = $api_secret;

            //Retrieve connection details
            $result = do_api_request($api_connection_handler, $api_resource_url, $api_opts);

            //Check result
            if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200 and property_exists($result, 'http_data'))
            {
                //Decode result
                $decoded_result = @json_decode($result->http_data);
                if (is_object($decoded_result) and isset($decoded_result->response->result->data->user))
                {
                    //User data
                    $user_data = $decoded_result->response->result->data->user;

                    //Social network profile data
                    $identity = $user_data->identity;

                    //Unique user token provided by OneAll
                    $user_token = $user_data->user_token;

                    //Unique identity token provided by OneAll
                    $identity_token = $identity->identity_token;

                    //Identity Provider
                    $user_identity_provider = $identity->source->name;

                    //Thumbnail
                    $user_thumbnail = (!empty($identity->thumbnailUrl) ? trim($identity->thumbnailUrl) : '');

                    //Picture
                    $user_picture = (!empty($identity->pictureUrl) ? trim($identity->pictureUrl) : '');

                    //About Me
                    $user_about_me = (!empty($identity->aboutMe) ? trim($identity->aboutMe) : '');

                    //Note
                    $user_note = (!empty($identity->note) ? trim($identity->note) : '');

                    //Firstname
                    $user_first_name = (!empty($identity->name->givenName) ? $identity->name->givenName : '');

                    //Lastname
                    $user_last_name = (!empty($identity->name->familyName) ? $identity->name->familyName : '');

                    //Fullname
                    if (!empty($identity->name->formatted))
                    {
                        $user_full_name = $identity->name->formatted;
                    }
                    elseif (!empty($identity->name->displayName))
                    {
                        $user_full_name = $identity->name->displayName;
                    }
                    else
                    {
                        $user_full_name = trim($user_first_name . ' ' . $user_last_name);
                    }

                    // Email Address.
                    $user_email = '';
                    $user_email_is_verified = false;
                    if (property_exists($identity, 'emails') and is_array($identity->emails))
                    {
                        while ($user_email_is_verified !== true and (list(, $email) = each($identity->emails)))
                        {
                            $user_email = $email->value;
                            $user_email_is_verified = ($email->is_verified == '1');
                        }
                    }
                    else
                    {
                        $user_email = oa_social_login_create_rand_email();
                    }

                    //User Website
                    if (!empty($identity->profileUrl))
                    {
                        $user_website = $identity->profileUrl;
                    }
                    elseif (!empty($identity->urls[0]->value))
                    {
                        $user_website = $identity->urls[0]->value;
                    }
                    else
                    {
                        $user_website = '';
                    }

                    //Preferred Username
                    if (!empty($identity->preferredUsername))
                    {
                        $user_login = $identity->preferredUsername;
                    }
                    elseif (!empty($identity->displayName))
                    {
                        $user_login = $identity->displayName;
                    }
                    else
                    {
                        $user_login = $user_full_name;
                    }

                    //Sanitize Login
                    $user_login = str_replace('.', '-', $user_login);
                    $user_login = sanitize_user($user_login, true);

                    // Get user by token
                    $user_id = oa_social_login_get_userid_by_user_token($user_token);

                    //Linking enabled?
                    if (!isset($mybb->settings['oa_social_login_link_verified_accounts']) or $mybb->settings['oa_social_login_link_verified_accounts'] == '1')
                    {
                        //Try to link to existing account
                        if (!is_numeric($user_id))
                        {
                            //Only if email is verified
                            if (!empty($user_email) and $user_email_is_verified === true)
                            {
                                //Read existing user
                                if (($user_id_tmp = oa_social_login_email_exists($user_email)) !== false)
                                {
                                    // user already get a user token ?
                                    $existing_user_token = oa_social_login_get_user_token_by_uid($user_id_tmp);

                                    $user_id = $user_id_tmp;

                                    // Email already used and user have another user token
                                    if (!empty($existing_user_token))
                                    {
                                        // OneAll API Relink : link new Identity token with existing user token
                                        oa_social_login_link_identity_to_usertoken($identity_token, $existing_user_token, $user_identity_provider);
                                    }
                                    else
                                    {
                                        // Save User token and Identity Token
                                        $oa_social_login_user_tokenid = oa_social_login_add_user_token($user_id_tmp, $user_token);
                                        oa_social_login_add_identity_token($oa_social_login_user_tokenid, $identity_token, $user_identity_provider);
                                    }
                                }
                                else
                                {
                                    // nothing to do, account will be created after
                                }
                            }
                        }
                        else // User token already existing
                        {
                            // identity already exists ?
                            $user_id = oa_social_login_get_userid_by_identity_token($identity_token);

                            // identity token unknown
                            if (!is_numeric($user_id))
                            {
                                // get oa_social_login_user_tokenid for table link
                                $oa_social_login_user_tokenid = oa_social_login_get_id_by_user_token($user_token);

                                // add identity token
                                oa_social_login_add_identity_token($oa_social_login_user_tokenid, $identity_token, $user_identity_provider);
                            }
                            else
                            {
                                // nothing to do, user will be logged in
                            }
                        }
                    }

                    //New User
                    if (!is_numeric($user_id))
                    {
                        //Username is mandatory
                        if (!isset($user_login) or strlen(trim($user_login)) == 0)
                        {
                            $user_login = $user_identity_provider . 'User';
                        }

                        // BuddyPress : See bp_core_strip_username_spaces()
                        if (function_exists('bp_core_strip_username_spaces'))
                        {
                            $user_login = str_replace(' ', '-', $user_login);
                        }

                        //Username must be unique
                        require_once MYBB_ROOT . "inc/functions_user.php";
                        if (username_exists($user_login))
                        {
                            $i = 1;
                            $user_login_tmp = $user_login;
                            do
                            {
                                $user_login_tmp = $user_login . ($i++);
                            } while (username_exists($user_login_tmp));
                            $user_login = $user_login_tmp;
                        }

                        //Setup the user's password
                        $user_password = oa_generate_password();

                        // Determine the usergroup stuff
                        $additionalgroups = '';

                        // Determine the usergroup stuff
                        if ($mybb->settings['regtype'] == "verify" || $mybb->settings['regtype'] == "admin" || $mybb->settings['regtype'] == "both" || isset($mybb->cookies['coppauser']))
                        {
                            $usergroup = 5;
                        }
                        else
                        {
                            $usergroup = 2;
                        }

                        // Set up user handler.
                        require_once MYBB_ROOT . "inc/datahandlers/user.php";
                        $userhandler = new UserDataHandler('insert');

                        // Set the data for the new user.
                        $new_user = array(
                            "username" => $user_login,
                            "password" => $user_password,
                            "password2" => $user_password,
                            "email" => $user_email,
                            "usergroup" => $usergroup,
                            "registration" => true,
                            "profile_fields_editable" => true
                        );

                        // Upload avatar from Social Networks
                        if ($mybb->settings['oa_social_login_avatar'] == 1)
                        {
                            $new_user["avatar"] = $user_picture;
                        }

                        // Set the data of the user in the datahandler.
                        $userhandler->set_data($new_user);
                        $errors = '';

                        // Email must be unique
                        $placeholder_email_used = false;
                        while (!$userhandler->verify_email())
                        {
                            $user_email = oa_social_login_create_rand_email();

                            // Set the data for the new user.
                            $new_user['email'] = $user_email;

                            // Set the data of the user in the datahandler.
                            $userhandler->set_data($new_user);
                        }

                        // Validate the user and get any errors that might have occurred.
                        if (!$userhandler->validate_user())
                        {
                            $errors = $userhandler->get_friendly_errors();
                        }
                        else
                        {
                            $plugins->run_hooks("member_do_register_start");

                            $user_info = $userhandler->insert_user();

                            // Save User token and Identity Token
                            $oa_social_login_user_tokenid = oa_social_login_add_user_token($user_info['uid'], $user_token);
                            oa_social_login_add_identity_token($oa_social_login_user_tokenid, $identity_token, $user_identity_provider);

                            // register and redirect user
                            register_user($user_info);
                            exit();
                        }
                    }

                    //Sucess
                    require_once MYBB_ROOT . '/inc/functions.php';
                    $user_data = get_user($user_id);

                    // Log User (user found)
                    if ($user_data !== false)
                    {
                        // Log User
                        login_user($user_data);

                        // redirect user
                        $lang->load("member");
                        oa_social_login_redirect(true, $lang->redirect_loggedin);
                        exit();
                    }
                    else
                    {
                        oa_social_login_redirect(true, $lang->oa_social_login_error);
                        exit();
                    }
                }
                else
                {
                    oa_social_login_redirect(true, $lang->oa_social_login_error);
                    exit();
                }
            }
            else
            {
                oa_social_login_redirect(true, $lang->oa_social_login_error);
                exit();
            }
        }
        else
        {
            oa_social_login_redirect(true, $lang->oa_social_login_error);
            exit();
        }
    }
}

/**
 *
 */
/**
 * OneALl Social Link - link new identity token to user token
 * @param  string $identity_token         identity token
 * @param  string $existing_user_token    user token
 * @param  string $user_identity_provider provider name
 * @return void
 */
function oa_social_login_link_identity_to_usertoken($identity_token, $existing_user_token, $user_identity_provider)
{
    global $mybb;

    if (!empty($existing_user_token) && !empty($identity_token))
    {
        // Read arguments.
        $api_subdomain = trim(strtolower($mybb->settings['oa_social_login_subdomain']));
        $api_connection_port = $mybb->settings['oa_social_login_connection_port'];
        $api_connection_handler = $mybb->settings['oa_social_login_connection_handler'];

        // Check the handler
        $api_connection_handler = ($api_connection_handler == 'fs' ? 'fsockopen' : 'curl');
        $api_connection_use_https = ($api_connection_port == 443 ? true : false);

        //See: http://docs.oneall.com/api/resources/identities/relink-identity/
        $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . '.api.oneall.com/identities/' . $identity_token . '/link.json';

        //API Credentials
        $api_opts = array();
        $api_key = trim($mybb->settings['oa_social_login_public_key']);
        $api_secret = trim($mybb->settings['oa_social_login_private_key']);
        $api_opts['api_key'] = $api_key;
        $api_opts['api_secret'] = $api_secret;
        $api_opts['custom_request'] = 'PUT';

        // Message Structure
        $data = array(
            'request' => array(
                'user' => array(
                    'user_token' => $existing_user_token
                )
            )
        );
        // Encode structure
        $request_structure_json = json_encode($data);

        //Retrieve connection details
        $result = do_api_request($api_connection_handler, $api_resource_url, $api_opts, $request_structure_json);

        // Parse result.
        if (is_object($result) && property_exists($result, 'http_code') && property_exists($result, 'http_data'))
        {
            switch ($result->http_code)
            {
                // Link successfull : save of the identity token in db
                case 200:
                    // get oa_social_login_user_tokenid for table link
                    $oa_social_login_user_tokenid = oa_social_login_get_id_by_user_token($existing_user_token);

                    // add identity token
                    oa_social_login_add_identity_token($oa_social_login_user_tokenid, $identity_token, $user_identity_provider);

                    break;

                // Other error.
                default:
                    redirect(oa_social_login_get_current_url(), $lang->oa_social_login_error);
                    exit();
            }
        }
        else
        {
            redirect(oa_social_login_get_current_url(), $lang->oa_social_login_error);
            exit();
        }
    }
}

/**
 * Login an user
 * @param  array $user_info user info
 * @return boolean
 */
function login_user($user_info = array())
{
    global $mybb, $session, $db;

    if (!$user_info)
    {
        $user_info = $mybb->user;
    }

    if (!$user_info['uid'] or !$user_info['loginkey'] or !$session)
    {
        return false;
    }

    // Delete old sessions
    $db->delete_query("sessions", "ip='" . $db->escape_string($session->ipaddress) . "' and sid != '" . $session->sid . "'");

    // Update session
    $db->update_query("sessions", array("uid" => $user_info['uid']), "sid='" . $session->sid . "'");

    // Log User
    my_setcookie("mybbuser", $user_info['uid'] . "_" . $user_info['loginkey'], null, true);
    my_setcookie("sid", $session->sid, -1, true);

    return true;
}

/**
 * Register an user
 * @param  array  $user_info user info
 * @return void
 */
function register_user($user_info = array())
{
    global $mybb, $lang, $db, $plugins;

    require_once MYBB_ROOT . $mybb->config['admin_dir'] . "/inc/functions.php";

    $lang->load("member");

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
    else if ($mybb->settings['regtype'] == "verify")
    {
        $activationcode = random_str();
        $now = TIME_NOW;
        $activationarray = array(
            "uid" => $user_info['uid'],
            "dateline" => TIME_NOW,
            "code" => $activationcode,
            "type" => "r"
        );
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
    else if ($mybb->settings['regtype'] == "randompass")
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
    else if ($mybb->settings['regtype'] == "admin")
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
    else if ($mybb->settings['regtype'] == "both")
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
            "type" => "b"
        );
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
