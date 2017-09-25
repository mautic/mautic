<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListUserPurchases;

class ListUserPurchasesTest extends ListUserInteractionsTestCase {

    protected function createRequest($user_id) {
        return new ListUserPurchases($user_id);
    }
}
?>
