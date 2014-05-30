<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class UniqueUserAliasValidator extends ConstraintValidator
{

    public $em;
    public $currentUser;

    public function __construct(EntityManager $em, SecurityContext $securityContext)
    {
        $this->em          = $em;
        $this->currentUser = $securityContext->getToken()->getUser();
    }

    public function validate($list, Constraint $constraint)
    {
        $field = $constraint->field;

        if (empty($field)) {
            throw new ConstraintDefinitionException('A field has to be specified.');
        }

        if ($list->getAlias()) {
            $lists = $this->em->getRepository('MauticLeadBundle:LeadList')->getUserSmartLists(
                $this->currentUser,
                $list->getAlias(),
                $list->getId()
            );

            if (count($lists)) {
                $this->context->addViolationAt(
                    $field,
                    $constraint->message,
                    array('%alias%' => $list->getAlias())
                );
            }
        }
    }
}