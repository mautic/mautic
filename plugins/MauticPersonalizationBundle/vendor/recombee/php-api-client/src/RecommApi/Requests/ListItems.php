<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * ListItems request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Gets a list of IDs of items currently present in the catalog.
 */
class ListItems extends Request {

    /**
     * @var string $filter Boolean-returning [ReQL](https://docs.recombee.com/reql.html) expression, which allows you to filter items to be listed. Only the items for which the expression is *true* will be returned.
     */
    protected $filter;
    /**
     * @var int $count The number of items to be listed.
     */
    protected $count;
    /**
     * @var int $offset Specifies the number of items to skip (ordered by `itemId`).
     */
    protected $offset;
    /**
     * @var bool $return_properties With `returnProperties=true`, property values of the listed items are returned along with their IDs in a JSON dictionary. 
     * Example response:
     * ```
     *   [
     *     {
     *       "itemId": "tv-178",
     *       "description": "4K TV with 3D feature",
     *       "categories":   ["Electronics", "Televisions"],
     *       "price": 342,
     *       "url": "myshop.com/tv-178"
     *     },
     *     {
     *       "itemId": "mixer-42",
     *       "description": "Stainless Steel Mixer",
     *       "categories":   ["Home & Kitchen"],
     *       "price": 39,
     *       "url": "myshop.com/mixer-42"
     *     }
     *   ]
     * ```
     */
    protected $return_properties;
    /**
     * @var array $included_properties Allows to specify, which properties should be returned when `returnProperties=true` is set. The properties are given as a comma-separated list. 
     * Example response for `includedProperties=description,price`:
     * ```
     *   [
     *     {
     *       "itemId": "tv-178",
     *       "description": "4K TV with 3D feature",
     *       "price": 342
     *     },
     *     {
     *       "itemId": "mixer-42",
     *       "description": "Stainless Steel Mixer",
     *       "price": 39
     *     }
     *   ]
     * ```
     */
    protected $included_properties;
    /**
     * @var array Array containing values of optional parameters
     */
   protected $optional;

    /**
     * Construct the request
     * @param array $optional Optional parameters given as an array containing pairs name of the parameter => value
     * - Allowed parameters:
     *     - *filter*
     *         - Type: string
     *         - Description: Boolean-returning [ReQL](https://docs.recombee.com/reql.html) expression, which allows you to filter items to be listed. Only the items for which the expression is *true* will be returned.
     *     - *count*
     *         - Type: int
     *         - Description: The number of items to be listed.
     *     - *offset*
     *         - Type: int
     *         - Description: Specifies the number of items to skip (ordered by `itemId`).
     *     - *returnProperties*
     *         - Type: bool
     *         - Description: With `returnProperties=true`, property values of the listed items are returned along with their IDs in a JSON dictionary. 
     * Example response:
     * ```
     *   [
     *     {
     *       "itemId": "tv-178",
     *       "description": "4K TV with 3D feature",
     *       "categories":   ["Electronics", "Televisions"],
     *       "price": 342,
     *       "url": "myshop.com/tv-178"
     *     },
     *     {
     *       "itemId": "mixer-42",
     *       "description": "Stainless Steel Mixer",
     *       "categories":   ["Home & Kitchen"],
     *       "price": 39,
     *       "url": "myshop.com/mixer-42"
     *     }
     *   ]
     * ```
     *     - *includedProperties*
     *         - Type: array
     *         - Description: Allows to specify, which properties should be returned when `returnProperties=true` is set. The properties are given as a comma-separated list. 
     * Example response for `includedProperties=description,price`:
     * ```
     *   [
     *     {
     *       "itemId": "tv-178",
     *       "description": "4K TV with 3D feature",
     *       "price": 342
     *     },
     *     {
     *       "itemId": "mixer-42",
     *       "description": "Stainless Steel Mixer",
     *       "price": 39
     *     }
     *   ]
     * ```
     * @throws Exceptions\UnknownOptionalParameterException UnknownOptionalParameterException if an unknown optional parameter is given in $optional
     */
    public function __construct($optional = array()) {
        $this->filter = isset($optional['filter']) ? $optional['filter'] : null;
        $this->count = isset($optional['count']) ? $optional['count'] : null;
        $this->offset = isset($optional['offset']) ? $optional['offset'] : null;
        $this->return_properties = isset($optional['returnProperties']) ? $optional['returnProperties'] : null;
        $this->included_properties = isset($optional['includedProperties']) ? $optional['includedProperties'] : null;
        $this->optional = $optional;

        $existing_optional = array('filter','count','offset','returnProperties','includedProperties');
        foreach ($this->optional as $key => $value) {
            if (!in_array($key, $existing_optional))
                 throw new UnknownOptionalParameterException($key);
         }
        $this->timeout = 600000;
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
        return "/{databaseId}/items/list/";
    }

    /**
     * Get query parameters
     * @return array Values of query parameters (name of parameter => value of the parameter)
     */
    public function getQueryParameters() {
        $params = array();
        if (isset($this->optional['filter']))
            $params['filter'] = $this->optional['filter'];
        if (isset($this->optional['count']))
            $params['count'] = $this->optional['count'];
        if (isset($this->optional['offset']))
            $params['offset'] = $this->optional['offset'];
        if (isset($this->optional['returnProperties']))
            $params['returnProperties'] = $this->optional['returnProperties'];
        if (isset($this->optional['includedProperties']))
            $params['includedProperties'] = $this->optional['includedProperties'];
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
