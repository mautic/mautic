<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * DeleteItem request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Deletes an item of given `itemId` from the catalog.
 * If there are any *purchases*, *ratings*, *bookmarks*, *cart additions* or *detail views* of the item present in the database, they will be deleted in cascade as well. Also, if the item is present in some *series*, it will be removed from all the *series* where present.
 * If an item becomes obsolete/no longer available, it is often meaningful to keep it in the catalog (along with all the interaction data, which are very useful), and only exclude the item from recommendations. In such a case, use [ReQL filter](https://docs.recombee.com/reql.html) instead of deleting the item completely.
 */
class DeleteItem extends Request {

    /**
     * @var string $item_id ID of the item to be deleted.
     */
    protected $item_id;

    /**
     * Construct the request
     * @param string $item_id ID of the item to be deleted.
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
        return Request::HTTP_DELETE;
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
