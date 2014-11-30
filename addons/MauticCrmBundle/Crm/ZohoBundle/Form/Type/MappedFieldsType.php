<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Mapper\LeadMapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ApiKeysType
 *
 * @package MauticAddon\MauticCrmBundle\Crm\SugarcrmBundle\Form\Type
 */
class MappedFieldsType extends AbstractType
{
    /**
     * @var \Mautic\CoreBundle\Factory\MauticFactory
     */
    protected $factory;

    /**
     * @param $config
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $request = $this->factory->getRequest();
        $object = $request->get('object');

        switch (strtolower($object))
        {
            case 'lead':
                $leadMapper = new LeadMapper($this->factory);
                $leadMapper->buildForm($this->factory, $builder, $options);
                break;
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return "zoho_mappedfields";
    }
}