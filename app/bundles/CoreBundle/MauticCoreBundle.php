<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Mautic\CoreBundle\DependencyInjection\Compiler;

/**
 * Class MauticCoreBundle
 *
 * @package Mautic\CoreBundle
 */
class MauticCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\TemplatingPass());
        $container->addCompilerPass(new Compiler\TranslationsPass());
    }

    public function boot()
    {
        //set the table prefix as a constant to be used in repositories requiring DBAL
        $prefix = $this->container->getParameter('mautic.db_table_prefix');
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', $prefix);
    }
}