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

//Report forms
$container->setDefinition(
    'mautic.form.type.report',
    new Definition(
        'Mautic\ReportBundle\Form\Type\ReportType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'report',
    ));
