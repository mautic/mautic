<?php
/**
 * This is an HTTP client class for Cloud Files.  It uses PHP's cURL module
 * to handle the actual HTTP request/response.  This is NOT a generic HTTP
 * client class and is only used to abstract out the HTTP communication for
 * the PHP Cloud Files API.
 *
 * This module was designed to re-use existing HTTP(S) connections between
 * subsequent operations.  For example, performing multiple PUT operations
 * will re-use the same connection.
 *
 * This modules also provides support for streaming content into and out
 * of Cloud Files.  The majority (all?) of the PHP HTTP client modules expect
 * to read the server's response into a string variable.  This will not work
 * with large files without killing your server.  Methods like,
 * get_object_to_stream() and put_object() take an open filehandle
 * argument for streaming data out of or into Cloud Files.
 *
 * Requres PHP 5.x (for Exceptions and OO syntax)
 *
 * See COPYING for license information.
 *
 * @author Eric "EJ" Johnson <ej@racklabs.com>
 * @copyright Copyright (c) 2008, Rackspace US, Inc
 */
require_once 'cloudfiles_exceptions.php';

define('PHP_CF_VERSION', '1.7.7');
define('USER_AGENT', sprintf('PHP-CloudFiles/%s', PHP_CF_VERSION));
define('ACCOUNT_CONTAINER_COUNT', 'X-Account-Container-Count');
define('ACCOUNT_BYTES_USED', 'X-Account-Bytes-Used');
define('CONTAINER_OBJ_COUNT', 'X-Container-Object-Count');
define('CONTAINER_BYTES_USED', 'X-Container-Bytes-Used');
define('METADATA_HEADER', 'X-Object-Meta-');
define('CDN_URI', 'X-CDN-URI');
define('CDN_ENABLED', 'X-CDN-Enabled');
define('CDN_LOG_RETENTION', 'X-Log-Retention');
define('CDN_ACL_USER_AGENT', 'X-User-Agent-ACL');
define('CDN_ACL_REFERRER', 'X-Referrer-ACL');
define('CDN_TTL', 'X-TTL');
define('CDNM_URL', 'X-CDN-Management-Url');
define('STORAGE_URL', 'X-Storage-Url');
define('AUTH_TOKEN', 'X-Auth-Token');
define('AUTH_USER_HEADER', 'X-Auth-User');
define('AUTH_KEY_HEADER', 'X-Auth-Key');
define('AUTH_USER_HEADER_LEGACY', 'X-Storage-User');
define('AUTH_KEY_HEADER_LEGACY', 'X-Storage-Pass');
define('AUTH_TOKEN_LEGACY', 'X-Storage-Token');
define('CDN_EMAIL', 'X-Purge-Email');
/**
 * HTTP/cURL wrapper for Cloud Files.
 *
 * This class should not be used directly.  It's only purpose is to abstract
 * out the HTTP communication from the main API.
 */
class CF_Http
{
    private $error_str;
    private $dbug;
    private $cabundle_path;
    private $api_version;

    // Authentication instance variables

    private $storage_url;
    private $cdnm_url;
    private $auth_token;

    // Request/response variables

    private $response_status;
    private $response_reason;
    private $connections;

    // Variables used for content/header callbacks

    private $_user_read_progress_callback_func;
    private $_user_write_progress_callback_func;
    private $_write_callback_type;
    private $_text_list;
    private $_account_container_count;
    private $_account_bytes_used;
    private $_container_object_count;
    private $_container_bytes_used;
    private $_obj_etag;
    private $_obj_last_modified;
    private $_obj_content_type;
    private $_obj_content_length;
    private $_obj_metadata;
    private $_obj_write_resource;
    private $_obj_write_string;
    private $_cdn_enabled;
    private $_cdn_uri;
    private $_cdn_ttl;
    private $_cdn_log_retention;
    private $_cdn_acl_user_agent;
    private $_cdn_acl_referrer;

    public function __construct($api_version)
    {
        $this->dbug          = false;
        $this->cabundle_path = null;
        $this->api_version   = $api_version;
        $this->error_str     = null;

        $this->storage_url = null;
        $this->cdnm_url    = null;
        $this->auth_token  = null;

        $this->response_status = null;
        $this->response_reason = null;

        // Curl connections array - since there is no way to "re-set" the
        // connection paramaters for a cURL handle, we keep an array of
        // the unique use-cases and funnel all of those same type
        // requests through the appropriate curl connection.

        $this->connections = [
            'GET_CALL' => null, // GET objects/containers/lists
            'PUT_OBJ'  => null, // PUT object
            'HEAD'     => null, // HEAD requests
            'PUT_CONT' => null, // PUT container
            'DEL_POST' => null, // DELETE containers/objects, POST objects
        ];

        $this->_user_read_progress_callback_func  = null;
        $this->_user_write_progress_callback_func = null;
        $this->_write_callback_type               = null;
        $this->_text_list                         = [];
        $this->_return_list                       = null;
        $this->_account_container_count           = 0;
        $this->_account_bytes_used                = 0;
        $this->_container_object_count            = 0;
        $this->_container_bytes_used              = 0;
        $this->_obj_write_resource                = null;
        $this->_obj_write_string                  = '';
        $this->_obj_etag                          = null;
        $this->_obj_last_modified                 = null;
        $this->_obj_content_type                  = null;
        $this->_obj_content_length                = null;
        $this->_obj_metadata                      = [];
        $this->_cdn_enabled                       = null;
        $this->_cdn_uri                           = null;
        $this->_cdn_ttl                           = null;
        $this->_cdn_log_retention                 = null;
        $this->_cdn_acl_user_agent                = null;
        $this->_cdn_acl_referrer                  = null;

        // The OS list with a PHP without an updated CA File for CURL to
        // connect to SSL Websites. It is the first 3 letters of the PHP_OS
        // variable.
        $OS_CAFILE_NONUPDATED = [
            'win', 'dar',
        ];

        if (in_array((strtolower(substr(PHP_OS, 0, 3))), $OS_CAFILE_NONUPDATED)) {
            $this->ssl_use_cabundle();
        }
    }

    public function ssl_use_cabundle($path = null)
    {
        if ($path) {
            $this->cabundle_path = $path;
        } else {
            $this->cabundle_path = dirname(__FILE__).'/share/cacert.pem';
        }
        if (!file_exists($this->cabundle_path)) {
            throw new IOException('Could not use CA bundle: '
                .$this->cabundle_path);
        }

        return;
    }

    // Uses separate cURL connection to authenticate

    public function authenticate($user, $pass, $acct = null, $host = null)
    {
        $path = [];
        if (isset($acct)) {
            $headers = [
                sprintf('%s: %s', AUTH_USER_HEADER_LEGACY, $user),
                sprintf('%s: %s', AUTH_KEY_HEADER_LEGACY, $pass),
                ];
            $path[] = $host;
            $path[] = rawurlencode(sprintf('v%d', $this->api_version));
            $path[] = rawurlencode($acct);
        } else {
            $headers = [
                sprintf('%s: %s', AUTH_USER_HEADER, $user),
                sprintf('%s: %s', AUTH_KEY_HEADER, $pass),
                ];
            $path[] = $host;
        }
        $path[] = 'v1.0';
        $url    = implode('/', $path);

        $curl_ch = curl_init();
        if (!is_null($this->cabundle_path)) {
            curl_setopt($curl_ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl_ch, CURLOPT_CAINFO, $this->cabundle_path);
        }
        curl_setopt($curl_ch, CURLOPT_VERBOSE, $this->dbug);
        curl_setopt($curl_ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($curl_ch, CURLOPT_HEADER, 0);
        curl_setopt($curl_ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_ch, CURLOPT_USERAGENT, USER_AGENT);
        curl_setopt($curl_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_ch, CURLOPT_HEADERFUNCTION, [&$this, '_auth_hdr_cb']);
        curl_setopt($curl_ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl_ch, CURLOPT_URL, $url);
        curl_exec($curl_ch);
        curl_close($curl_ch);

        return [$this->response_status, $this->response_reason,
            $this->storage_url, $this->cdnm_url, $this->auth_token, ];
    }

    // (CDN) GET /v1/Account

    public function list_cdn_containers($enabled_only)
    {
        $conn_type = 'GET_CALL';
        $url_path  = $this->_make_path('CDN');

        $this->_write_callback_type = 'TEXT_LIST';
        if ($enabled_only) {
            $return_code = $this->_send_request($conn_type, $url_path.
            '/?enabled_only=true');
        } else {
            $return_code = $this->_send_request($conn_type, $url_path);
        }
        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';
            [0, $this->error_str, []];
        }
        if ($return_code == 401) {
            return [$return_code, 'Unauthorized', []];
        }
        if ($return_code == 404) {
            return [$return_code, 'Account not found.', []];
        }
        if ($return_code == 204) {
            return [$return_code, 'Account has no CDN enabled Containers.',
                [], ];
        }
        if ($return_code == 200) {
            $this->create_array();

            return [$return_code, $this->response_reason, $this->_text_list];
        }
        $this->error_str = 'Unexpected HTTP response: '.$this->response_reason;

        return [$return_code, $this->error_str, []];
    }

    // (CDN) DELETE /v1/Account/Container or /v1/Account/Container/Object

    public function purge_from_cdn($path, $email = null)
    {
        if (!$path) {
            throw new SyntaxException('Path not set');
        }
        $url_path = $this->_make_path('CDN', null, $path);
        if ($email) {
            $hdrs        = [CDN_EMAIL => $email];
            $return_code = $this->_send_request('DEL_POST', $url_path, $hdrs, 'DELETE');
        } else {
            $return_code = $this->_send_request('DEL_POST', $url_path, null, 'DELETE');
        }

        return $return_code;
    }

    // (CDN) POST /v1/Account/Container
    public function update_cdn_container($container_name, $ttl, $cdn_log_retention,
                                  $cdn_acl_user_agent, $cdn_acl_referrer)
    {
        if ($container_name == '') {
            throw new SyntaxException('Container name not set.');
        }

        if ($container_name != '0' and !isset($container_name)) {
            throw new SyntaxException('Container name not set.');
        }

        $url_path = $this->_make_path('CDN', $container_name);
        $hdrs     = [
            CDN_ENABLED        => 'True',
            CDN_TTL            => $ttl,
            CDN_LOG_RETENTION  => $cdn_log_retention ? 'True' : 'False',
            CDN_ACL_USER_AGENT => $cdn_acl_user_agent,
            CDN_ACL_REFERRER   => $cdn_acl_referrer,
            ];
        $return_code = $this->_send_request('DEL_POST', $url_path, $hdrs, 'POST');
        if ($return_code == 401) {
            $this->error_str = 'Unauthorized';

            return [$return_code, $this->error_str, null];
        }
        if ($return_code == 404) {
            $this->error_str = 'Container not found.';

            return [$return_code, $this->error_str, null];
        }
        if ($return_code != 202) {
            $this->error_str = 'Unexpected HTTP response: '.$this->response_reason;

            return [$return_code, $this->error_str, null];
        }

        return [$return_code, 'Accepted', $this->_cdn_uri];
    }

    // (CDN) PUT /v1/Account/Container

    public function add_cdn_container($container_name, $ttl = 86400)
    {
        if ($container_name == '') {
            throw new SyntaxException('Container name not set.');
        }

        if ($container_name != '0' and !isset($container_name)) {
            throw new SyntaxException('Container name not set.');
        }

        $url_path = $this->_make_path('CDN', $container_name);
        $hdrs     = [
            CDN_ENABLED => 'True',
            CDN_TTL     => $ttl,
            ];
        $return_code = $this->_send_request('PUT_CONT', $url_path, $hdrs);
        if ($return_code == 401) {
            $this->error_str = 'Unauthorized';

            return [$return_code, $this->response_reason, false];
        }
        if (!in_array($return_code, [201, 202])) {
            $this->error_str = 'Unexpected HTTP response: '.$this->response_reason;

            return [$return_code, $this->response_reason, false];
        }

        return [$return_code, $this->response_reason, $this->_cdn_uri];
    }

    // (CDN) POST /v1/Account/Container

    public function remove_cdn_container($container_name)
    {
        if ($container_name == '') {
            throw new SyntaxException('Container name not set.');
        }

        if ($container_name != '0' and !isset($container_name)) {
            throw new SyntaxException('Container name not set.');
        }

        $url_path    = $this->_make_path('CDN', $container_name);
        $hdrs        = [CDN_ENABLED => 'False'];
        $return_code = $this->_send_request('DEL_POST', $url_path, $hdrs, 'POST');
        if ($return_code == 401) {
            $this->error_str = 'Unauthorized';

            return [$return_code, $this->error_str];
        }
        if ($return_code == 404) {
            $this->error_str = 'Container not found.';

            return [$return_code, $this->error_str];
        }
        if ($return_code != 202) {
            $this->error_str = 'Unexpected HTTP response: '.$this->response_reason;

            return [$return_code, $this->error_str];
        }

        return [$return_code, 'Accepted'];
    }

    // (CDN) HEAD /v1/Account

    public function head_cdn_container($container_name)
    {
        if ($container_name == '') {
            throw new SyntaxException('Container name not set.');
        }

        if ($container_name != '0' and !isset($container_name)) {
            throw new SyntaxException('Container name not set.');
        }

        $conn_type   = 'HEAD';
        $url_path    = $this->_make_path('CDN', $container_name);
        $return_code = $this->_send_request($conn_type, $url_path, null, 'GET', true);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [0, $this->error_str, null, null, null, null, null, null];
        }
        if ($return_code == 401) {
            return [$return_code, 'Unauthorized', null, null, null, null, null, null];
        }
        if ($return_code == 404) {
            return [$return_code, 'Account not found.', null, null, null, null, null, null];
        }
        if ($return_code == 204) {
            return [$return_code, $this->response_reason,
                $this->_cdn_enabled, $this->_cdn_uri, $this->_cdn_ttl,
                $this->_cdn_log_retention,
                $this->_cdn_acl_user_agent,
                $this->_cdn_acl_referrer,
                ];
        }

        return [$return_code, $this->response_reason,
                     null, null, null,
                     $this->_cdn_log_retention,
                     $this->_cdn_acl_user_agent,
                     $this->_cdn_acl_referrer,
            ];
    }

    // GET /v1/Account

    public function list_containers($limit = 0, $marker = null)
    {
        $conn_type = 'GET_CALL';
        $url_path  = $this->_make_path();

        $limit  = intval($limit);
        $params = [];
        if ($limit > 0) {
            $params[] = "limit=$limit";
        }
        if ($marker) {
            $params[] = 'marker='.rawurlencode($marker);
        }
        if (!empty($params)) {
            $url_path .= '?'.implode('&', $params);
        }

        $this->_write_callback_type = 'TEXT_LIST';
        $return_code                = $this->_send_request($conn_type, $url_path);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [0, $this->error_str, []];
        }
        if ($return_code == 204) {
            return [$return_code, 'Account has no containers.', []];
        }
        if ($return_code == 404) {
            $this->error_str = 'Invalid account name for authentication token.';

            return [$return_code, $this->error_str, []];
        }
        if ($return_code == 200) {
            $this->create_array();

            return [$return_code, $this->response_reason, $this->_text_list];
        }
        $this->error_str = 'Unexpected HTTP response: '.$this->response_reason;

        return [$return_code, $this->error_str, []];
    }

    // GET /v1/Account?format=json

    public function list_containers_info($limit = 0, $marker = null)
    {
        $conn_type = 'GET_CALL';
        $url_path  = $this->_make_path().'?format=json';

        $limit  = intval($limit);
        $params = [];
        if ($limit > 0) {
            $params[] = "limit=$limit";
        }
        if ($marker) {
            $params[] = 'marker='.rawurlencode($marker);
        }
        if (!empty($params)) {
            $url_path .= '&'.implode('&', $params);
        }

        $this->_write_callback_type = 'OBJECT_STRING';
        $return_code                = $this->_send_request($conn_type, $url_path);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [0, $this->error_str, []];
        }
        if ($return_code == 204) {
            return [$return_code, 'Account has no containers.', []];
        }
        if ($return_code == 404) {
            $this->error_str = 'Invalid account name for authentication token.';

            return [$return_code, $this->error_str, []];
        }
        if ($return_code == 200) {
            $json_body = json_decode($this->_obj_write_string, true);

            return [$return_code, $this->response_reason, $json_body];
        }
        $this->error_str = 'Unexpected HTTP response: '.$this->response_reason;

        return [$return_code, $this->error_str, []];
    }

    // HEAD /v1/Account

    public function head_account()
    {
        $conn_type = 'HEAD';

        $url_path    = $this->_make_path();
        $return_code = $this->_send_request($conn_type, $url_path);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';
            [0, $this->error_str, 0, 0];
        }
        if ($return_code == 404) {
            return [$return_code, 'Account not found.', 0, 0];
        }
        if ($return_code == 204) {
            return [$return_code, $this->response_reason,
                $this->_account_container_count, $this->_account_bytes_used, ];
        }

        return [$return_code, $this->response_reason, 0, 0];
    }

    // PUT /v1/Account/Container

    public function create_container($container_name)
    {
        if ($container_name == '') {
            throw new SyntaxException('Container name not set.');
        }

        if ($container_name != '0' and !isset($container_name)) {
            throw new SyntaxException('Container name not set.');
        }

        $url_path    = $this->_make_path('STORAGE', $container_name);
        $return_code = $this->_send_request('PUT_CONT', $url_path);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return false;
        }

        return $return_code;
    }

    // DELETE /v1/Account/Container

    public function delete_container($container_name)
    {
        if ($container_name == '') {
            throw new SyntaxException('Container name not set.');
        }

        if ($container_name != '0' and !isset($container_name)) {
            throw new SyntaxException('Container name not set.');
        }

        $url_path    = $this->_make_path('STORAGE', $container_name);
        $return_code = $this->_send_request('DEL_POST', $url_path, [], 'DELETE');

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';
        }
        if ($return_code == 409) {
            $this->error_str = 'Container must be empty prior to removing it.';
        }
        if ($return_code == 404) {
            $this->error_str = 'Specified container did not exist to delete.';
        }
        if ($return_code != 204) {
            $this->error_str = "Unexpected HTTP return code: $return_code.";
        }

        return $return_code;
    }

    // GET /v1/Account/Container

    public function list_objects($cname, $limit = 0, $marker = null, $prefix = null, $path = null)
    {
        if (!$cname) {
            $this->error_str = 'Container name not set.';

            return [0, $this->error_str, []];
        }

        $url_path = $this->_make_path('STORAGE', $cname);

        $limit  = intval($limit);
        $params = [];
        if ($limit > 0) {
            $params[] = "limit=$limit";
        }
        if ($marker) {
            $params[] = 'marker='.rawurlencode($marker);
        }
        if ($prefix) {
            $params[] = 'prefix='.rawurlencode($prefix);
        }
        if ($path) {
            $params[] = 'path='.rawurlencode($path);
        }
        if (!empty($params)) {
            $url_path .= '?'.implode('&', $params);
        }

        $conn_type                  = 'GET_CALL';
        $this->_write_callback_type = 'TEXT_LIST';
        $return_code                = $this->_send_request($conn_type, $url_path);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [0, $this->error_str, []];
        }
        if ($return_code == 204) {
            $this->error_str = 'Container has no Objects.';

            return [$return_code, $this->error_str, []];
        }
        if ($return_code == 404) {
            $this->error_str = 'Container has no Objects.';

            return [$return_code, $this->error_str, []];
        }
        if ($return_code == 200) {
            $this->create_array();

            return [$return_code, $this->response_reason, $this->_text_list];
        }
        $this->error_str = "Unexpected HTTP response code: $return_code";

        return [0, $this->error_str, []];
    }

    // GET /v1/Account/Container?format=json

    public function get_objects($cname, $limit = 0, $marker = null, $prefix = null, $path = null)
    {
        if (!$cname) {
            $this->error_str = 'Container name not set.';

            return [0, $this->error_str, []];
        }

        $url_path = $this->_make_path('STORAGE', $cname);

        $limit    = intval($limit);
        $params   = [];
        $params[] = 'format=json';
        if ($limit > 0) {
            $params[] = "limit=$limit";
        }
        if ($marker) {
            $params[] = 'marker='.rawurlencode($marker);
        }
        if ($prefix) {
            $params[] = 'prefix='.rawurlencode($prefix);
        }
        if ($path) {
            $params[] = 'path='.rawurlencode($path);
        }
        if (!empty($params)) {
            $url_path .= '?'.implode('&', $params);
        }

        $conn_type                  = 'GET_CALL';
        $this->_write_callback_type = 'OBJECT_STRING';
        $return_code                = $this->_send_request($conn_type, $url_path);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [0, $this->error_str, []];
        }
        if ($return_code == 204) {
            $this->error_str = 'Container has no Objects.';

            return [$return_code, $this->error_str, []];
        }
        if ($return_code == 404) {
            $this->error_str = 'Container has no Objects.';

            return [$return_code, $this->error_str, []];
        }
        if ($return_code == 200) {
            $json_body = json_decode($this->_obj_write_string, true);

            return [$return_code, $this->response_reason, $json_body];
        }
        $this->error_str = "Unexpected HTTP response code: $return_code";

        return [0, $this->error_str, []];
    }

    // HEAD /v1/Account/Container

    public function head_container($container_name)
    {
        if ($container_name == '') {
            $this->error_str = 'Container name not set.';

            return false;
        }

        if ($container_name != '0' and !isset($container_name)) {
            $this->error_str = 'Container name not set.';

            return false;
        }

        $conn_type = 'HEAD';

        $url_path    = $this->_make_path('STORAGE', $container_name);
        $return_code = $this->_send_request($conn_type, $url_path);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';
            [0, $this->error_str, 0, 0];
        }
        if ($return_code == 404) {
            return [$return_code, 'Container not found.', 0, 0];
        }
        if ($return_code == 204 || $return_code == 200) {
            return [$return_code, $this->response_reason,
                $this->_container_object_count, $this->_container_bytes_used, ];
        }

        return [$return_code, $this->response_reason, 0, 0];
    }

    // GET /v1/Account/Container/Object

    public function get_object_to_string(&$obj, $hdrs = [])
    {
        if (!is_object($obj) || get_class($obj) != 'CF_Object') {
            throw new SyntaxException(
                'Method argument is not a valid CF_Object.');
        }

        $conn_type = 'GET_CALL';

        $url_path                   = $this->_make_path('STORAGE', $obj->container->name, $obj->name);
        $this->_write_callback_type = 'OBJECT_STRING';
        $return_code                = $this->_send_request($conn_type, $url_path, $hdrs);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [$return_code0, $this->error_str, null];
        }
        if ($return_code == 404) {
            $this->error_str = 'Object not found.';

            return [$return_code0, $this->error_str, null];
        }
        if (($return_code < 200) || ($return_code > 299
                && $return_code != 412 && $return_code != 304)) {
            $this->error_str = "Unexpected HTTP return code: $return_code";

            return [$return_code, $this->error_str, null];
        }

        return [$return_code, $this->response_reason, $this->_obj_write_string];
    }

    // GET /v1/Account/Container/Object

    public function get_object_to_stream(&$obj, &$resource = null, $hdrs = [])
    {
        if (!is_object($obj) || get_class($obj) != 'CF_Object') {
            throw new SyntaxException(
                'Method argument is not a valid CF_Object.');
        }
        if (!is_resource($resource)) {
            throw new SyntaxException(
                'Resource argument not a valid PHP resource.');
        }

        $conn_type = 'GET_CALL';

        $url_path                   = $this->_make_path('STORAGE', $obj->container->name, $obj->name);
        $this->_obj_write_resource  = $resource;
        $this->_write_callback_type = 'OBJECT_STREAM';
        $return_code                = $this->_send_request($conn_type, $url_path, $hdrs);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [$return_code, $this->error_str];
        }
        if ($return_code == 404) {
            $this->error_str = 'Object not found.';

            return [$return_code, $this->error_str];
        }
        if (($return_code < 200) || ($return_code > 299
                && $return_code != 412 && $return_code != 304)) {
            $this->error_str = "Unexpected HTTP return code: $return_code";

            return [$return_code, $this->error_str];
        }

        return [$return_code, $this->response_reason];
    }

    // PUT /v1/Account/Container/Object

    public function put_object(&$obj, &$fp)
    {
        if (!is_object($obj) || get_class($obj) != 'CF_Object') {
            throw new SyntaxException(
                'Method argument is not a valid CF_Object.');
        }
        if (!is_resource($fp)) {
            throw new SyntaxException(
                'File pointer argument is not a valid resource.');
        }

        $conn_type = 'PUT_OBJ';
        $url_path  = $this->_make_path('STORAGE', $obj->container->name, $obj->name);

        $hdrs = $this->_metadata_headers($obj);

        $etag = $obj->getETag();
        if (isset($etag)) {
            $hdrs[] = 'ETag: '.$etag;
        }
        if (!$obj->content_type) {
            $hdrs[] = 'Content-Type: application/octet-stream';
        } else {
            $hdrs[] = 'Content-Type: '.$obj->content_type;
        }

        $this->_init($conn_type);
        curl_setopt($this->connections[$conn_type],
                CURLOPT_INFILE, $fp);
        if (!$obj->content_length) {
            // We don''t know the Content-Length, so assumed "chunked" PUT

            curl_setopt($this->connections[$conn_type], CURLOPT_UPLOAD, true);
            $hdrs[] = 'Transfer-Encoding: chunked';
        } else {
            // We know the Content-Length, so use regular transfer

            curl_setopt($this->connections[$conn_type],
                    CURLOPT_INFILESIZE, $obj->content_length);
        }
        $return_code = $this->_send_request($conn_type, $url_path, $hdrs);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [0, $this->error_str, null];
        }
        if ($return_code == 412) {
            $this->error_str = 'Missing Content-Type header';

            return [$return_code, $this->error_str, null];
        }
        if ($return_code == 422) {
            $this->error_str = 'Derived and computed checksums do not match.';

            return [$return_code, $this->error_str, null];
        }
        if ($return_code != 201) {
            $this->error_str = "Unexpected HTTP return code: $return_code";

            return [$return_code, $this->error_str, null];
        }

        return [$return_code, $this->response_reason, $this->_obj_etag];
    }

    // POST /v1/Account/Container/Object

    public function update_object(&$obj)
    {
        if (!is_object($obj) || get_class($obj) != 'CF_Object') {
            throw new SyntaxException(
                'Method argument is not a valid CF_Object.');
        }

        if (!is_array($obj->metadata) || empty($obj->metadata)) {
            $this->error_str = 'Metadata array is empty.';

            return 0;
        }

        $url_path = $this->_make_path('STORAGE', $obj->container->name, $obj->name);

        $hdrs        = $this->_metadata_headers($obj);
        $return_code = $this->_send_request('DEL_POST', $url_path, $hdrs, 'POST');
        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return 0;
        }
        if ($return_code == 404) {
            $this->error_str = 'Account, Container, or Object not found.';
        }
        if ($return_code != 202) {
            $this->error_str = "Unexpected HTTP return code: $return_code";
        }

        return $return_code;
    }

    // HEAD /v1/Account/Container/Object

    public function head_object(&$obj)
    {
        if (!is_object($obj) || get_class($obj) != 'CF_Object') {
            throw new SyntaxException(
                'Method argument is not a valid CF_Object.');
        }

        $conn_type = 'HEAD';

        $url_path    = $this->_make_path('STORAGE', $obj->container->name, $obj->name);
        $return_code = $this->_send_request($conn_type, $url_path);

        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return [0, $this->error_str.' '.$this->response_reason,
                null, null, null, null, [], ];
        }

        if ($return_code == 404) {
            return [$return_code, $this->response_reason,
                null, null, null, null, [], ];
        }
        if ($return_code == 204 || $return_code == 200) {
            return [$return_code, $this->response_reason,
                $this->_obj_etag,
                $this->_obj_last_modified,
                $this->_obj_content_type,
                $this->_obj_content_length,
                $this->_obj_metadata, ];
        }
        $this->error_str = "Unexpected HTTP return code: $return_code";

        return [$return_code, $this->error_str.' '.$this->response_reason,
                null, null, null, null, [], ];
    }

    // DELETE /v1/Account/Container/Object

    public function delete_object($container_name, $object_name)
    {
        if ($container_name == '') {
            $this->error_str = 'Container name not set.';

            return 0;
        }

        if ($container_name != '0' and !isset($container_name)) {
            $this->error_str = 'Container name not set.';

            return 0;
        }

        if (!$object_name) {
            $this->error_str = 'Object name not set.';

            return 0;
        }

        $url_path    = $this->_make_path('STORAGE', $container_name, $object_name);
        $return_code = $this->_send_request('DEL_POST', $url_path, null, 'DELETE');
        if (!$return_code) {
            $this->error_str .= ': Failed to obtain valid HTTP response.';

            return 0;
        }
        if ($return_code == 404) {
            $this->error_str = 'Specified container did not exist to delete.';
        }
        if ($return_code != 204) {
            $this->error_str = "Unexpected HTTP return code: $return_code.";
        }

        return $return_code;
    }

    public function get_error()
    {
        return $this->error_str;
    }

    public function setDebug($bool)
    {
        $this->dbug = $bool;
        foreach ($this->connections as $k => $v) {
            if (!is_null($v)) {
                curl_setopt($this->connections[$k], CURLOPT_VERBOSE, $this->dbug);
            }
        }
    }

    public function getCDNMUrl()
    {
        return $this->cdnm_url;
    }

    public function getStorageUrl()
    {
        return $this->storage_url;
    }

    public function getAuthToken()
    {
        return $this->auth_token;
    }

    public function setCFAuth($cfs_auth, $servicenet = false)
    {
        if ($servicenet) {
            $this->storage_url = 'https://snet-'.substr($cfs_auth->storage_url, 8);
        } else {
            $this->storage_url = $cfs_auth->storage_url;
        }
        $this->auth_token = $cfs_auth->auth_token;
        $this->cdnm_url   = $cfs_auth->cdnm_url;
    }

    public function setReadProgressFunc($func_name)
    {
        $this->_user_read_progress_callback_func = $func_name;
    }

    public function setWriteProgressFunc($func_name)
    {
        $this->_user_write_progress_callback_func = $func_name;
    }

    private function _header_cb($ch, $header)
    {
        preg_match("/^HTTP\/1\.[01] (\d{3}) (.*)/", $header, $matches);
        if (isset($matches[1])) {
            $this->response_status = $matches[1];
        }
        if (isset($matches[2])) {
            $this->response_reason = $matches[2];
        }
        if (stripos($header, CDN_ENABLED) === 0) {
            $val = trim(substr($header, strlen(CDN_ENABLED) + 1));
            if (strtolower($val) == 'true') {
                $this->_cdn_enabled = true;
            } elseif (strtolower($val) == 'false') {
                $this->_cdn_enabled = false;
            } else {
                $this->_cdn_enabled = null;
            }

            return strlen($header);
        }
        if (stripos($header, CDN_URI) === 0) {
            $this->_cdn_uri = trim(substr($header, strlen(CDN_URI) + 1));

            return strlen($header);
        }
        if (stripos($header, CDN_TTL) === 0) {
            $this->_cdn_ttl = trim(substr($header, strlen(CDN_TTL) + 1)) + 0;

            return strlen($header);
        }
        if (stripos($header, CDN_LOG_RETENTION) === 0) {
            $this->_cdn_log_retention =
                trim(substr($header, strlen(CDN_LOG_RETENTION) + 1)) == 'True' ? true : false;

            return strlen($header);
        }

        if (stripos($header, CDN_ACL_USER_AGENT) === 0) {
            $this->_cdn_acl_user_agent =
                trim(substr($header, strlen(CDN_ACL_USER_AGENT) + 1));

            return strlen($header);
        }

        if (stripos($header, CDN_ACL_REFERRER) === 0) {
            $this->_cdn_acl_referrer =
                trim(substr($header, strlen(CDN_ACL_REFERRER) + 1));

            return strlen($header);
        }

        if (stripos($header, ACCOUNT_CONTAINER_COUNT) === 0) {
            $this->_account_container_count = (float) trim(substr($header,
                    strlen(ACCOUNT_CONTAINER_COUNT) + 1)) + 0;

            return strlen($header);
        }
        if (stripos($header, ACCOUNT_BYTES_USED) === 0) {
            $this->_account_bytes_used = (float) trim(substr($header,
                    strlen(ACCOUNT_BYTES_USED) + 1)) + 0;

            return strlen($header);
        }
        if (stripos($header, CONTAINER_OBJ_COUNT) === 0) {
            $this->_container_object_count = (float) trim(substr($header,
                    strlen(CONTAINER_OBJ_COUNT) + 1)) + 0;

            return strlen($header);
        }
        if (stripos($header, CONTAINER_BYTES_USED) === 0) {
            $this->_container_bytes_used = (float) trim(substr($header,
                    strlen(CONTAINER_BYTES_USED) + 1)) + 0;

            return strlen($header);
        }
        if (stripos($header, METADATA_HEADER) === 0) {
            // $header => X-Object-Meta-Foo: bar baz
            $temp = substr($header, strlen(METADATA_HEADER));
            // $temp => Foo: bar baz
            $parts = explode(':', $temp);
            // $parts[0] => Foo
            $val = substr(strstr($temp, ':'), 1);
            // $val => bar baz
            $this->_obj_metadata[$parts[0]] = trim($val);

            return strlen($header);
        }
        if (stripos($header, 'ETag:') === 0) {
            // $header => ETag: abc123def456...
            $val = substr(strstr($header, ':'), 1);
            // $val => abc123def456...
            $this->_obj_etag = trim($val);

            return strlen($header);
        }
        if (stripos($header, 'Last-Modified:') === 0) {
            $val                      = substr(strstr($header, ':'), 1);
            $this->_obj_last_modified = trim($val);

            return strlen($header);
        }
        if (stripos($header, 'Content-Type:') === 0) {
            $val                     = substr(strstr($header, ':'), 1);
            $this->_obj_content_type = trim($val);

            return strlen($header);
        }
        if (stripos($header, 'Content-Length:') === 0) {
            $val                       = substr(strstr($header, ':'), 1);
            $this->_obj_content_length = (float) trim($val) + 0;

            return strlen($header);
        }

        return strlen($header);
    }

    private function _read_cb($ch, $fd, $length)
    {
        $data = fread($fd, $length);
        $len  = strlen($data);
        if (isset($this->_user_write_progress_callback_func)) {
            call_user_func($this->_user_write_progress_callback_func, $len);
        }

        return $data;
    }

    private function _write_cb($ch, $data)
    {
        $dlen = strlen($data);
        switch ($this->_write_callback_type) {
        case 'TEXT_LIST':
         $this->_return_list = $this->_return_list.$data;
         //= explode("\n",$data); # keep tab,space
         //his->_text_list[] = rtrim($data,"\n\r\x0B"); # keep tab,space
            break;
        case 'OBJECT_STREAM':
            fwrite($this->_obj_write_resource, $data, $dlen);
            break;
        case 'OBJECT_STRING':
            $this->_obj_write_string .= $data;
            break;
        }
        if (isset($this->_user_read_progress_callback_func)) {
            call_user_func($this->_user_read_progress_callback_func, $dlen);
        }

        return $dlen;
    }

    private function _auth_hdr_cb($ch, $header)
    {
        preg_match("/^HTTP\/1\.[01] (\d{3}) (.*)/", $header, $matches);
        if (isset($matches[1])) {
            $this->response_status = $matches[1];
        }
        if (isset($matches[2])) {
            $this->response_reason = $matches[2];
        }
        if (stripos($header, STORAGE_URL) === 0) {
            $this->storage_url = trim(substr($header, strlen(STORAGE_URL) + 1));
        }
        if (stripos($header, CDNM_URL) === 0) {
            $this->cdnm_url = trim(substr($header, strlen(CDNM_URL) + 1));
        }
        if (stripos($header, AUTH_TOKEN) === 0) {
            $this->auth_token = trim(substr($header, strlen(AUTH_TOKEN) + 1));
        }
        if (stripos($header, AUTH_TOKEN_LEGACY) === 0) {
            $this->auth_token = trim(substr($header, strlen(AUTH_TOKEN_LEGACY) + 1));
        }

        return strlen($header);
    }

    private function _make_headers($hdrs = null)
    {
        $new_headers = [];
        $has_stoken  = false;
        $has_uagent  = false;
        if (is_array($hdrs)) {
            foreach ($hdrs as $h => $v) {
                if (is_int($h)) {
                    $parts  = explode(':', $v);
                    $header = $parts[0];
                    $value  = trim(substr(strstr($v, ':'), 1));
                } else {
                    $header = $h;
                    $value  = trim($v);
                }

                if (stripos($header, AUTH_TOKEN) === 0) {
                    $has_stoken = true;
                }
                if (stripos($header, 'user-agent') === 0) {
                    $has_uagent = true;
                }
                $new_headers[] = $header.': '.$value;
            }
        }
        if (!$has_stoken) {
            $new_headers[] = AUTH_TOKEN.': '.$this->auth_token;
        }
        if (!$has_uagent) {
            $new_headers[] = 'User-Agent: '.USER_AGENT;
        }

        return $new_headers;
    }

    private function _init($conn_type, $force_new = false)
    {
        if (!array_key_exists($conn_type, $this->connections)) {
            $this->error_str = 'Invalid CURL_XXX connection type';

            return false;
        }

        if (is_null($this->connections[$conn_type]) || $force_new) {
            $ch = curl_init();
        } else {
            return;
        }

        if ($this->dbug) {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
        }

        if (!is_null($this->cabundle_path)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $this->cabundle_path);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [&$this, '_header_cb']);

        if ($conn_type == 'GET_CALL') {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, [&$this, '_write_cb']);
        }

        if ($conn_type == 'PUT_OBJ') {
            curl_setopt($ch, CURLOPT_PUT, 1);
            curl_setopt($ch, CURLOPT_READFUNCTION, [&$this, '_read_cb']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        }
        if ($conn_type == 'HEAD') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        if ($conn_type == 'PUT_CONT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_INFILESIZE, 0);
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        if ($conn_type == 'DEL_POST') {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        $this->connections[$conn_type] = $ch;

        return;
    }

    private function _reset_callback_vars()
    {
        $this->_text_list               = [];
        $this->_return_list             = null;
        $this->_account_container_count = 0;
        $this->_account_bytes_used      = 0;
        $this->_container_object_count  = 0;
        $this->_container_bytes_used    = 0;
        $this->_obj_etag                = null;
        $this->_obj_last_modified       = null;
        $this->_obj_content_type        = null;
        $this->_obj_content_length      = null;
        $this->_obj_metadata            = [];
        $this->_obj_write_string        = '';
        $this->_cdn_enabled             = null;
        $this->_cdn_uri                 = null;
        $this->_cdn_ttl                 = null;
        $this->response_status          = 0;
        $this->response_reason          = '';
    }

    private function _make_path($t = 'STORAGE', $c = null, $o = null)
    {
        $path = [];
        switch ($t) {
        case 'STORAGE':
            $path[] = $this->storage_url; break;
        case 'CDN':
            $path[] = $this->cdnm_url; break;
        }
        if ($c == '0') {
            $path[] = rawurlencode($c);
        }

        if ($c) {
            $path[] = rawurlencode($c);
        }
        if ($o) {
            // mimic Python''s urllib.quote() feature of a "safe" '/' character

            $path[] = str_replace('%2F', '/', rawurlencode($o));
        }

        return implode('/', $path);
    }

    private function _metadata_headers(&$obj)
    {
        $hdrs = [];
        foreach ($obj->metadata as $k => $v) {
            if (strpos($k, ':') !== false) {
                throw new SyntaxException(
                    "Metadata keys cannot contain a ':' character.");
            }
            $k   = trim($k);
            $key = sprintf('%s%s', METADATA_HEADER, $k);
            if (!array_key_exists($key, $hdrs)) {
                if (strlen($k) > 128 || strlen($v) > 256) {
                    $this->error_str = 'Metadata key or value exceeds ';
                    $this->error_str .= "maximum length: ($k: $v)";

                    return 0;
                }
                $hdrs[] = sprintf('%s%s: %s', METADATA_HEADER, $k, trim($v));
            }
        }

        return $hdrs;
    }

    private function _send_request($conn_type, $url_path, $hdrs = null, $method = 'GET', $force_new = false)
    {
        $this->_init($conn_type, $force_new);
        $this->_reset_callback_vars();
        $headers = $this->_make_headers($hdrs);

        if (gettype($this->connections[$conn_type]) == 'unknown type') {
            throw new ConnectionNotOpenException(
                'Connection is not open.'
                );
        }

        switch ($method) {
        case 'DELETE':
            curl_setopt($this->connections[$conn_type],
                CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        case 'POST':
            curl_setopt($this->connections[$conn_type],
                CURLOPT_CUSTOMREQUEST, 'POST');
        default:
            break;
        }

        curl_setopt($this->connections[$conn_type],
                    CURLOPT_HTTPHEADER, $headers);

        curl_setopt($this->connections[$conn_type],
            CURLOPT_URL, $url_path);

        if (!curl_exec($this->connections[$conn_type]) && curl_errno($this->connections[$conn_type]) !== 0) {
            $this->error_str = '(curl error: '
                .curl_errno($this->connections[$conn_type]).') ';
            $this->error_str .= curl_error($this->connections[$conn_type]);

            return false;
        }

        return curl_getinfo($this->connections[$conn_type], CURLINFO_HTTP_CODE);
    }

    public function close()
    {
        foreach ($this->connections as $cnx) {
            if (isset($cnx)) {
                curl_close($cnx);
                $this->connections[$cnx] = null;
            }
        }
    }

    private function create_array()
    {
        $this->_text_list = explode("\n", rtrim($this->_return_list, "\n\x0B"));

        return true;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
