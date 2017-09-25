<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * ListItemCartAdditions request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * List all the ever-made cart addition of a given item.
 */
class ListItemCartAdditions extends Request {

    /**
     * @var string $item_id ID of the item of which the cart addition are to be listed.
     */
    protected $item_id;

    /**
     * Construct the request
     * @param string $item_id ID of the item of which the cart addition are to be listed.
     */
    public function __construct($item_id) {
        $this->item_id = $item_id;
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
        return "/{databaseId}/items/{$this->item_id}/cartadditions/";
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
