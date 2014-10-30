<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class PageTokenHelper
 */
class PageTokenHelper
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
        //get a list of forms

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'form:forms:viewown',
            'form:forms:viewother'
        ), "RETURN_ARRAY");

        if (!$permissions['form:forms:viewown'] && !$permissions['form:forms:viewother']) {
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
        $search  = $request->get('search', $session->get('mautic.formtoken.filter', ''));

        $session->set('mautic.formtoken.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['form:forms:viewother']) {
            $filter['force'] = array('column' => 'f.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser());
        }

        $forms = $this->factory->getModel('form.form')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderByDir' => "DESC"
            ));
        $count = count($forms);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $page = 1;
            } else {
                $page = (floor($limit / $count)) ? : 1;
            }
            $session->set('mautic.formtoken.page', $page);
        }

        return $this->factory->getTemplating()->render('MauticFormBundle:SubscribedEvents\PageToken:list.html.php', array(
            'items'       => $forms,
            'page'        => $page,
            'limit'       => $limit,
            'totalCount'  => $count,
            'tmpl'        => $request->get('tmpl', 'index'),
            'searchValue' => $search
        ));
    }
}
