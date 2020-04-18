<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    // Function to check if a field is empty
    public function seeFieldIsEmpty($value)
    {
        $this->assertTrue(empty($value));
    }

    // Function to check if a field is not empty
    public function seeFieldIsNotEmpty($value)
    {
        $this->assertFalse(empty($value));
    }
}
