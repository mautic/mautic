<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class BuilderTokenHelper
 */
class BuilderTokenHelper
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
        if (!$this->factory->getSecurity()->isGranted('asset:assets:view')) {
            return;
        }

        $session = $this->factory->getSession();

        //set limits
        $limit = 5;

        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $request = $this->factory->getRequest();
        $search  = $request->get('search', $session->get('mautic.asset.buildertoken.filter', ''));

        $session->set('mautic.asset.buildertoken.filter', $search);

        $assets = $this->factory->getModel('asset')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => array('string' => $search),
                'orderByDir' => "DESC"
            ));
        $count = count($assets);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $page = 1;
            } else {
                $page = (floor($limit / $count)) ? : 1;
            }
            $session->set('mautic.asset.buildertoken.page', $page);
        }

        return $this->factory->getTemplating()->render('MauticAssetBundle:SubscribedEvents\BuilderToken:list.html.php', array(
            'items'       => $assets,
            'page'        => $page,
            'limit'       => $limit,
            'totalCount'  => $count,
            'tmpl'        => $request->get('tmpl', 'index'),
            'searchValue' => $search
        ));
    }
}
