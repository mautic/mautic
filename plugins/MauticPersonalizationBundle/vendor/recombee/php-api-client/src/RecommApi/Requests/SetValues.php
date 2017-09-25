<?php

/**
 * SetValues request
 * @author Ondrej Fiedler <ondrej.fiedler@recombee.com>
 */
namespace Recombee\RecommApi\Requests;

/**
 * Set/update (some) property values of an entity.
 */
abstract class SetValues extends Request {

    /**
     * @var array $values The values for the individual properties.
     * Example of body:
     * ```
     *   {
     *     "product_description": "4K TV with 3D feature",
     *     "categories":   ["Electronics", "Televisions"],
     *     "price_usd": 342,
     *   }
     * ```
     */
    protected $values;


    /**
     * @var bool $cascade_create Sets whether the given user/item should be created if not present in the database.
     */
    protected $cascade_create;

    /**
     * Construct the request
     * @param array $values The values for the individual properties.
     * Example of body:
     * ```
     *   {
     *     "product_description": "4K TV with 3D feature",
     *     "categories":   ["Electronics", "Televisions"],
     *     "price_usd": 342,
     *   }
     * ```
     * @param array $optional Optional parameters given as an array containing pairs name of the parameter => value
     * - Allowed parameters:
     *     - *cascadeCreate*
     *         - Type: bool
     *         - Description: Sets whether the given entity should be created if not present in the database.
     *
     */
    public function __construct($values, $optional = array()) {
        $this->values = $values;
        $this->timeout = 1000;
        $this->ensure_https = false;

        $this->cascade_create = isset($optional['cascadeCreate']) ? $optional['cascadeCreate'] : null;
        $this->optional = $optional;

        $existing_optional = array('cascadeCreate');
        foreach ($this->optional as $key => $value) {
            if (!in_array($key, $existing_optional))
                 throw new UnknownOptionalParameterException($key);
         }
    }

    /**
     * Get used HTTP method
     * @return static Used HTTP method
     */
    public function getMethod() {
        return Request::HTTP_POST;
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

        $result = array();
        foreach($this->values as $key => $value)
        {
            $result[$key] = $value;
            if(is_object($value) && $value instanceof \DateTime)
            {
                $result[$key] = $value->format(\DateTime::ATOM);
            }
        }

        if (isset($this->optional['cascadeCreate']))
            $result['!cascadeCreate'] = $this-> optional['cascadeCreate'];

        return $result;
    }

}
?>
