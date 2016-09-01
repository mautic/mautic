<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Event\CompanyBuilderEvent;
use Mautic\LeadBundle\CompanyEvents;
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
     * PointModel constructor.
     *
     * @param Session $session
     *
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\LeadBundle\Entity\CompanyRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Company');
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
        $user  = (!$this->security->isGranted('lead:leads:viewother')) ?
            $this->factory->getUser() : false;
        $companys = $this->em->getRepository('MauticLeadBundle:Company')->getCompanies($user);

        return $companys;
    }

    /**
     * Reorganizes a field list to be keyed by field's group then alias
     *
     * @param $fields
     * @return array
     */
    public function organizeFieldsByGroup($fields)
    {
        $array = array();

        foreach ($fields as $field) {
            if ($field instanceof LeadField) {
                $alias = $field->getAlias();
                if ($field->isPublished() and $field->getObject() === 'Company') {
                    $group                          = $field->getGroup();
                    $array[$group][$alias]['id']    = $field->getId();
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field->getLabel();
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field->getType();
                }
            } else {
                $alias = $field['alias'];
                $field[]=$alias;
                $this->logger->debug(print_r($field,true));
                if ($field['isPublished'] and $field['object'] === 'Company') {
                    $group = $field['group'];
                    $array[$group][$alias]['id']    = $field['id'];
                    $array[$group][$alias]['group'] = $group;
                    $array[$group][$alias]['label'] = $field['label'];
                    $array[$group][$alias]['alias'] = $alias;
                    $array[$group][$alias]['type']  = $field['type'];
                }
            }
        }

        //make sure each group key is present
        $groups = array('core', 'social', 'personal', 'professional', 'other');
        foreach ($groups as $g) {
            if (!isset($array[$g])) {
                $array[$g] = array();
            }
        }

        return $array;
    }
}
