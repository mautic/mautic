<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait CitrixRegistrationTrait
{
    /**
     * @param string $product
     * @param Lead   $currentLead
     * @param array  $productsToRegister
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     */
    public function registerProduct($product, $currentLead, array $productsToRegister)
    {
        $leadFields                         = $currentLead->getProfileFields();
        list($email, $firstname, $lastname) = [
            array_key_exists('email', $leadFields) ? $leadFields['email'] : '',
            array_key_exists('firstname', $leadFields) ? $leadFields['firstname'] : '',
            array_key_exists('lastname', $leadFields) ? $leadFields['lastname'] : '',
        ];

        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
            foreach ($productsToRegister as $productToRegister) {
                $productId = $productToRegister['productId'];

                $isRegistered = CitrixHelper::registerToProduct(
                    $product,
                    $productId,
                    $email,
                    $firstname,
                    $lastname
                );
                if ($isRegistered) {
                    $eventName = CitrixHelper::getCleanString(
                            $productToRegister['productTitle']
                        ).'_#'.$productToRegister['productId'];

                    $this->citrixModel->addEvent(
                        $product,
                        $email,
                        $eventName,
                        $productToRegister['productTitle'],
                        CitrixEventTypes::REGISTERED,
                        $currentLead
                    );
                } else {
                    throw new BadRequestHttpException('Unable to register!');
                }
            }
        } else {
            throw new BadRequestHttpException('Mandatory lead fields not found!');
        }
    }
}
