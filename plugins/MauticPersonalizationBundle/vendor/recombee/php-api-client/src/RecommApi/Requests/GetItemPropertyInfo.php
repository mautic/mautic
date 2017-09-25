<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * GetItemPropertyInfo request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Gets information about specified item property.
 */
class GetItemPropertyInfo extends Request {

    /**
     * @var string $property_name Name of the property about which the information is to be retrieved.
     */
    protected $property_name;

    /**
     * Construct the request
     * @param string $property_name Name of the property about which the information is to be retrieved.
     */
    public function __construct($property_name) {
        $this->property_name = $property_name;
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
        return "/{databaseId}/items/properties/{$this->property_name}";
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
