<?php

namespace Mautic\CoreBundle\Test\Session;

use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

/**
 * Class FixedMockFileSessionStorage.
 *
 * Work around for Symfony bug https://github.com/symfony/symfony/issues/13450
 */
class FixedMockFileSessionStorage extends MockFileSessionStorage
{
    public function setId($id)
    {
        if ($this->id !== $id) {
            parent::setId($id);
        }
    }
}
