<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\CoreBundle\Helper\SearchStringHelper;
use Mautic\CoreBundle\Test\MauticWebTestCase;

/**
* Class RoleRepositoryTest
 * @package Mautic\UserBundle\Tests\Entity
 */
class RoleRepositoryTest extends MauticWebTestCase
{

    public function testAdvancedSearch()
    {
        $repo = $this->em->getRepository('MauticUserBundle:Role');

        //set the translator
        $repo->setTranslator($this->container->get('translator'));

        //translator is off for tests so use the language string for commands
        $args     = array(
            "filter" =>
                "mautic.core.searchcommand.is:mautic.user.user.searchcommand.isadmin " .
                "mautic.core.searchcommand.name:mautic.user.role.admin.name"
        );

        $filterHelper = new SearchStringHelper();
        $filter       = $filterHelper->parseSearchString($args["filter"]);

        $roles = $repo->getEntities($args);
        $this->assertCount(1, $roles, $roles->getQuery()->getDql() . "\n\n" . print_r($roles->getQuery()->getParameters(), true) . "\n\n".print_r($filter,true));
    }
}
