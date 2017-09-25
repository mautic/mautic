<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * RemoveFromSeries request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Removes an existing series item from the series.
 */
class RemoveFromSeries extends Request {

    /**
     * @var string $series_id ID of the series from which a series item is to be removed.
     */
    protected $series_id;
    /**
     * @var string $item_type Type of the item to be removed.
     */
    protected $item_type;
    /**
     * @var string $item_id ID of the item iff `itemType` is `item`. ID of the series iff `itemType` is `series`.
     */
    protected $item_id;
    /**
     * @var float $time Time index of the item to be removed.
     */
    protected $time;

    /**
     * Construct the request
     * @param string $series_id ID of the series from which a series item is to be removed.
     * @param string $item_type Type of the item to be removed.
     * @param string $item_id ID of the item iff `itemType` is `item`. ID of the series iff `itemType` is `series`.
     * @param float $time Time index of the item to be removed.
     */
    public function __construct($series_id, $item_type, $item_id, $time) {
        $this->series_id = $series_id;
        $this->item_type = $item_type;
        $this->item_id = $item_id;
        $this->time = $time;
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
        return "/{databaseId}/series/{$this->series_id}/items/";
    }

    /**
     * Get query parameters
     * @return array Values of query parameters (name of parameter => value of the parameter)
     */
    public function getQueryParameters() {
        $params = array();
        $params['itemType'] = $this->item_type;
        $params['itemId'] = $this->item_id;
        $params['time'] = $this->time;
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
