<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListUserCartAdditions;

class ListUserCartAdditionsTest extends ListUserInteractionsTestCase {

    protected function createRequest($user_id) {
        return new ListUserCartAdditions($user_id);
    }
}
?>
