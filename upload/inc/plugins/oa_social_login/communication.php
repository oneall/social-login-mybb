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
define('OA_SOCIAL_LOGIN_USER_AGENT', 'SocialLogin/2.7.2 myBB/1.8 (+http://www.oneall.com/)');

/**
 * Sends an API request by using the given handler.
 * @param  string  $handler curl or fsockopen
 * @param  string  $url     endpoint
 * @param  array   $options additionnal option
 * @param  array   $data    post data
 * @param  integer $timeout timeout duration
 * @return void
 */
function oa_social_login_do_api_request($handler, $url, $options = array(), $data = array(), $timeout = 15)
{
    // FSOCKOPEN
    if ($handler == 'fsockopen')
    {
        return oa_social_login_fsockopen_request($url, $options, $data, $timeout);
    }
    // CURL
    else
    {
        return oa_social_login_curl_request($url, $options, $data, $timeout);
    }
}

// ********************************************************
// CURL
// ********************************************************

/**
 *
 * Check if cURL has been loaded and is enabled.
 * @param  boolean $secure use https or http
 * @return boolean         curl is setup
 */
function oa_social_login_check_curl_available()
{
    //Make sure cURL has been loaded
    if (in_array('curl', get_loaded_extensions()) && function_exists('curl_init') && function_exists('curl_exec'))
    {
        $disabled_functions = oa_social_login_get_php_disabled_functions();

        //Make sure cURL not been disabled
        if (!in_array('curl_init', $disabled_functions) and !in_array('curl_exec', $disabled_functions))
        {
            //Loaded and enabled
            return true;
        }
    }

    // Either not loaded or been disabled

    return false;
}

/**
 *
 * Checks if CURL can be used.
 * @param  boolean $secure use https or http
 * @return boolean         curl is setup
 */
function oa_social_login_check_curl($secure = true)
{
    if (in_array('curl', get_loaded_extensions()) && function_exists('curl_exec') && !in_array('curl_exec', oa_social_login_get_php_disabled_functions()))
    {
        $result = oa_social_login_curl_request(($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
        if (is_object($result) && property_exists($result, 'http_code') && $result->http_code == 200)
        {
            if (property_exists($result, 'http_data'))
            {
                if (strtolower($result->http_data) == 'ok')
                {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Sends a CURL request.
 * @param  string  $url           endpoint
 * @param  array   $options       additionnal option
 * @param  array   $data          post data
 * @param  integer $timeout       timeout duration
 * @param  integer $num_redirects
 * @return object                 request response
 */
function oa_social_login_curl_request($url, $options = array(), $data = array(), $timeout = 30, $num_redirects = 0)
{
    // Store the result
    $result = new \stdClass();

    // Send request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_REFERER, $url);
    curl_setopt($curl, CURLOPT_VERBOSE, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, OA_SOCIAL_LOGIN_USER_AGENT);

    // Does not work in PHP Safe Mode, we manually follow the locations if necessary.
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);

    // BASIC AUTH?
    if (isset($options['api_key']) && isset($options['api_secret']))
    {
        curl_setopt($curl, CURLOPT_USERPWD, $options['api_key'] . ':' . $options['api_secret']);
    }

    // Which type of request : GET/PUT/POST/DELETE/....
    if (!empty($options['custom_request']))
    {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $options['custom_request']);

        // Data
        if (!empty($data))
        {
            if (is_array($data))
            {
                $post_values = array();
                foreach ($data as $key => $value)
                {
                    $post_values[] = $key . '=' . urlencode($value);
                }
                $post_value = implode("&", $post_values);
            }
            else
            {
                $post_value = trim($data);
            }

            // Setup POST Data
            if (!empty($post_value))
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post_value);
            }
            else
            {
                curl_setopt($curl, CURLOPT_HTTPHEADER, 'Content-length: 0');
            }
        }
        else
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, 'Content-length: 0');
        }
    }

    // Proxy Settings
    if (!empty($options['proxy_url']))
    {
        // Proxy Location
        curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($curl, CURLOPT_PROXY, $options['proxy_url']);

        // Proxy Port
        if (!empty($options['proxy_port']))
        {
            curl_setopt($curl, CURLOPT_PROXYPORT, $options['proxy_port']);
        }

        // Proxy Authentication
        if (!empty($options['proxy_username']) && !empty($options['proxy_password']))
        {
            curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $options['proxy_username'] . ':' . $options['proxy_password']);
        }
    }

    // Make request
    if (($response = curl_exec($curl)) !== false)
    {
        // Get Information
        $curl_info = curl_getinfo($curl);

        // Save result
        $result->http_code = $curl_info['http_code'];
        $result->http_headers = preg_split('/\r\n|\n|\r/', trim(substr($response, 0, $curl_info['header_size'])));
        $result->http_data = trim(substr($response, $curl_info['header_size']));
        $result->http_error = null;

        // Check if we have a redirection header
        if (in_array($result->http_code, array(301, 302)) && $num_redirects < 4)
        {
            // Make sure we have http headers
            if (is_array($result->http_headers))
            {
                // Header found ?
                $header_found = false;

                // Loop through headers.
                while (!$header_found && (list(, $header) = each($result->http_headers)))
                {
                    // Try to parse a redirection header.
                    if (preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches))
                    {
                        // Sanitize redirection url.
                        $url_tmp = trim(str_replace($matches[1], "", $matches[0]));
                        $url_parsed = parse_url($url_tmp);
                        if (!empty($url_parsed))
                        {
                            // Header found!
                            $header_found = true;

                            // Follow redirection url.
                            $result = oa_social_login_curl_request($url_tmp, $options, $data, $timeout, $num_redirects + 1);
                        }
                    }
                }
            }
        }
    }
    else
    {
        $result->http_code = -1;
        $result->http_data = null;
        $result->http_error = curl_error($curl);
    }

    // Done

    return $result;
}

// ********************************************************
// FSockopen
// ********************************************************

/**
 * Check if fsockopen is available.
 * @return boolean is fsockopen available
 */
function check_fsockopen_available()
{
    //Make sure fsockopen has been loaded
    if (function_exists('fsockopen') and function_exists('fwrite'))
    {
        $disabled_functions = oa_social_login_get_php_disabled_functions();

        //Make sure fsockopen has not been disabled
        if (!in_array('fsockopen', $disabled_functions) and !in_array('fwrite', $disabled_functions))
        {
            //Loaded and enabled
            return true;
        }
    }

    //Not loaded or disabled

    return false;
}

/**
 * Checks if fsockopen can be used.
 * @param  boolean $secure https or http
 * @return boolean
 */
function check_fsockopen($secure = true)
{
    if (function_exists('fsockopen') && !in_array('fsockopen', oa_social_login_get_php_disabled_functions()))
    {
        $result = oa_social_login_fsockopen_request(($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
        if (is_object($result) && property_exists($result, 'http_code') && $result->http_code == 200)
        {
            if (property_exists($result, 'http_data'))
            {
                if (strtolower($result->http_data) == 'ok')
                {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Sends an fsockopen request.
 * @param  string  $url           endpoint
 * @param  array   $options       additionnal option
 * @param  array   $data          post data
 * @param  integer $timeout       timeout duration
 * @param  integer $num_redirects
 * @return object                 request response
 */
function oa_social_login_fsockopen_request($url, $options = array(), $data = array(), $timeout = 30, $num_redirects = 0)
{
    // Store the result
    $result = new \stdClass();

    // Make that this is a valid URL
    if (($uri = parse_url($url)) == false)
    {
        $result->http_code = -1;
        $result->http_data = null;
        $result->http_error = 'invalid_uri';

        return $result;
    }

    // Make sure we can handle the schema
    switch ($uri['scheme'])
    {
        case 'http':
            $port = (isset($uri['port']) ? $uri['port'] : 80);
            $host = ($uri['host'] . ($port != 80 ? ':' . $port : ''));
            $fp = @fsockopen($uri['host'], $port, $errno, $errstr, $timeout);
            break;

        case 'https':
            $port = (isset($uri['port']) ? $uri['port'] : 443);
            $host = ($uri['host'] . ($port != 443 ? ':' . $port : ''));
            $fp = @fsockopen('ssl://' . $uri['host'], $port, $errno, $errstr, $timeout);
            break;

        default:
            $result->http_code = -1;
            $result->http_data = null;
            $result->http_error = 'invalid_schema';

            return $result;
            break;
    }

    // Make sure the socket opened properly
    if (!$fp)
    {
        $result->http_code = -$errno;
        $result->http_data = null;
        $result->http_error = trim($errstr);

        return $result;
    }

    // Construct the path to act on
    $path = (isset($uri['path']) ? $uri['path'] : '/');
    if (isset($uri['query']))
    {
        $path .= '?' . $uri['query'];
    }

    // Create HTTP request
    $defaults = array();
    $defaults['Host'] = 'Host: ' . $host;
    $defaults['User-Agent'] = 'User-Agent: ' . OA_SOCIAL_LOGIN_USER_AGENT;

    // BASIC AUTH?
    if (isset($options['api_key']) && isset($options['api_secret']))
    {
        $defaults['Authorization'] = 'Authorization: Basic ' . base64_encode($options['api_key'] . ":" . $options['api_secret']);
    }

    // Which type of request : GET/PUT/POST/DELETE/....
    if (!empty($options['custom_request']) && !empty($data))
    {
        if (is_array($data))
        {
            $post_values = array();
            foreach ($data as $key => $value)
            {
                $post_values[] = $key . '=' . urlencode($value);
            }
            $post_value = implode("&", $post_values);
        }
        else
        {
            $post_value = trim($data);
        }

        // Setup POST/PUT Data
        $request = $options['custom_request'] . " " . $path . " HTTP/1.1\r\n";
        $request .= implode("\r\n", $defaults);
        $request .= "Content-Type: application/json\r\n";
        $request .= "Content-Length: " . strlen($data) . "\r\n";
        $request .= "Connection: Close\r\n\r\n";
        $request .= $post_value;
    }
    else
    {
        // Build and send request
        $request = 'GET ' . $path . " HTTP/1.0\r\n";
        $request .= implode("\r\n", $defaults);
        $request .= "\r\n\r\n";
    }

    fwrite($fp, $request);

    // Fetch response
    $response = '';
    while (!feof($fp))
    {
        $response .= fread($fp, 1024);
    }

    // Close connection
    fclose($fp);

    // Parse response
    list($response_header, $response_body) = explode("\r\n\r\n", $response, 2);

    // Parse header
    $response_header = preg_split("/\r\n|\n|\r/", $response_header);
    list($header_protocol, $header_code, $header_status_message) = explode(' ', trim(array_shift($response_header)), 3);

    // Set result
    $result->http_code = $header_code;
    $result->http_headers = $response_header;
    $result->http_data = $response_body;

    // Make sure we we have a redirection status code
    if (in_array($result->http_code, array(301, 302)) && $num_redirects <= 4)
    {
        // Make sure we have http headers
        if (is_array($result->http_headers))
        {
            // Header found?
            $header_found = false;

            // Loop through headers.
            while (!$header_found && (list(, $header) = each($result->http_headers)))
            {
                // Check for location header
                if (preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches))
                {
                    // Found
                    $header_found = true;

                    // Clean url
                    $url_tmp = trim(str_replace($matches[1], "", $matches[0]));
                    $url_parsed = parse_url($url_tmp);

                    // Found
                    if (!empty($url_parsed))
                    {
                        $result = oa_social_login_fsockopen_request($url_tmp, $options, $data, $timeout, $num_redirects + 1);
                    }
                }
            }
        }
    }

    // Done

    return $result;
}
