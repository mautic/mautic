<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * AddItem request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Adds new item of given `itemId` to the items catalog.
 * All the item properties for the newly created items are set null.
 */
class AddItem extends Request {

    /**
     * @var string $item_id ID of the item to be created.
     */
    protected $item_id;

    /**
     * Construct the request
     * @param string $item_id ID of the item to be created.
     */
    public function __construct($item_id) {
        $this->item_id = $item_id;
        $this->timeout = 1000;
        $this->ensure_https = false;
    }

    /**
     * Get used HTTP method
     * @return static Used HTTP method
     */
    public function getMethod() {
        return Request::HTTP_PUT;
    }

    /**
     * Get URI to the endpoint
     * @return string URI to the endpoint
     */
    public function getPath() {
        return "/{databaseId}/items/{$this->item_id}";
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
