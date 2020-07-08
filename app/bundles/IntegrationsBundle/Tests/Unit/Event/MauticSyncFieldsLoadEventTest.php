<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\Event;

use Mautic\IntegrationsBundle\Event\MauticSyncFieldsLoadEvent;
use PHPUnit\Framework\TestCase;

class MauticSyncFieldsLoadEventTest extends TestCase
{
    public function testWorkflow(): void
    {
        $objectName = 'object';
        $fields     = [
            'fieldKey' => 'fieldName',
        ];

        $newFieldKey   = 'newFieldKey';
        $newFieldValue = 'newFieldValue';

        $event = new MauticSyncFieldsLoadEvent($objectName, $fields);
        $this->assertSame($objectName, $event->getObjectName());
        $this->assertSame($fields, $event->getFields());
        $event->addField($newFieldKey, $newFieldValue);
        $this->assertSame(
            array_merge($fields, [$newFieldKey => $newFieldValue]),
            $event->getFields()
        );
    }
}
