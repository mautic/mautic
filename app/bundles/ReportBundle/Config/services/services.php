<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

//FormBuilder Generator
$container->setDefinition('mautic.report.form_builder',
    new Definition(
        'Mautic\ReportBundle\Form\FormBuilder',
        array(
            new Reference('mautic.factory')
        )
    )
);

//Report Generator
$container->setDefinition('mautic.report.generator',
    new Definition(
        'Mautic\ReportBundle\Generator\ReportGenerator',
        array(
            new Reference('doctrine.orm.entity_manager'),
            new Reference('security.context'),
            new Reference('mautic.report.form_builder')
        )
    )
);
