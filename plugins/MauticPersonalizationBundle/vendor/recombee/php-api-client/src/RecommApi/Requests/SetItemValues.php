<?php

/**
 * SetItemValues request
 * @author Ondrej Fiedler <ondrej.fiedler@recombee.com>
 */
namespace Recombee\RecommApi\Requests;

/**
 * Set/update (some) property values of a given item. The properties (columns) must be previously created by [Add item property](https://docs.recombee.com/api.html#add-item-property).
 */
class SetItemValues extends SetValues {

    /**
     * @var string $item_id ID of the item which will be modified.
     */
    protected $item_id;
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
     * Construct the request
     * @param string $item_id ID of the item which will be modified.
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
    public function __construct($item_id, $values, $optional=array()) {
        $this->item_id = $item_id;
        parent::__construct($values, $optional);
    }

    /**
     * Get URI to the endpoint
     * @return string URI to the endpoint
     */
    public function getPath() {
        return "/{databaseId}/items/{$this->item_id}";
    }
}
?>
