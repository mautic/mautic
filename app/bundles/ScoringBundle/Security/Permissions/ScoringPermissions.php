<?php

/*
 * @author      Captivea (QCH)
 */

namespace Mautic\ScoringBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ScoringPermissions.
 */
class ScoringPermissions extends AbstractPermissions
{
    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->addStandardPermissions('scoringCategory');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'scoring';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data) {
        $this->addStandardFormFields('scoring', 'scoringCategory', $builder, $data);
    }
}
