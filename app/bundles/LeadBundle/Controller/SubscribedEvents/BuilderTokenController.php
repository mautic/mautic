<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\SubscribedEvents;

/**
 * Class BuilderTokenController.
 */
class BuilderTokenController extends \Mautic\CoreBundle\Controller\SubscribedEvents\BuilderTokenController
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return 'lead.field';
    }

    public function getBundleName()
    {
        return 'MauticLeadBundle';
    }

    /**
     * @return array
     */
    public function getPermissionSet()
    {
        return ['lead:fields:full'];
    }

    /**
     * @return array
     */
    public function getEntityArguments()
    {
        return [
            'filter' => [
                'force' => [
                    [
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true,
                    ],
                ],
            ],
            'orderBy'        => 'f.label',
            'orderByDir'     => 'ASC',
            'hydration_mode' => 'HYDRATE_ARRAY',
        ];
    }
}
