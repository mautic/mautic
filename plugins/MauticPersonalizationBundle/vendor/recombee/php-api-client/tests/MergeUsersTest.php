<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\MergeUsers;

class MergeUsersTest extends MergeUsersTestCase {

    protected function createRequest($target_user_id, $source_user_id, $optional=array()) {
        return new MergeUsers($target_user_id, $source_user_id, $optional);
    }
}
?>
