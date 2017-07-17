<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class EmailTokenHelper.
 *
 * @deprecated 2.6.0 to be removed in 3.0
 */
class EmailTokenHelper
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param int $page
     *
     * @return string
     */
    public function getTokenContent($page = 1)
    {
        if (!$this->factory->getSecurity()->isGranted('lead:fields:full')) {
            return;
        }

        $session = $this->factory->getSession();

        //set limits
        $limit = 5;

        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $request = $this->factory->getRequest();
        $search  = $request->get('search', $session->get('mautic.lead.emailtoken.filter', ''));

        $session->set('mautic.lead.emailtoken.filter', $search);

        $filter = [
            'string' => $search,
            'force'  => [
                [
                    'column' => 'f.isPublished',
                    'expr'   => 'eq',
                    'value'  => true,
                ],
            ],
        ];

        $fields = $this->factory->getModel('lead.field')->getEntities(
            [
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => $filter,
                'orderBy'        => 'f.label',
                'orderByDir'     => 'ASC',
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]);
        $count = count($fields);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $page = 1;
            } else {
                $page = (ceil($count / $limit)) ?: 1;
            }
            $session->set('mautic.lead.emailtoken.page', $page);
        }

        return $this->factory->getTemplating()->render('MauticLeadBundle:SubscribedEvents\EmailToken:list.html.php', [
            'items'       => $fields,
            'page'        => $page,
            'limit'       => $limit,
            'totalCount'  => $count,
            'tmpl'        => $request->get('tmpl', 'index'),
            'searchValue' => $search,
        ]);
    }
}
