<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteCartAddition;

class DeleteCartAdditionTest extends DeleteInteractionTestCase {

    protected function createRequest($user_id, $item_id, $optional=array()) {
        return new DeleteCartAddition($user_id, $item_id, $optional);
    }
}
?>
