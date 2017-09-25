<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListUserBookmarks;

class ListUserBookmarksTest extends ListUserInteractionsTestCase {

    protected function createRequest($user_id) {
        return new ListUserBookmarks($user_id);
    }
}
?>
