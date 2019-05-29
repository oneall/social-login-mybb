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

/**
 * Returns a list of disabled PHP functions.
 * @return array disabled function
 */
function oa_social_login_get_php_disabled_functions()
{
    $disabled_functions = trim(ini_get('disable_functions'));
    if (strlen($disabled_functions) == 0)
    {
        $disabled_functions = array();
    }
    else
    {
        $disabled_functions = explode(',', $disabled_functions);
        $disabled_functions = array_map('trim', $disabled_functions);
    }

    return $disabled_functions;
}

/**
 * Create a random email address
 * @return string email
 */
function oa_social_login_create_rand_email()
{
    return md5(uniqid(rand(10000, 99000))) . "@example.com";
}

/**
 * Check if the current connection is being made over https
 * @return boolean
 */
function oa_social_login_https_on()
{
    $request = $_SERVER;

    if (! empty ($request['SERVER_PORT']))
    {
        if ($request['SERVER_PORT'] == 443)
        {
            return true;
        }
    }

    if (! empty ($request['HTTP_X_FORWARDED_PROTO']))
    {
        if ($request['HTTP_X_FORWARDED_PROTO'] == 'https')
        {
            return true;
        }
    }

    if (! empty ($request['HTTPS']))
    {
        if (in_array(strtolower(trim($request['HTTPS'])), array('on', '1')))
        {
            return true;
        }
    }

    return false;
}

/**
 * Returns the current url
 * @return string current url
 */
function oa_social_login_get_current_url()
{
    // Extract parts
    $request_uri = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']);
    $request_protocol = (oa_social_login_https_on() ? 'https' : 'http');
    $request_host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']));

    // Port of this request
    $request_port = '';

    // We are using a proxy
    if (isset($_SERVER['HTTP_X_FORWARDED_PORT']))
    {
        // SERVER_PORT is usually wrong on proxies, don't use it!
        $request_port = intval($_SERVER['HTTP_X_FORWARDED_PORT']);
    }
    // Does not seem like a proxy
    elseif (isset($_SERVER['SERVER_PORT']))
    {
        $request_port = intval($_SERVER['SERVER_PORT']);
    }

    // Remove standard ports
    $request_port = (!in_array($request_port, array(80, 443)) ? $request_port : '');

    // Build url
    $current_url = $request_protocol . '://' . $request_host . (!empty($request_port) ? (':' . $request_port) : '') . $request_uri;

    // Done
    return $current_url;
}

/**
 * Replace unsafe characters.
 * @param  string  $username raw username
 * @param  boolean $strict   reduce to ascii
 * @return string            sanitized username
 */
function oa_social_login_sanitize_login($username, $strict = false)
{
    $raw_username = $username;
    $username = oa_social_login_strip_all_tags($username);

    $accents = array(
        'Š' => 'S',
        'š' => 's',
        'Ž' => 'Z',
        'ž' => 'z',
        'À' => 'A',
        'Á' => 'A',
        'Â' => 'A',
        'Ã' => 'A',
        'Ä' => 'A',
        'Å' => 'A',
        'Æ' => 'A',
        'Ç' => 'C',
        'È' => 'E',
        'É' => 'E',
        'Ê' => 'E',
        'Ë' => 'E',
        'Ì' => 'I',
        'Í' => 'I',
        'Î' => 'I',
        'Ï' => 'I',
        'Ñ' => 'N',
        'Ò' => 'O',
        'Ó' => 'O',
        'Ô' => 'O',
        'Õ' => 'O',
        'Ö' => 'O',
        'Ø' => 'O',
        'Ù' => 'U',
        'Ú' => 'U',
        'Û' => 'U',
        'Ü' => 'U',
        'Ý' => 'Y',
        'Þ' => 'B',
        'ß' => 'Ss',
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'æ' => 'a',
        'ç' => 'c',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ð' => 'o',
        'ñ' => 'n',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'ø' => 'o',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ý' => 'y',
        'þ' => 'b',
        'ÿ' => 'y');
    $username = strtr($username, $accents);

    // Kill octets
    $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
    $username = preg_replace('/&.+?;/', '', $username); // Kill entities

    // If strict, reduce to ASCII for max portability.
    if ($strict)
    {
        $username = preg_replace('|[^a-z0-9 _.\-@]|i', '', $username);
    }

    // Remove trailing spaces
    $username = trim($username);

    // Consolidate contiguous whitespace
    $username = preg_replace('|\s+|', ' ', $username);

    return $username;
}

/**
 * Strip all HTML tags including script and style
 * @param  string  $string        text
 * @param  boolean $remove_breaks remove line break
 * @return string                 edited string
 */
function oa_social_login_strip_all_tags($string, $remove_breaks = false)
{
    $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
    $string = strip_tags($string);

    if ($remove_breaks)
    {
        $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
    }

    return trim($string);
}
