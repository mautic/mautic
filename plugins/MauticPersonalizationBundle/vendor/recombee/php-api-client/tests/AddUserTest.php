<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\AddUser;

class AddUserTest extends AddEntityTestCase {

    protected function createRequest($user_id) {
        return new AddUser($user_id);
    }
}
?>
