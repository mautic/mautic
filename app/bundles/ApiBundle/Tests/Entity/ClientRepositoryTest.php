<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Tests\Entity;

use Mautic\CoreBundle\Helper\SearchStringHelper;
use Mautic\CoreBundle\Test\MauticWebTestCase;

/**
* Class ClientRepositoryTest
 * @package Mautic\ApiBundle\Tests\Entity
 */
class ClientRepositoryTest extends MauticWebTestCase
{

    public function testAdvancedSearch()
    {
        $repo = $this->em->getRepository('MauticApiBundle:oAuth2\Client');

        //set the translator
        $repo->setTranslator($this->container->get('translator'));

        //translator is off for tests so use the language string for commands
        $args     = array(
            "filter" =>
                "mautic.core.searchcommand.name:mautic" .
                " mautic.api.client.searchcommand.redirecturi:" . str_replace("http://", "", $this->container->get('router')->generate(
                    'mautic_dashboard_index', array(), true))
        );

        $filterHelper = new SearchStringHelper();
        $filter       = $filterHelper->parseSearchString($args["filter"]);

        $clients = $repo->getEntities($args);
        $this->assertCount(1, $clients, $clients->getQuery()->getDql() . "\n\n" . print_r($clients->getQuery()->getParameters(), true) . "\n\n".print_r($filter,true));
    }
}
