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

namespace Mautic\FormBundle\Tests\Collection;

use Mautic\FormBundle\Collection\ObjectCollection;
use Mautic\FormBundle\Crate\ObjectCrate;

final class ObjectCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testToChoices()
    {
        $collection = new ObjectCollection(
            [
                new ObjectCrate('contact', 'Contact'),
                new ObjectCrate('company', 'Company'),
            ]
        );

        $this->assertSame(
            [
                'Contact' => 'contact',
                'Company' => 'company',
            ],
            $collection->toChoices()
        );
    }
}
