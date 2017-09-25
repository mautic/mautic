<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * DeleteUser request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Deletes a user of given *userId* from the database.
 * If there are any purchases, ratings, bookmarks, cart additions or detail views made by the user present in the database, they will be deleted in cascade as well.
 */
class DeleteUser extends Request {

    /**
     * @var string $user_id ID of the user to be added.
     */
    protected $user_id;

    /**
     * Construct the request
     * @param string $user_id ID of the user to be added.
     */
    public function __construct($user_id) {
        $this->user_id = $user_id;
        $this->timeout = 1000;
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
        return "/{databaseId}/users/{$this->user_id}";
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
