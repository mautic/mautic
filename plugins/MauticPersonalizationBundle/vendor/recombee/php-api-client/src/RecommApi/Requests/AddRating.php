<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * AddRating request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * Adds a rating of given item made by a given user.
 */
class AddRating extends Request {

    /**
     * @var string $user_id User who submitted the rating
     */
    protected $user_id;
    /**
     * @var string $item_id Rated item
     */
    protected $item_id;
    /**
     * @var string|float $timestamp UTC timestamp of the rating as ISO8601-1 pattern or UTC epoch time. The default value is the current time.
     */
    protected $timestamp;
    /**
     * @var float $rating Rating rescaled to interval [-1.0,1.0], where -1.0 means the worst rating possible, 0.0 means neutral, and 1.0 means absolutely positive rating. For example, in the case of 5-star evaluations, rating = (numStars-3)/2 formula may be used for the conversion.
     */
    protected $rating;
    /**
     * @var bool $cascade_create Sets whether the given user/item should be created if not present in the database.
     */
    protected $cascade_create;
    /**
     * @var array Array containing values of optional parameters
     */
   protected $optional;

    /**
     * Construct the request
     * @param string $user_id User who submitted the rating
     * @param string $item_id Rated item
     * @param float $rating Rating rescaled to interval [-1.0,1.0], where -1.0 means the worst rating possible, 0.0 means neutral, and 1.0 means absolutely positive rating. For example, in the case of 5-star evaluations, rating = (numStars-3)/2 formula may be used for the conversion.
     * @param array $optional Optional parameters given as an array containing pairs name of the parameter => value
     * - Allowed parameters:
     *     - *timestamp*
     *         - Type: string|float
     *         - Description: UTC timestamp of the rating as ISO8601-1 pattern or UTC epoch time. The default value is the current time.
     *     - *cascadeCreate*
     *         - Type: bool
     *         - Description: Sets whether the given user/item should be created if not present in the database.
     * @throws Exceptions\UnknownOptionalParameterException UnknownOptionalParameterException if an unknown optional parameter is given in $optional
     */
    public function __construct($user_id, $item_id, $rating, $optional = array()) {
        $this->user_id = $user_id;
        $this->item_id = $item_id;
        $this->rating = $rating;
        $this->timestamp = isset($optional['timestamp']) ? $optional['timestamp'] : null;
        $this->cascade_create = isset($optional['cascadeCreate']) ? $optional['cascadeCreate'] : null;
        $this->optional = $optional;

        $existing_optional = array('timestamp','cascadeCreate');
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
        return "/{databaseId}/ratings/";
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
        $p['userId'] = $this->user_id;
        $p['itemId'] = $this->item_id;
        $p['rating'] = $this->rating;
        if (isset($this->optional['timestamp']))
             $p['timestamp'] = $this-> optional['timestamp'];
        if (isset($this->optional['cascadeCreate']))
             $p['cascadeCreate'] = $this-> optional['cascadeCreate'];
        return $p;
    }

}
?>
