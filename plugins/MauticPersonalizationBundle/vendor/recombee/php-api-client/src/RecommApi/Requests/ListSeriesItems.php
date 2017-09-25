<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * ListSeriesItems request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * List all the items present in the given series, sorted according to their time index values.
 */
class ListSeriesItems extends Request {

    /**
     * @var string $series_id ID of the series items of which are to be listed.
     */
    protected $series_id;

    /**
     * Construct the request
     * @param string $series_id ID of the series items of which are to be listed.
     */
    public function __construct($series_id) {
        $this->series_id = $series_id;
        $this->timeout = 1000;
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
        return $p;
    }

}
?>
