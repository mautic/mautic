<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Crate;

use Mautic\FormBundle\Crate\ObjectCrate;
use PHPUnit\Framework\Assert;

final class ObjectCrateTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $field = new ObjectCrate('contact', 'Contact');

        Assert::assertSame('contact', $field->getKey());
        Assert::assertSame('Contact', $field->getName());
    }
}
