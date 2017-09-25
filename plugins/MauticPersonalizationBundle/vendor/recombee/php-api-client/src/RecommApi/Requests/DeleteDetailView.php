<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * DeleteDetailView request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Deletes an existing detail view uniquely specified by (`userId`, `itemId`, and `timestamp`) or all the detail views with given `userId` and `itemId` if `timestamp` is omitted.
 */
class DeleteDetailView extends Request {

    /**
     * @var string $user_id ID of the user who made the detail view.
     */
    protected $user_id;
    /**
     * @var string $item_id ID of the item of which the details were viewed.
     */
    protected $item_id;
    /**
     * @var float $timestamp Unix timestamp of the detail view. If the `timestamp` is omitted, then all the detail views with given `userId` and `itemId` are deleted.
     */
    protected $timestamp;
    /**
     * @var array Array containing values of optional parameters
     */
   protected $optional;

    /**
     * Construct the request
     * @param string $user_id ID of the user who made the detail view.
     * @param string $item_id ID of the item of which the details were viewed.
     * @param array $optional Optional parameters given as an array containing pairs name of the parameter => value
     * - Allowed parameters:
     *     - *timestamp*
     *         - Type: float
     *         - Description: Unix timestamp of the detail view. If the `timestamp` is omitted, then all the detail views with given `userId` and `itemId` are deleted.
     * @throws Exceptions\UnknownOptionalParameterException UnknownOptionalParameterException if an unknown optional parameter is given in $optional
     */
    public function __construct($user_id, $item_id, $optional = array()) {
        $this->user_id = $user_id;
        $this->item_id = $item_id;
        $this->timestamp = isset($optional['timestamp']) ? $optional['timestamp'] : null;
        $this->optional = $optional;

        $existing_optional = array('timestamp');
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
        return Request::HTTP_DELETE;
    }

    /**
     * Get URI to the endpoint
     * @return string URI to the endpoint
     */
    public function getPath() {
        return "/{databaseId}/detailviews/";
    }

    /**
     * Get query parameters
     * @return array Values of query parameters (name of parameter => value of the parameter)
     */
    public function getQueryParameters() {
        $params = array();
        $params['userId'] = $this->user_id;
        $params['itemId'] = $this->item_id;
        if (isset($this->optional['timestamp']))
            $params['timestamp'] = $this->optional['timestamp'];
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
