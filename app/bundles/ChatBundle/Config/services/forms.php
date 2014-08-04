<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;

$container->setDefinition(
    'mautic.form.type.chatchannel',
    new Definition(
        'Mautic\ChatBundle\Form\Type\ChannelType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'chatchannel'
    ));