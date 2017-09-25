<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\AddDetailView;

class AddDetailViewTest extends AddInteractionTestCase {

    protected function createRequest($user_id, $item_id, $optional=array()) {
        return new AddDetailView($user_id, $item_id, $optional);
    }
}
?>
