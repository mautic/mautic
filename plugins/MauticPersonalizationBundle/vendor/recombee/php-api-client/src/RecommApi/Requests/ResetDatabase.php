<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * ResetDatabase request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Completely erases all your data, including items, item properties, series, user database, purchases, ratings, detail views, and bookmarks. Make sure the request to be never executed in production environment! Resetting your database is irreversible.
 */
class ResetDatabase extends Request {


    /**
     * Construct the request
     */
    public function __construct() {
        $this->timeout = 239000;
        $this->ensure_https = false;
    }

    /**
     * Get used HTTP method
     * @return static Used HTTP method
     */
    public function getMethod() {
        return Request::HTTP_DELETE;
    }

    /**
     * Get URI to the endpoint
     * @return string URI to the endpoint
     */
    public function getPath() {
        return "/{databaseId}/";
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
