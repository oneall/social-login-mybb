<?php

/**
 * Returns a list of disabled PHP functions.
 * @return array disabled function
 */
function get_php_disabled_functions()
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
 * Check if an email has a valid format
 * @param  string  $email email to check
 * @return boolean
 */
function is_email($email)
{
    return preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*$/i', $email);
}

/**
 * Create a random email
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

    if ($request['SERVER_PORT'] == 443)
    {
        return true;
    }

    if ($request['HTTP_X_FORWARDED_PROTO'] == 'https')
    {
        return true;
    }

    if (in_array(strtolower(trim($request['HTTPS'])), array(
        'on',
        '1'
    )))
    {
        return true;
    }

    return false;
}

/**
 * Returns the current url
 * @return string current url
 */
function oa_social_login_get_current_url()
{
    //Extract parts
    $request_uri = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']);
    $request_protocol = (oa_social_login_https_on() ? 'https' : 'http');
    $request_host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']));

    //Port of this request
    $request_port = '';

    //We are using a proxy
    if (isset($_SERVER['HTTP_X_FORWARDED_PORT']))
    {
        // SERVER_PORT is usually wrong on proxies, don't use it!
        $request_port = intval($_SERVER['HTTP_X_FORWARDED_PORT']);
    }
    //Does not seem like a proxy
    elseif (isset($_SERVER['SERVER_PORT']))
    {
        $request_port = intval($_SERVER['SERVER_PORT']);
    }

    // Remove standard ports
    $request_port = (!in_array($request_port, array(80, 443)) ? $request_port : '');

    //Build url
    $current_url = $request_protocol . '://' . $request_host . (!empty($request_port) ? (':' . $request_port) : '') . $request_uri;

    //Remove the oa_social_login_source argument
    if (strpos($current_url, 'oa_social_login_source') !== false)
    {
        //Break up url
        list($url_part, $query_part) = array_pad(explode('?', $current_url), 2, '');
        parse_str($query_part, $query_vars);

        //Remove oa_social_login_source argument
        if (is_array($query_vars) and isset($query_vars['oa_social_login_source']))
        {
            unset($query_vars['oa_social_login_source']);
        }

        //Build new url
        $current_url = $url_part . ((is_array($query_vars) and count($query_vars) > 0) ? ('?' . http_build_query($query_vars)) : '');
    }

    //Done

    return $current_url;
}

/**
 * Replace unsafe characters.
 * @param  string  $username raw username
 * @param  boolean $strict   reduce to ascii
 * @return string            sanitized username
 */
function sanitize_user($username, $strict = false)
{
    $raw_username = $username;
    $username = strip_all_tags($username);

    $accents = array('Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
        'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
        'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y');
    $username = strtr($username, $accents);

    // Kill octets
    $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
    $username = preg_replace('/&.+?;/', '', $username); // Kill entities

    // If strict, reduce to ASCII for max portability.
    if ($strict)
    {
        $username = preg_replace('|[^a-z0-9 _.\-@]|i', '', $username);
    }

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
function strip_all_tags($string, $remove_breaks = false)
{
    $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
    $string = strip_tags($string);

    if ($remove_breaks)
    {
        $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
    }

    return trim($string);
}
