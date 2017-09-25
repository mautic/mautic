<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * DeleteSeries request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Deletes the series of given `seriesId` from the database.
 * Deleting a series will only delete assignment of items to it, not the items themselves!
 */
class DeleteSeries extends Request {

    /**
     * @var string $series_id ID of the series to be deleted.
     */
    protected $series_id;

    /**
     * Construct the request
     * @param string $series_id ID of the series to be deleted.
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
        return Request::HTTP_DELETE;
    }

    /**
     * Get URI to the endpoint
     * @return string URI to the endpoint
     */
    public function getPath() {
        return "/{databaseId}/series/{$this->series_id}";
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
