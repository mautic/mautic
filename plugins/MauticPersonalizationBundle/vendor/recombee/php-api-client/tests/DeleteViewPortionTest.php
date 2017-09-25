<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteViewPortion;

class DeleteViewPortionTest extends DeleteViewPortionTestCase {

    protected function createRequest($user_id, $item_id, $optional=array()) {
        return new DeleteViewPortion($user_id, $item_id, $optional);
    }
}
?>
