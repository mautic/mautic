<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * RemoveFromGroup request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Removes an existing group item from the group.
 */
class RemoveFromGroup extends Request {

    /**
     * @var string $group_id ID of the group from which a group item is to be removed.
     */
    protected $group_id;
    /**
     * @var string $item_type Type of the item to be removed.
     */
    protected $item_type;
    /**
     * @var string $item_id ID of the item iff `itemType` is `item`. ID of the group iff `itemType` is `group`.
     */
    protected $item_id;

    /**
     * Construct the request
     * @param string $group_id ID of the group from which a group item is to be removed.
     * @param string $item_type Type of the item to be removed.
     * @param string $item_id ID of the item iff `itemType` is `item`. ID of the group iff `itemType` is `group`.
     */
    public function __construct($group_id, $item_type, $item_id) {
        $this->group_id = $group_id;
        $this->item_type = $item_type;
        $this->item_id = $item_id;
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
        return "/{databaseId}/groups/{$this->group_id}/items/";
    }

    /**
     * Get query parameters
     * @return array Values of query parameters (name of parameter => value of the parameter)
     */
    public function getQueryParameters() {
        $params = array();
        $params['itemType'] = $this->item_type;
        $params['itemId'] = $this->item_id;
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
