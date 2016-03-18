<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


/**
 * Class FacebookLoginType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class FacebookLoginType extends AbstractType
{
	/**
	 * @var MauticFactory
	 */
	private $factory;
	
	public function __construct(MauticFactory $factory)
	{
		$this->factory = $factory;
	}
	
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		
		
		$builder->add('maxRows', 'text', array(
			'label_attr' => array('class' => 'control-label'),
			'label' => 'mautic.integration.Facebook.login.maxRows',
			'required' => false,
			'attr' => array(
				'class' => 'form-control',
				'placeholder' => 'mautic.integration.Facebook.login.maxRows',
				'preaddon' => 'fa fa-at'
			)
		));
		
		$builder->add('size', 'choice', array(
			'choices' => array(
				'icon' => 'mautic.integration.Facebook.login.size.icon',
				'small' => 'mautic.integration.Facebook.login.size.small',
				'medium' => 'mautic.integration.Facebook.login.size.medium',
				'large' => 'mautic.integration.Facebook.login.size.large',
				'xlarge' => 'mautic.integration.Facebook.login.size.xlarge',
			),
			'label' => 'mautic.integration.Facebook.login.size',
			'required' => false,
			'empty_value' => false,
			'label_attr' => array('class' => 'control-label'),
			'attr' => array('class' => 'form-control')
		));
		
		$builder->add('showFaces', 'yesno_button_group', array(
			'choice_list' => new ChoiceList(array(
				0,
				1
			), array(
					'mautic.core.form.no',
					'mautic.core.form.yes'
				)),
			'label' => 'mautic.integration.Facebook.share.showfaces',
			'data' => (!isset($options['data']['showFaces'])) ? 1 : $options['data']['showFaces']
		));
		
		$builder->add('autoLogout', 'yesno_button_group', array(
			'choice_list' => new ChoiceList(array(
				0,
				1
			), array(
					'mautic.core.form.no',
					'mautic.core.form.yes'
				)),
			'label' => 'mautic.integration.Facebook.login.autologout',
			'data' => (!isset($options['data']['showShare'])) ? 1 : $options['data']['showShare']
		));
		
		/** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
		$integrationHelper = $this->factory->getHelper('integration');
		$integrationObject = $integrationHelper->getIntegrationObject('Facebook');
				
		$keys = $integrationObject->getDecryptedApiKeys();
		$FBclientId = $keys[$integrationObject->getClientIdKey()];
		
		$builder->add('clientId', 'hidden', array(
			'data' => $FBclientId,
		));
		
		$mappedLeadFields = $integrationObject->getAvailableLeadFields();
		$socialFields = '';
		
		foreach ($mappedLeadFields as $key => $field)
		{
			$socialFields .= $key . ",";
		}
		
		$builder->add('socialProfile', 'hidden', array(
			
			'data' => substr($socialFields, 0, -1),
		));
		
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return "sociallogin_facebook";
	}
}