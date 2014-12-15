<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;

$container->setDefinition(
    'mautic.form.type.chatchannel',
    new Definition(
        'MauticAddon\MauticChatBundle\Form\Type\ChannelType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'chatchannel'
    ));