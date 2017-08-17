<?php

/*
 * @author      Captivea (QCH)
 */

namespace Mautic\ScoringBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ScoringPermissions.
 */
class ScoringPermissions extends \Mautic\PointBundle\Security\Permissions\PointPermissions
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
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        parent::buildForm($builder, $options, $data);
        $this->addStandardFormFields('point', 'scoringCategory', $builder, $data);
    }
}
