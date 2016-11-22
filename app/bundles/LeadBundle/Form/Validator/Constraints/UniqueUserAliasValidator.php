<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class UniqueUserAliasValidator extends ConstraintValidator
{
    public $em;
    public $currentUser;

    public function __construct(MauticFactory $factory)
    {
        $this->em          = $factory->getEntityManager();
        $this->currentUser = $factory->getUser();
    }

    public function validate($list, Constraint $constraint)
    {
        $field = $constraint->field;

        if (empty($field)) {
            throw new ConstraintDefinitionException('A field has to be specified.');
        }

        if ($list->getAlias()) {
            $lists = $this->em->getRepository('MauticLeadBundle:LeadList')->getLists(
                $this->currentUser,
                $list->getAlias(),
                $list->getId()
            );

            if (count($lists)) {
                $this->context->addViolationAt(
                    $field,
                    $constraint->message,
                    ['%alias%' => $list->getAlias()]
                );
            }
        }
    }
}
