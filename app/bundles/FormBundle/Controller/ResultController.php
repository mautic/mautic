<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends CommonFormController
{

    /**
     * @param $objectId
     * @param $page
     */
    public function indexAction($objectId, $page)
    {
        $formModel = $this->factory->getModel('form.form');
        $form      = $formModel->getEntity($objectId);

        $formPage  = $this->factory->getSession()->get('mautic.form.page', 1);
        $returnUrl   = $this->generateUrl('mautic_form_index', array('page' => $formPage));

        if ($form === null) {
            //redirect back to form list
            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $formPage),
                'contentTemplate' => 'MauticFormBundle:Form:index',
                'passthroughVars' => array(
                    'activeLink'    => 'mautic_form_index',
                    'mauticContent' => 'form'
                ),
                'flashes'         => array(array(
                    'type'    => 'error',
                    'msg'     => 'mautic.form.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                ))
            ));
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'form:forms:viewown', 'form:forms:viewother', $form->getCreatedBy()
        ))  {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.formresult.'.$objectId.'.limit', $this->factory->getParameter('default_pagelimit'));

        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->factory->getSession()->get('mautic.formresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $this->factory->getSession()->get('mautic.formresult.'.$objectId.'.orderbydir', 'ASC');

        $filters    = $this->factory->getSession()->get('mautic.formresult.'.$objectId.'.filters', array());

        $model = $this->factory->getModel('form.submission');

        //get the results
        $entities = $model->getEntities(
            array(
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => array('force' => $filters),
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
                'form'           => $form,
                'withTotalCount' => true
            )
        );

        $count   = $entities['count'];
        $results = $entities['results'];
        unset($entities);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->factory->getSession()->set('mautic.formresult.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_form_results', array('objectId' => $objectId, 'page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticFormBundle:Result:index',
                'passthroughVars' => array(
                    'activeLink'    => 'mautic_form_index',
                    'mauticContent' => 'formresult'
                )
            ));
        }

        //set what page currently on so that we can return here if need be
        $this->factory->getSession()->set('mautic.formresult.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';
        return $this->delegateView(array(
            'viewParameters'  => array(
                'items'       => $results,
                'filters'     => $filters,
                'form'        => $form,
                'page'        => $page,
                'totalCount'  => $count,
                'limit'       => $limit,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticFormBundle:Result:list.html.php',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_form_index',
                'mauticContent' => 'formresult',
                'route'         => $this->generateUrl('mautic_form_results', array(
                    'objectId'       => $objectId,
                    'page'           => $page
                )),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    /**
     * @param        $objectId
     * @param string $format
     * @return mixed
     */
    public function exportAction($objectId, $format = 'csv')
    {
        $formModel  = $this->factory->getModel('form.form');
        $form       = $formModel->getEntity($objectId);

        $formPage   = $this->factory->getSession()->get('mautic.form.page', 1);
        $returnUrl  = $this->generateUrl('mautic_form_index', array('page' => $formPage));

        if ($form === null) {
            //redirect back to form list
            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $formPage),
                'contentTemplate' => 'MauticFormBundle:Form:index',
                'passthroughVars' => array(
                    'activeLink'    => 'mautic_form_index',
                    'mauticContent' => 'form'
                ),
                'flashes'         => array(array(
                    'type'    => 'error',
                    'msg'     => 'mautic.form.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                ))
            ));
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'form:forms:viewown', 'form:forms:viewother', $form->getCreatedBy()
        ))  {
            return $this->accessDenied();
        }

        $limit = $this->factory->getParameter('default_pagelimit');

        $page = $this->factory->getSession()->get('mautic.formresult.page', 1);
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $orderBy    = $this->factory->getSession()->get('mautic.formresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $this->factory->getSession()->get('mautic.formresult.'.$objectId.'.orderbydir', 'ASC');

        $filters    = $this->factory->getSession()->get('mautic.formresult.'.$objectId.'.filters', array());

        $args = array(
            'start'      => $start,
            'limit'      => $limit,
            'filter'     => array('force' => $filters),
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
            'form'       => $form
        );

        $model = $this->factory->getModel('form.submission');

        return $model->exportResults($format, $form, $args);
    }
}