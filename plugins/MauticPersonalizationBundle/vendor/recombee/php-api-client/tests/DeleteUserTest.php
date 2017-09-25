<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteUser;

class DeleteUserTest extends DeleteEntityTestCase {

    protected function createRequest($user_id) {
        return new DeleteUser($user_id);
    }
}
?>
