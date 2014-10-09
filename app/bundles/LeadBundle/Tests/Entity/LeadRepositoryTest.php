<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Helper\SearchStringHelper;
use Mautic\CoreBundle\Test\MauticWebTestCase;

/**
* Class LeadRepositoryTest
 * @package Mautic\UserBundle\Tests\Entity
 */
class LeadRepositoryTest extends MauticWebTestCase
{

    public function testAdvancedSearch()
    {
        $repo = $this->em->getRepository('MauticLeadBundle:Lead');

        //set the translator
        $repo->setTranslator($this->container->get('translator'));

        //translator is off for tests so use the language string for commands
        $args     = array(
            "filter" =>
                "mautic.core.searchcommand.is:mautic.lead.lead.searchcommand.isanonymous"
        );

        $filterHelper = new SearchStringHelper();
        $filter       = $filterHelper->parseSearchString($args["filter"]);

        $roles = $repo->getEntities($args);
        $this->assertCount(1, $roles, print_r($filter,true));
    }
}
