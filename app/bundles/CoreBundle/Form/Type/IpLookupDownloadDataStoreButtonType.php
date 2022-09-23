<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class IpLookupDownloadDataStoreButtonType extends AbstractType
{
    /**
     * @var DateHelper
     */
    private $dateHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * IpLookupDownloadDataStoreButtonType constructor.
     */
    public function __construct(DateHelper $dateHelper, TranslatorInterface $translator)
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
            ButtonType::class,
            [
                'label' => ($localDataExists) ? 'mautic.core.ip_lookup.update_data' : 'mautic.core.ip_lookup.fetch_data',
                'attr'  => [
                    'class'   => 'btn btn-'.($localDataExists ? 'success' : 'danger'),
                    'onclick' => 'Mautic.downloadIpLookupDataStore()',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['ip_lookup_service' => null]);
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
                    ['%date%' => $lastModified]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'iplookup_download_data_store_button';
    }
}
