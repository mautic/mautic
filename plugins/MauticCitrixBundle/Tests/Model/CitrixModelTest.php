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

use Mautic\CoreBundle\Test\MauticWebTestCase;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;

class CitrixModelTest extends MauticWebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getMauticFixtures($returnClassNames = false)
    {
        $fixtures    = [];
        $fixturesDir = __DIR__.'/../DataFixtures/ORM';

        if (file_exists($fixturesDir)) {
            $classPrefix = 'MauticPlugin\\MauticCitrixBundle\\Tests\\DataFixtures\\ORM\\';
            $this->populateFixturesFromDirectory($fixturesDir, $fixtures, $classPrefix, $returnClassNames);
        }

        return $fixtures;
    }

    public function testCountEventsBy()
    {
        /** @var CitrixModel $model */
        $model = $this->container->get('mautic.citrix.model.citrix');
        $count = $model->countEventsBy('webinar', "joe.o'connor@domain.com", 'registered', ['sample-webinar_#0000']);
        $this->assertEquals($count, 1);
    }
}
