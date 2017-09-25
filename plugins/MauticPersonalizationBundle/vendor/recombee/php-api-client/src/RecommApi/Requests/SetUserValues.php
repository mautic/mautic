<?php

/**
 * SetUserValues request
 * @author Ondrej Fiedler <ondrej.fiedler@recombee.com>
 */
namespace Recombee\RecommApi\Requests;

/**
 * Set/update (some) property values of a given user. The properties (columns) must be previously created by [Add user property](https://docs.recombee.com/api.html#add-user-property).
 */
class SetUserValues extends SetValues {

    /**
     * @var string $user_id ID of the user which will be modified.
     */
    protected $user_id;
    /**
     * @var array $values The values for the individual properties.
     * Example of body:
     * ```
     *   {
     *     "country": "US",
     *     "sex":   "F"
     *   }
     * ```
     */
    protected $values;

    /**
     * Construct the request
     * @param string $user_id ID of the user which will be modified.
     * @param array $values The values for the individual properties.
     * Example of body:
     * ```
     *   {
     *     "country": "US",
     *     "sex":   "F"
     *   }
     * ```
     * @param array $optional Optional parameters given as an array containing pairs name of the parameter => value
     * - Allowed parameters:
     *     - *cascadeCreate*
     *         - Type: bool
     *         - Description: Sets whether given user should be created if not present in the database.
     *
     */
    public function __construct($user_id, $values, $optional=array()) {
        $this->user_id = $user_id;
        parent::__construct($values, $optional);
    }

    /**
     * Get URI to the endpoint
     * @return string URI to the endpoint
     */
    public function getPath() {
        return "/{databaseId}/users/{$this->user_id}";
    }
}
?>
