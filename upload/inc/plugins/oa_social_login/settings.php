<?php

/**
 * @package       OneAll Social Login
 * @copyright     Copyright 2011-Present http://www.oneall.com
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
function oa_social_login_get_settings()
{
    global $lang;

    // Load language
    if (!$lang->oa_social_login)
    {
        $lang->load('oa_social_login');
    }

    return [
        'create_account' => [
            'optionscode' => 'php
    <div class=\"section welcome\">
        <h3>Help, Updates &amp; Documentation</h3>
        <ul>
            <li><a href=\"http://www.twitter.com/oneall\" class=\"external\" target=\"_blank\">Follow us</a> on Twitter to stay informed about updates;</li>
            <li><a href=\"http://docs.oneall.com/plugins/\" class=\"external\" target=\"_blank\">Read</a> the online documentation for more information about this plugin;</li>
            <li><a href=\"http://docs.oneall.com/plugins/\" class=\"external\" target=\"_blank\">Discover</a> our turnkey plugins for Drupal, Joomla, WordPress;</li>
            <li><a href=\"http://www.oneall.com/company/contact-us/\" class=\"external\" target=\"_blank\">Contact us</a> if you have feedback or need assistance!</li>
        </ul>
    </div>
    <div class=\"section get_started\">
        <h4>To be able to use Social Login, you first of all have to create a free account at <a href=\"https://app.oneall.com/signup/\" class=\"external\" target=\"_blank\">http://www.oneall.com</a> and setup a Site.</h4>
        <p>
            <a href=\"https://app.oneall.com/signup/\" class=\"button1 external\" target=\"_blank\">' . $lang->oa_social_login_setup_free_account . '</a> | <a href=\"https://app.oneall.com/applications/\" class=\"button1 external\" target=\"_blank\">' . $lang->oa_social_login_create_credential . '</a>
        </p>
    </div>' ],
        'api_connection_display' => [
            'optionscode' => 'php
    <p>
       <legend>' . $lang->setting_oa_social_login_api_connection_display . '</legend>
    </p>' ],
        'connection_handler' => [
            'title' => $lang->setting_oa_social_login_api_connection_handler,
            'description' => $lang->setting_oa_social_login_api_connection_handler_desc,
            'value' => 'cr',
            'optionscode' => "select\ncr=PHP CURL\nfs=PHP FSOCKOPEN"],
        'connection_port' => [
            'title' => $lang->setting_oa_social_login_connection_port,
            'description' => $lang->setting_oa_social_login_connection_port_desc,
            'value' => '443',
            'optionscode' => "select\n443=" . $lang->setting_oa_social_login_connection_port_443 . "\n80=" . $lang->setting_oa_social_login_connection_port_80],
        'autodetect_button' => [
            'optionscode' => 'php
    <p>
        <span id=\"oa_social_login_api_connection_handler_result\"></span>
    </p>
    <input class=\"button2\" type=\"button\" id=\"oa_social_login_autodetect_api_connection_handler\" value=\"' . $lang->setting_oa_social_login_autodetect_api_connection_handler . '\" />'],
        'api_credential_display' => [
            'optionscode' => 'php
    <p>
       <legend>API Credentials - <a href=\"https://app.oneall.com/applications/\" class=\"external\" target=\"_blank\">Click here to create or view your API Credentials</legend>
    </p>' ],
        'subdomain' => [
            'title' => $lang->setting_oa_social_login_api_subdomain,
            'description' => $lang->setting_oa_social_login_api_subdomain_desc,
            'value' => '',
            'optionscode' => 'text'],
        'public_key' => [
            'title' => $lang->setting_oa_social_login_api_public_key,
            'description' => $lang->setting_oa_social_login_api_public_key_desc,
            'value' => '',
            'optionscode' => 'text'],
        'private_key' => [
            'title' => $lang->setting_oa_social_login_api_private_key,
            'description' => $lang->setting_oa_social_login_api_private_key_desc,
            'value' => '',
            'optionscode' => 'text'],
        'api_verify_button' => [
            'optionscode' => 'php
    <p>
        <span id=\"oa_social_login_api_test_result\"></span>
    </p>
    <input class=\"button2\" type=\"button\" id=\"oa_social_login_test_api_settings\" value=\"' . $lang->setting_oa_social_login_verify_api_settings . '\" />'],
        'where_display' => [
            'optionscode' => 'php
    <p>
       <legend>' . $lang->setting_oa_social_login_where_display . '</legend>
    </p>' ],
        'main_page' => [
            'title' => $lang->setting_oa_social_login_main_page_desc,
            'description' => $lang->setting_oa_social_login_main_page,
            'optionscode' => 'yesno',
            'value' => 0],
        'main_page_caption' => [
            'title' => $lang->setting_oa_social_login_main_page_caption_desc,
            'description' => $lang->setting_oa_social_login_main_page_caption,
            'optionscode' => 'text',
            'value' => 'Connect with your social network account'],
        'login_page' => [
            'title' => $lang->setting_oa_social_login_login_page_desc,
            'description' => $lang->setting_oa_social_login_login_page,
            'optionscode' => 'yesno',
            'value' => 1],
        'login_page_caption' => [
            'title' => $lang->setting_oa_social_login_login_page_caption_desc,
            'description' => $lang->setting_oa_social_login_login_page_caption,
            'optionscode' => 'text',
            'value' => 'Or login with your social network account'],
        'member_page' => [
            'title' => $lang->setting_oa_social_login_member_page_desc,
            'description' => $lang->setting_oa_social_login_member_page,
            'optionscode' => 'yesno',
            'value' => 1],
        'member_page_caption' => [
            'title' => $lang->setting_oa_social_login_member_page_caption_desc,
            'description' => $lang->setting_oa_social_login_member_page_caption,
            'optionscode' => 'text',
            'value' => 'Or login with your social network account'],

        'registration_page' => [
            'title' => $lang->setting_oa_social_login_registration_page_desc,
            'description' => $lang->setting_oa_social_login_registration_page,
            'optionscode' => 'yesno',
            'value' => 1],
        'registration_page_caption' => [
            'title' => $lang->setting_oa_social_login_registration_page_caption_desc,
            'description' => $lang->setting_oa_social_login_registration_page_caption,
            'optionscode' => 'text',
            'value' => 'Register using your social network account'],

        'other_page' => [
            'title' => $lang->setting_oa_social_login_other_page_desc,
            'description' => $lang->setting_oa_social_login_other_page,
            'optionscode' => 'yesno',
            'value' => 0],

        'other_page_caption' => [
            'title' => $lang->setting_oa_social_login_other_page_caption_desc,
            'description' => $lang->setting_oa_social_login_other_page_caption,
            'optionscode' => 'text',
            'value' => 'Connect with your social network account'],

        // Do you want to use Social Link?
        'link_display' => [
            'optionscode' => 'php
            <p>
                <legend>' . $lang->setting_oa_social_login_link_legend . '</legend>
            </p>' ],

        'link_verified_accounts' => [
            'title' => $lang->setting_oa_social_login_link_verified_accounts,
            'description' => $lang->setting_oa_social_login_link_verified_accounts_desc,
            'optionscode' => 'yesno',
            'value' => '1'],

        'link_user_profile' => [
            'title' => $lang->setting_oa_social_login_link_profile_title,
            'description' => $lang->setting_oa_social_login_link_profile_desc,
            'optionscode' => 'yesno',
            'value' => 1],

        'link_user_profile_caption' => [
            'title' => $lang->setting_oa_social_login_link_caption_title,
            'description' => $lang->setting_oa_social_login_link_caption_desc,
            'optionscode' => 'text',
            'value' => 'Link your social networks accounts to be able to use them to login.'],

        // Enable uploading avatars from social network ?
        'avatars_display' => [
            'optionscode' => 'php
            <p>
                <legend>' . $lang->setting_oa_social_login_avatars_display . '</legend>
            </p>' ],

        'avatar' => [
            'title' => $lang->setting_oa_social_login_avatar_desc,
            'description' => $lang->setting_oa_social_login_avatar,
            'optionscode' => 'yesno',
            'value' => '1'],

        // Redirection
        'redirection_display' => [
            'optionscode' => 'php
            <p>
                <legend>' . $lang->setting_oa_social_login_redirection_display . '</legend>
            </p>' ],

        'redirection' => [
            'title' => $lang->setting_oa_social_login_redirection_desc,
            'description' => $lang->setting_oa_social_login_redirection,
            'optionscode' => 'text',
            'value' => ''],

        // Choose the social networks to enable on your forum
        'social_network_display' => [
            'optionscode' => 'php
    <p>
       <legend>' . $lang->setting_oa_social_login_social_network_display . '</legend>
    </p>' ],
        'provider_amazon' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_amazon" title="Amazon">Amazon</span>',
            'title' => 'Amazon',
            'optionscode' => 'onoff'
        ],
        'provider_battlenet' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_battlenet" title="BattleNet">BattleNet</span>',
            'title' => 'BattleNet',
            'optionscode' => 'onoff'
        ],
        'provider_blogger' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_blogger" title="Blogger">Blogger</span>',
            'title' => 'Blogger',
            'optionscode' => 'onoff'
        ],
        'provider_discord' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_discord" title="Discord">Discord</span>',
            'title' => 'Discord',
            'optionscode' => 'onoff'
        ],
        'provider_disqus' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_disqus" title="Disqus">Disqus</span>',
            'title' => 'Disqus',
            'optionscode' => 'onoff'
        ],
        'provider_draugiem' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_draugiem" title="Draugiem">Draugiem</span>',
            'title' => 'Draugiem',
            'optionscode' => 'onoff'
        ],
        'provider_dribbble' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_dribbble" title="Dribbble">Dribbble</span>',
            'title' => 'Dribbble',
            'optionscode' => 'onoff'
        ],
        'provider_facebook' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_facebook" title="Facebook">Facebook</span>',
            'title' => 'Facebook',
            'optionscode' => 'onoff',
            'value' => '1'
        ],
        'provider_foursquare' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_foursquare" title="Foursquare">Foursquare</span>',
            'title' => 'Foursquare',
            'optionscode' => 'onoff'
        ],
        'provider_github' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_github" title="Github.com">Github.com</span>',
            'title' => 'Github.com',
            'optionscode' => 'onoff'
        ],
        'provider_google' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_google" title="Google">Google</span>',
            'title' => 'Google',
            'optionscode' => 'onoff'
        ],
        'provider_instagram' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_instagram" title="Instagram">Instagram</span>',
            'title' => 'Instagram',
            'optionscode' => 'onoff'
        ],
        'provider_line' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_line" title="Line">Line</span>',
            'title' => 'Line',
            'optionscode' => 'onoff'
        ],
        'provider_linkedin' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_linkedin" title="LinkedIn">LinkedIn</span>',
            'title' => 'LinkedIn',
            'optionscode' => 'onoff'
        ],
        'provider_livejournal' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_livejournal" title="LiveJournal">LiveJournal</span>',
            'title' => 'LiveJournal',
            'optionscode' => 'onoff'
        ],
        'provider_mailru' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_mailru" title="Mail.ru">Mail.ru</span>',
            'title' => 'Mail.ru',
            'optionscode' => 'onoff'
        ],
        'provider_meetup' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_meetup" title="Meetup">Meetup</span>',
            'title' => 'Meetup',
            'optionscode' => 'onoff'
        ],
        'provider_odnoklassniki' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_odnoklassniki" title="Odnoklassniki">Odnoklassniki</span>',
            'title' => 'Odnoklassniki',
            'optionscode' => 'onoff'
        ],
        'provider_openid' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_openid" title="OpenID">OpenID</span>',
            'title' => 'OpenID',
            'optionscode' => 'onoff'
        ],
        'provider_paypal' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_paypal" title="PayPal">PayPal</span>',
            'title' => 'PayPal',
            'optionscode' => 'onoff'
        ],
        'provider_pinterest' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_pinterest" title="Pinterest">Pinterest</span>',
            'title' => 'Pinterest',
            'optionscode' => 'onoff'
        ],
        'provider_pixelpin' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_pixelpin" title="PixelPin">PixelPin</span>',
            'title' => 'PixelPin',
            'optionscode' => 'onoff'
        ],
        'provider_reddit' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_reddit" title="Reddit">Reddit</span>',
            'title' => 'Reddit',
            'optionscode' => 'onoff'
        ],
        'provider_skyrock' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_skyrock" title="Skyrock.com">Skyrock.com</span>',
            'title' => 'Skyrock.com',
            'optionscode' => 'onoff'
        ],
        'provider_soundcloud' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_soundcloud" title="SoundCloud">SoundCloud</span>',
            'title' => 'SoundCloud',
            'optionscode' => 'onoff'
        ],
        'provider_stackexchange' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_stackexchange" title="StackExchange">StackExchange</span>',
            'title' => 'StackExchange',
            'optionscode' => 'onoff'
        ],
        'provider_steam' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_steam" title="Steam">Steam</span>',
            'title' => 'Steam',
            'optionscode' => 'onoff'
        ],
        'provider_tumblr' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_tumblr" title="Tumblr">Tumblr</span>',
            'title' => 'Tumblr',
            'optionscode' => 'onoff'
        ],
        'provider_twitch' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_twitch" title="Twitch.tv">Twitch.tv</span>',
            'title' => 'Twitch.tv',
            'optionscode' => 'onoff'
        ],
        'provider_twitter' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_twitter" title="Twitter">Twitter</span>',
            'title' => 'Twitter',
            'optionscode' => 'onoff',
            'value' => '1'
        ],
        'provider_vimeo' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_vimeo" title="Vimeo">Vimeo</span>',
            'title' => 'Vimeo',
            'optionscode' => 'onoff'
        ],
        'provider_vkontakte' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_vkontakte" title="VKontakte">VKontakte</span>',
            'title' => 'VKontakte',
            'optionscode' => 'onoff'
        ],
        'provider_weibo' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_weibo" title="Weibo">Weibo</span>',
            'title' => 'Weibo',
            'optionscode' => 'onoff'
        ],
        'provider_windowslive' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_windowslive" title="Windows Live">Windows Live</span>',
            'title' => 'Windows Live',
            'optionscode' => 'onoff'
        ],
        'provider_wordpress' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_wordpress" title="WordPress.com">WordPress.com</span>',
            'title' => 'WordPress.com',
            'optionscode' => 'onoff'
        ],
        'provider_xing' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_xing" title="Xing">Xing</span>',
            'title' => 'Xing',
            'optionscode' => 'onoff'
        ],
        'provider_yahoo' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_yahoo" title="Yahoo">Yahoo</span>',
            'title' => 'Yahoo',
            'optionscode' => 'onoff'
        ],
        'provider_youtube' => [
            'description' => '<span class="oa_social_login_provider oa_social_login_provider_youtube" title="YouTube">YouTube</span>',
            'title' => 'YouTube',
            'optionscode' => 'onoff'
        ]
    ];
}
