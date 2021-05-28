<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use MauticPlugin\MauticCitrixBundle\Tests\DataFixtures\ORM\LoadCitrixData;

class CitrixModelTest extends MauticMysqlTestCase
{
    public function testCountEventsBy()
    {
        $this->loadFixtures([LoadCitrixData::class]);

        /** @var CitrixModel $model */
        $model = self::$container->get('mautic.citrix.model.citrix');
        $count = $model->countEventsBy('webinar', "joe.o'connor@domain.com", 'registered', ['sample-webinar_#0000']);
        $this->assertEquals(1, $count);
    }
}
