<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteRating;

class DeleteRatingTest extends DeleteInteractionTestCase {

    protected function createRequest($user_id, $item_id, $optional=array()) {
        return new DeleteRating($user_id, $item_id, $optional);
    }
}
?>
