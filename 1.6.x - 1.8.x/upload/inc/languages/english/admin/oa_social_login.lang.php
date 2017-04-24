<?php
// Installation
$l['oa_social_login'] = "OneAll Social Login";

$l['oa_social_login_create_account'] = "To be able to use Social Login, you first of all have to create a free account at <a href=\"https://app.oneall.com/signup/\" class=\"external\">http://www.oneall.com</a> and setup a Site.";

$l['oa_social_login_setup_free_account'] = "Setup my free account";
$l['oa_social_login_create_credential'] = "Create and view my API Credentials.";

$l['setting_group_oa_social_login'] = "OneAll Social Login";
$l['setting_group_oa_social_login_desc'] = "Allow your visitors to login and register with social networks like Twitter, Facebook, LinkedIn, Hyves, VKontakte, Google and Yahoo amongst others. Social Login increases your user registration rate by simplifying the registration process and provides permission-based social data retrieved from the social network profiles. Social Login integrates with your existing registration system so you and your users don't have to start from scratch.";

// Settings
$l['setting_oa_social_login_enable'] = "Enable Social Login ? ";
$l['setting_oa_social_login_enable_desc'] = "Allows you to temporarily disable Social Login without having to remove it.";

$l['setting_oa_social_login_link_verified_accounts'] = "Automatically link Social Network accounts to existing user accounts ? ";
$l['setting_oa_social_login_link_verified_accounts_desc'] = "If enabled, social network accounts with a verified email address will be linked to existing myBB user accounts having the same email address.";

$l['setting_oa_social_login_api_subdomain'] = "Api Subdomain";
$l['setting_oa_social_login_api_subdomain_desc'] = "Enter your OneAll API Subdomain.";

$l['setting_oa_social_login_api_public_key'] = "Api Public Key";
$l['setting_oa_social_login_api_public_key_desc'] = "Enter your OneAll API Public Key.";

$l['setting_oa_social_login_api_private_key'] = "Api Private Key";
$l['setting_oa_social_login_api_private_key_desc'] = "Enter your OneAll API Private Key.";

$l['setting_oa_social_login_verify_api_settings'] = "Verify API Settings";
$l['setting_oa_social_login_autodetect_api_connection_handler'] = "Autodetect API Connection";

$l['setting_oa_social_login_api_connection_handler'] = "API Connection Handler";
$l['setting_oa_social_login_api_connection_handler_desc'] = "OneAll is a connexion manager to the API of the Social Medias.
<ul><li>Using CURL is recommended but it might be disabled on some servers. (<a href='http://www.php.net/manual/en/book.curl.php' target='_blank'>CURL Manual</a>)</li>
<li>Only use FSOCKOPEN if you encounter any problems with CURL. (<a href='http://www.php.net/manual/en/function.fsockopen.php' target='_blank'>FSOCKOPEN Manual</a>)</li></ul>";

$l['setting_oa_social_login_connection_port_443'] = "Communication via HTTPS on port 443";
$l['setting_oa_social_login_connection_port_80'] = "Communication via HTTP on port 80";
$l['setting_oa_social_login_connection_port'] = "API Connection Port";
$l['setting_oa_social_login_connection_port_desc'] = "Your firewall must allow outgoing requests on either port 80 or 443.
<ul><li>Using port 443 is recommended but you might have to install OpenSSL on your server.</li>
<li>Using port 80 is a bit faster, does not need OpenSSL but is less secure.</li></ul>";

$l['setting_oa_social_login_main_page'] = "If enabled, Social Login will be displayed on the main page.";
$l['setting_oa_social_login_main_page_desc'] = "Display on the main page ?";

$l['setting_oa_social_login_main_page_caption'] = "This title is displayed above the Social Login icons on the main page.";
$l['setting_oa_social_login_main_page_caption_desc'] = "Main page caption";

$l['setting_oa_social_login_login_page'] = "If enabled, Social Login will be displayed on the login page.";
$l['setting_oa_social_login_login_page_desc'] = "Display on the login page ?";

$l['setting_oa_social_login_login_page_caption'] = "This title is displayed above the Social Login icons on the login page.";
$l['setting_oa_social_login_login_page_caption_desc'] = "Login page caption";

$l['setting_oa_social_login_member_page'] = "If enabled, Social Login will be displayed on the member page.";
$l['setting_oa_social_login_member_page_desc'] = "Display on the member page ?";

$l['setting_oa_social_login_member_page_caption'] = "This title is displayed above the Social Login icons on the member page.";
$l['setting_oa_social_login_member_page_caption_desc'] = "Member page caption";

$l['setting_oa_social_login_registration_page'] = "If enabled, Social Login will be displayed on the registration page.";
$l['setting_oa_social_login_registration_page_desc'] = "Display on the registration page ?";

$l['setting_oa_social_login_registration_page_caption'] = "This title is displayed above the Social Login icons on the registration page.";
$l['setting_oa_social_login_registration_page_caption_desc'] = "Registration page caption";

$l['setting_oa_social_login_other_page'] = "If enabled, Social Login will also be displayed on any other pages of the forum.";
$l['setting_oa_social_login_other_page_desc'] = "Display on any other pages ?";

$l['setting_oa_social_login_other_page_caption'] = "This title is displayed above the Social Login icons on the other pages.";
$l['setting_oa_social_login_other_page_caption_desc'] = "Caption on other pages";

$l['setting_oa_social_login_link_display'] = "Enable social network account linking ?";

$l['setting_oa_social_login_avatars_display'] = "Enable uploading avatars from social network ?";

$l['setting_oa_social_login_avatar'] = "Allow retrieving the user's avatar from his social network profile.";
$l['setting_oa_social_login_avatar_desc'] = "Enable uploading avatars from social network ? ";

$l['setting_oa_social_login_redirection'] = "Enter a full URL to a page of your myBB. If left empty the user stays on the same page.";
$l['setting_oa_social_login_redirection_desc'] = "Redirect users to this page after they have connected with their social network account: ";

$l['setting_oa_social_login_redirection_display'] = "Redirection";
$l['setting_oa_social_login_social_network_display'] = "Choose the social networks to enable on your forum";

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

// Errors
$l['oa_social_login_api_credentials_check_com'] = 'Could not contact API. Is the API connection setup properly ?';
$l['oa_social_login_api_credentials_fill_out'] = 'Please fill out each of the fields above.';
$l['oa_social_login_api_credentials_keys_wrong'] = 'The API credentials are wrong, please check your public/private key.';
$l['oa_social_login_api_credentials_ok'] = 'The settings are correct - do not forget to save your changes!';
$l['oa_social_login_api_credentials_subdomain_wrong'] = 'The subdomain does not exist. Have you filled it out correctly ?';
$l['oa_social_login_api_credentials_use_auto'] = 'The connection handler does not seem to work. Please use the Autodetection.';

// Popup
$l['oa_social_login_login_network'] = 'Login with your social network account';
