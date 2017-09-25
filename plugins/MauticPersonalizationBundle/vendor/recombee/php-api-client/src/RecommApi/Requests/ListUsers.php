<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * ListUsers request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Gets a list of IDs of users currently present in the catalog.
 */
class ListUsers extends Request {

    /**
     * @var string $filter Boolean-returning [ReQL](https://docs.recombee.com/reql.html) expression, which allows you to filter users to be listed. Only the users for which the expression is *true* will be returned.
     */
    protected $filter;
    /**
     * @var int $count The number of users to be listed.
     */
    protected $count;
    /**
     * @var int $offset Specifies the number of users to skip (ordered by `userId`).
     */
    protected $offset;
    /**
     * @var bool $return_properties With `returnProperties=true`, property values of the listed users are returned along with their IDs in a JSON dictionary. 
     * Example response:
     * ```
     *   [
     *     {
     *       "userId": "user-81",
     *       "country": "US",
     *       "sex": "M"
     *     },
     *     {
     *       "userId": "user-314",
     *       "country": "CAN",
     *       "sex": "F"
     *     }
     *   ]
     * ```
     */
    protected $return_properties;
    /**
     * @var array $included_properties Allows to specify, which properties should be returned when `returnProperties=true` is set. The properties are given as a comma-separated list. 
     * Example response for `includedProperties=country`:
     * ```
     *   [
     *     {
     *       "userId": "user-81",
     *       "country": "US"
     *     },
     *     {
     *       "userId": "user-314",
     *       "country": "CAN"
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
     *         - Description: Boolean-returning [ReQL](https://docs.recombee.com/reql.html) expression, which allows you to filter users to be listed. Only the users for which the expression is *true* will be returned.
     *     - *count*
     *         - Type: int
     *         - Description: The number of users to be listed.
     *     - *offset*
     *         - Type: int
     *         - Description: Specifies the number of users to skip (ordered by `userId`).
     *     - *returnProperties*
     *         - Type: bool
     *         - Description: With `returnProperties=true`, property values of the listed users are returned along with their IDs in a JSON dictionary. 
     * Example response:
     * ```
     *   [
     *     {
     *       "userId": "user-81",
     *       "country": "US",
     *       "sex": "M"
     *     },
     *     {
     *       "userId": "user-314",
     *       "country": "CAN",
     *       "sex": "F"
     *     }
     *   ]
     * ```
     *     - *includedProperties*
     *         - Type: array
     *         - Description: Allows to specify, which properties should be returned when `returnProperties=true` is set. The properties are given as a comma-separated list. 
     * Example response for `includedProperties=country`:
     * ```
     *   [
     *     {
     *       "userId": "user-81",
     *       "country": "US"
     *     },
     *     {
     *       "userId": "user-314",
     *       "country": "CAN"
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
        $this->timeout = 239000;
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
        return "/{databaseId}/users/list/";
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
