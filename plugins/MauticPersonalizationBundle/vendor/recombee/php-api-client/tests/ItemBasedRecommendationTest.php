<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ItemBasedRecommendation;

class ItemBasedRecommendationTest extends RecommendationTestCase {

    protected function createRequest($item_id, $count, $optional=array()) {
    	$optional = array_merge($optional, ['targetUserId' => 'entity_id']);
        return new ItemBasedRecommendation($item_id, $count, $optional);
    }
}
?>
