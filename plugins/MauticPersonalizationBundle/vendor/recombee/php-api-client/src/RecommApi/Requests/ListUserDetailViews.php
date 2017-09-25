<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * ListUserDetailViews request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Lists all the detail views of different items ever made by a given user.
 */
class ListUserDetailViews extends Request {

    /**
     * @var string $user_id ID of the user whose detail views are to be listed.
     */
    protected $user_id;

    /**
     * Construct the request
     * @param string $user_id ID of the user whose detail views are to be listed.
     */
    public function __construct($user_id) {
        $this->user_id = $user_id;
        $this->timeout = 100000;
        $this->ensure_https = false;
    }

    /**
     * Get used HTTP method
     * @return static Used HTTP method
     */
    public function getMethod() {
        return Request::HTTP_GET;
    }

    /**
     * Get URI to the endpoint
     * @return string URI to the endpoint
     */
    public function getPath() {
        return "/{databaseId}/users/{$this->user_id}/detailviews/";
    }

    /**
     * Get query parameters
     * @return array Values of query parameters (name of parameter => value of the parameter)
     */
    public function getQueryParameters() {
        $params = array();
        return $params;
    }

    /**
     * Get body parameters
     * @return array Values of body parameters (name of parameter => value of the parameter)
     */
    public function getBodyParameters() {
        $p = array();
        return $p;
    }

}
?>
