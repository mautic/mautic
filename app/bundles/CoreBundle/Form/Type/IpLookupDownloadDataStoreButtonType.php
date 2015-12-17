<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class IpLookupDownloadDataStoreButtonType extends AbstractType
{
    /**
     * @var DateHelper
     */
    private $dateHelper;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(DateHelper $dateHelper, Translator $translator)
    {
        $this->dateHelper = $dateHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $localDataExists = file_exists($options['ip_lookup_service']->getLocalDataStoreFilepath());

        $builder->add(
            'fetch_button',
            'button',
            array(
                'label' => ($localDataExists) ? 'mautic.core.ip_lookup.update_data' : 'mautic.core.ip_lookup.fetch_data',
                'attr' => array(
                    'class'   => 'btn btn-'.($localDataExists ? 'success' : 'danger'),
                    'onclick' => 'Mautic.downloadIpLookupDataStore()',
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'ip_lookup_service' => null
        ));
    }


    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (null !== $options['ip_lookup_service'] && $options['ip_lookup_service'] instanceof AbstractLocalDataLookup) {
            $localFilePath   = $options['ip_lookup_service']->getLocalDataStoreFilepath();
            $localDataExists = file_exists($localFilePath);
            if ($localDataExists && $lastModifiedTimestamp = filemtime($localFilePath)) {
                $lastModified                            = $this->dateHelper->toText($lastModifiedTimestamp, 'UTC', 'U');
                $view->vars['ipDataStoreLastDownloaded'] = $this->translator->trans(
                    'mautic.core.ip_lookup.last_updated',
                    array('%date%' => $lastModified)
                );
            }
        }
    }

    public function getName()
    {
        return 'iplookup_download_data_store_button';
    }
}