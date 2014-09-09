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

$container->setDefinition(
    'mautic.campaign.type.form',
    new Definition(
        'Mautic\CampaignBundle\Form\Type\CampaignType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'campaign'
    ));

$container->setDefinition(
    'mautic.campaignrange.type.action',
    new Definition(
        'Mautic\CampaignBundle\Form\Type\CampaignEventType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'campaignevent'
    ));