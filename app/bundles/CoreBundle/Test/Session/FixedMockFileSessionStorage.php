<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
