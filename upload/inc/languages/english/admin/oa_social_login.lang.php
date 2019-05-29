<?php
/**
 * @package   	OneAll Social Login
 * @copyright 	Copyright 2011-Present http://www.oneall.com
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

// Installation
$l['oa_social_login'] = 'OneAll Social Login';

$l['oa_social_login_create_account'] = "To be able to use Social Login, you first of all have to create a free account at <a href=\"https://app.oneall.com/signup/\" class=\"external\">http://www.oneall.com</a> and setup a Site.";

$l['oa_social_login_setup_free_account'] = "Setup my free account";
$l['oa_social_login_create_credential'] = "Create and view my API Credentials.";

$l['setting_group_oa_social_login'] = "OneAll Social Login";
$l['setting_group_oa_social_login_desc'] = "Allow your visitors to login and register with social networks like Twitter, Facebook, LinkedIn, Hyves, VKontakte, Google and Yahoo amongst others. Social Login increases your user registration rate by simplifying the registration process and provides permission-based social data retrieved from the social network profiles. Social Login integrates with your existing registration system so you and your users don't have to start from scratch.";


$l['setting_oa_social_login_api_subdomain'] = "Api Subdomain";
$l['setting_oa_social_login_api_subdomain_desc'] = "Enter your OneAll API Subdomain.";

$l['setting_oa_social_login_api_public_key'] = "Api Public Key";
$l['setting_oa_social_login_api_public_key_desc'] = "Enter your OneAll API Public Key.";

$l['setting_oa_social_login_api_private_key'] = "Api Private Key";
$l['setting_oa_social_login_api_private_key_desc'] = "Enter your OneAll API Private Key.";

$l['setting_oa_social_login_verify_api_settings'] = "Verify API Settings";
$l['setting_oa_social_login_autodetect_api_connection_handler'] = "Autodetect API Connection";

$l['setting_oa_social_login_api_connection_handler'] = "API Connection Handler";
$l['setting_oa_social_login_api_connection_handler_desc'] = "Select the handler to use to establish a connection with the OneAll API.
<ul><li>Using CURL is recommended but it might be disabled on some servers. (<a href='http://www.php.net/manual/en/book.curl.php' target='_blank'>CURL Manual</a>)</li>
<li>Only use FSOCKOPEN if you encounter any problems with CURL. (<a href='http://www.php.net/manual/en/function.fsockopen.php' target='_blank'>FSOCKOPEN Manual</a>)</li></ul>";

$l['setting_oa_social_login_connection_port_443'] = "Communication via HTTPS on port 443";
$l['setting_oa_social_login_connection_port_80'] = "Communication via HTTP on port 80";
$l['setting_oa_social_login_connection_port'] = "API Connection Port";
$l['setting_oa_social_login_connection_port_desc'] = "Your firewall must allow outgoing requests on either port 80 or 443.
<ul><li>Using port 443 is recommended but you might have to install OpenSSL on your server.</li>
<li>Using port 80 is a bit faster, does not need OpenSSL but is less secure.</li></ul>";


$l['setting_oa_social_login_main_page_desc'] = "Main Page \ Display Social Login?";
$l['setting_oa_social_login_main_page'] = "If enabled, Social Login will be displayed on the homepage of your forum.";
$l['setting_oa_social_login_main_page_caption_desc'] = "Main Page \ Caption";
$l['setting_oa_social_login_main_page_caption'] = "This title is displayed above the Social Login icons on the main page.";

$l['setting_oa_social_login_login_page_desc'] = "Login Popup \ Display Social Login?";
$l['setting_oa_social_login_login_page'] = "If enabled, Social Login will be displayed inside of the login popup.";
$l['setting_oa_social_login_login_page_caption_desc'] = "Login Popup \ Caption";
$l['setting_oa_social_login_login_page_caption'] = "This title is displayed above the Social Login icons in the login popup.";

$l['setting_oa_social_login_member_page_desc'] = "No-Permission Notification \ Display Social Login?";
$l['setting_oa_social_login_member_page'] = "If enabled, Social Login will be displayed on the page that is shown whenever a user accesses a page for which he lacks the permission.";
$l['setting_oa_social_login_member_page_caption_desc'] = "No-Permission Notification \ Caption";
$l['setting_oa_social_login_member_page_caption'] = "This title is displayed above the Social Login icons displayed on the no-permission notification page.";

$l['setting_oa_social_login_registration_page_desc'] = "Registration Page \ Display Social Login?";
$l['setting_oa_social_login_registration_page'] = "If enabled, Social Login will be displayed on the registration page.";
$l['setting_oa_social_login_registration_page_caption_desc'] = "Registration Page \ Caption";
$l['setting_oa_social_login_registration_page_caption'] = "This title is displayed above the Social Login icons on the registration page.";

$l['setting_oa_social_login_other_page_desc'] = "Other Pages \ Display Social Login?";
$l['setting_oa_social_login_other_page'] = "If enabled, Social Login will also be displayed on any other pages of the forum.";
$l['setting_oa_social_login_other_page_caption_desc'] = "Other Pages \ Caption";
$l['setting_oa_social_login_other_page_caption'] = "This title is displayed above the Social Login icons on the other pages.";


// Social Link
$l['setting_oa_social_login_link_legend'] = "Do you want to use Social Link?";
$l['setting_oa_social_login_link_verified_accounts'] = "Automatically link Social Network accounts to existing user accounts ? ";
$l['setting_oa_social_login_link_verified_accounts_desc'] = "If enabled, social network accounts with a verified email address will automatically be linked to existing myBB user accounts having the same email address.";
$l['setting_oa_social_login_link_profile_title'] = "Social Link \ Enable in the user's profile?";
$l['setting_oa_social_login_link_profile_desc'] = "If enabled, users can open their profile settings and link their social network accounts to their existing myBB account.";
$l['setting_oa_social_login_link_caption_title'] = "Social Link \ Caption";
$l['setting_oa_social_login_link_caption_desc'] = "This title is displayed above the Social Link icons in the user's profile.";


$l['setting_oa_social_login_avatars_display'] = "Do you want to use Social Network avatars?";

$l['setting_oa_social_login_avatar'] = "If enabled the user's social network photo will be user as avatar on the forum.";
$l['setting_oa_social_login_avatar_desc'] = "Enable social network avatars ? ";


$l['setting_oa_social_login_redirection_display'] = "Where do you want to redirect users to?";
$l['setting_oa_social_login_redirection_desc'] = "Redirect users to this page after they have connected with their social network account: ";
$l['setting_oa_social_login_redirection'] = "Enter a full URL to a page of your myBB. If left empty the user stays on the same page.";


$l['setting_oa_social_login_social_network_display'] = "Which Social Networks do you want to enable?";

$l['setting_oa_social_login_where_display'] = "Where do you want to display Social Login ?";

$l['setting_oa_social_login_enable_display'] = "Enable Social Login ?";
$l['setting_oa_social_login_api_connection_display'] = "API Connection";
$l['setting_oa_social_login_api_credential_display'] = "API Credentials - <a href=\"https://app.oneall.com/applications/\" class=\"external\" target=\"_blank\">Click here to create or view your API Credentials";

$l['setting_oa_social_login_create_account_help'] = "Help, Updates &amp; Documentation";
$l['setting_oa_social_login_create_account_follow_us'] = "<a href=\"http://www.twitter.com/oneall\" class=\"external\" target=\"_blank\">Follow us</a> on Twitter to stay informed about updates;";
$l['setting_oa_social_login_create_account_read'] = "<a href=\"http://docs.oneall.com/plugins/\" class=\"external\" target=\"_blank\">Read</a> the online documentation for more information about this plugin;";
$l['setting_oa_social_login_create_account_discover'] = "<a href=\"http://docs.oneall.com/plugins/\" class=\"external\" target=\"_blank\">Discover</a> our turnkey plugins for Drupal, Joomla, WordPress;";
$l['setting_oa_social_login_create_account_contact'] = "<a href=\"http://www.oneall.com/company/contact-us/\" class=\"external\" target=\"_blank\">Contact us</a> if you have feedback or need assistance!";
$l['setting_oa_social_login_create_account_use'] = "To be able to use Social Login, you first of all have to create a free account at <a href=\"https://app.oneall.com/signup/\" class=\"external\" target=\"_blank\">http://www.oneall.com</a> and setup a Site.";

// API Loading Message
$l['oa_social_login_api_please_wait'] = 'Contacting API - please wait this may take a few minutes ...';

// Verify API Credentials
$l['oa_social_login_api_fill_credentials'] = 'Please fill out each of the fields above.';
$l['oa_social_login_api_error_use_auto'] = 'The connection handler does not seem to work. Please use the Autodetection.';
$l['oa_social_login_api_subdomain_not_found'] = 'The subdomain does not exist. Have you filled it out correctly ?';
$l['oa_social_login_api_connection_ok'] = 'The settings are correct - do not forget to save your changes!';
$l['oa_social_login_api_credentials_wrong'] = 'The API credentials are wrong, please check your public/private key.';
$l['oa_social_login_api_check_com'] = 'Could not contact API. Is the API connection setup properly ?';

// Autodetect API Handler
$l['oa_social_login_api_curl_443'] = 'Detected CURL on Port 443 - do not forget to save your changes!';
$l['oa_social_login_api_curl_80'] = 'Detected CURL on Port 80 - do not forget to save your changes!';
$l['oa_social_login_api_curl_blocked'] = 'CURL is available but both ports (80, 443) are blocked for outbound requests.';
$l['oa_social_login_api_fsockopen_443'] = 'Detected FSOCKOPEN on Port 443 - do not forget to save your changes!';
$l['oa_social_login_api_fsockopen_80'] = 'Detected FSOCKOPEN on Port 80 - do not forget to save your changes!';
$l['oa_social_login_api_fsockopen_blocked'] = 'FSOCKOPEN is available but both ports (80, 443) are blocked for outbound requests.';
$l['oa_social_login_api_error'] = 'Autodetection Error - Please either install CURL or enable FSOCKOPEN on your server.';