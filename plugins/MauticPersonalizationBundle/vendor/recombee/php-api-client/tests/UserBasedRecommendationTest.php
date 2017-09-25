<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\UserBasedRecommendation;

class UserBasedRecommendationTest extends RecommendationTestCase {

    protected function createRequest($user_id, $count, $optional=array()) {
        return new UserBasedRecommendation($user_id, $count, $optional);
    }
}
?>
