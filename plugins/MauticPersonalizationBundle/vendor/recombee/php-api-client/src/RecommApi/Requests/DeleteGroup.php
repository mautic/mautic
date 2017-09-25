<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * DeleteGroup request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Deletes the group of given `groupId` from the database.
 * Deleting a group will only delete assignment of items to it, not the items themselves!
 */
class DeleteGroup extends Request {

    /**
     * @var string $group_id ID of the group to be deleted.
     */
    protected $group_id;

    /**
     * Construct the request
     * @param string $group_id ID of the group to be deleted.
     */
    public function __construct($group_id) {
        $this->group_id = $group_id;
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
        return "/{databaseId}/groups/{$this->group_id}";
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
