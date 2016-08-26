<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CompanyBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CompanyBundle\Entity\Company;
use Mautic\CompanyBundle\Event\CompanyBuilderEvent;
use Mautic\CompanyBundle\CompanyEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class CompanyModel
 */
class CompanyModel extends CommonFormModel
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var LeadModel
     */
    protected $leadModel;
    /**
     * PointModel constructor.
     *
     * @param Session $session
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel, Session $session)
    {
        $this->session = $session;
        $this->leadModel = $leadModel;
    }
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CompanyBundle\Entity\CompanyRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCompanyBundle:Company');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'company:companies';
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Company) {
            throw new MethodNotAllowedHttpException(array('Company'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }
        return $formFactory->create('company', $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return Company|null
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Company();
        }

        return parent::getEntity($id);
    }
    
    /**
     *
     * @return mixed
     */
    public function getUserCompanies()
    {
        $user  = (!$this->security->isGranted('company:companies:viewother')) ?
            $this->factory->getUser() : false;
        $companys = $this->em->getRepository('MauticCompanyBundle:Company')->getCompanys($user);

        return $companys;
    }
}
