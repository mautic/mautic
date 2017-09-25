<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * InsertToSeries request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Inserts an existing item/series into series of given seriesId at position determined by time.
 */
class InsertToSeries extends Request {

    /**
     * @var string $series_id ID of the series to be inserted into.
     */
    protected $series_id;
    /**
     * @var string $item_type `item` iff the regular item from the catalog is to be inserted, `series` iff series is inserted as the item.
     */
    protected $item_type;
    /**
     * @var string $item_id ID of the item iff `itemType` is `item`. ID of the series iff `itemType` is `series`.
     */
    protected $item_id;
    /**
     * @var float $time Time index used for sorting items in the series. According to time, items are sorted within series in ascending order. In the example of TV show episodes, the episode number is a natural choice to be passed as time.
     */
    protected $time;
    /**
     * @var bool $cascade_create Indicates that any non-existing entity specified within the request should be created (as is corresponding PUT requests were invoked). This concerns both the `seriesId` and the `itemId`. If `cascadeCreate` is set true, the behavior also depends on the `itemType`. Either item or series may be created if not present in the database.
     */
    protected $cascade_create;
    /**
     * @var array Array containing values of optional parameters
     */
   protected $optional;

    /**
     * Construct the request
     * @param string $series_id ID of the series to be inserted into.
     * @param string $item_type `item` iff the regular item from the catalog is to be inserted, `series` iff series is inserted as the item.
     * @param string $item_id ID of the item iff `itemType` is `item`. ID of the series iff `itemType` is `series`.
     * @param float $time Time index used for sorting items in the series. According to time, items are sorted within series in ascending order. In the example of TV show episodes, the episode number is a natural choice to be passed as time.
     * @param array $optional Optional parameters given as an array containing pairs name of the parameter => value
     * - Allowed parameters:
     *     - *cascadeCreate*
     *         - Type: bool
     *         - Description: Indicates that any non-existing entity specified within the request should be created (as is corresponding PUT requests were invoked). This concerns both the `seriesId` and the `itemId`. If `cascadeCreate` is set true, the behavior also depends on the `itemType`. Either item or series may be created if not present in the database.
     * @throws Exceptions\UnknownOptionalParameterException UnknownOptionalParameterException if an unknown optional parameter is given in $optional
     */
    public function __construct($series_id, $item_type, $item_id, $time, $optional = array()) {
        $this->series_id = $series_id;
        $this->item_type = $item_type;
        $this->item_id = $item_id;
        $this->time = $time;
        $this->cascade_create = isset($optional['cascadeCreate']) ? $optional['cascadeCreate'] : null;
        $this->optional = $optional;

        $existing_optional = array('cascadeCreate');
        foreach ($this->optional as $key => $value) {
            if (!in_array($key, $existing_optional))
                 throw new UnknownOptionalParameterException($key);
         }
        $this->timeout = 1000;
        $this->ensure_https = false;
    }

    /**
     * Get used HTTP method
     * @return static Used HTTP method
     */
    public function getMethod() {
        return Request::HTTP_POST;
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
        return $params;
    }

    /**
     * Get body parameters
     * @return array Values of body parameters (name of parameter => value of the parameter)
     */
    public function getBodyParameters() {
        $p = array();
        $p['itemType'] = $this->item_type;
        $p['itemId'] = $this->item_id;
        $p['time'] = $this->time;
        if (isset($this->optional['cascadeCreate']))
             $p['cascadeCreate'] = $this-> optional['cascadeCreate'];
        return $p;
    }

}
?>
