<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteDetailView;

class DeleteDetailViewTest extends DeleteInteractionTestCase {

    protected function createRequest($user_id, $item_id, $optional=array()) {
        return new DeleteDetailView($user_id, $item_id, $optional);
    }
}
?>
