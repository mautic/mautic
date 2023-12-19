<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class IpLookupDownloadDataStoreButtonType extends AbstractType
{
    public function __construct(
        private DateHelper $dateHelper,
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['ip_lookup_service' => null]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
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

    public function getBlockPrefix()
    {
        return 'iplookup_download_data_store_button';
    }
}
