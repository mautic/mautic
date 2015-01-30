<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCloudStorageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AmazonS3Type
 */
class AmazonS3Type extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('bucket', 'text', array(
            'label'       => 'mautic.integration.AmazonS3.bucket.path',
            'required'    => false,
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array('class' => 'form-control')
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cloudstorage_amazons3';
    }
}
