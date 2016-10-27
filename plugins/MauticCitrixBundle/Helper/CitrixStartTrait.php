<?php
/**
 * Created by PhpStorm.
 * User: Werner
 * Date: 10/26/2016
 * Time: 12:44 PM
 */

namespace MauticPlugin\MauticCitrixBundle\Helper;


use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;

trait CitrixStartTrait
{

    /**
     * @param string $product
     * @param Lead $currentLead
     * @param array $productsToStart
     * @throws \HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     */
    public static function startProduct($product, $currentLead, array $productsToStart)
    {
        $leadFields = $currentLead->getProfileFields();
        list($email, $firstname, $lastname) = [
            array_key_exists('email', $leadFields) ? $leadFields['email'] : '',
            array_key_exists('firstname', $leadFields) ? $leadFields['firstname'] : '',
            array_key_exists('lastname', $leadFields) ? $leadFields['lastname'] : '',
        ];

        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
            foreach ($productsToStart as $productToRegister) {
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
                    /** @var CitrixModel $citrixModel */
                    $citrixModel = CitrixHelper::getContainer()
                        ->get('mautic.model.factory')
                        ->getModel('citrix.citrix');

                    $citrixModel->addEvent(
                        $product,
                        $email,
                        $eventName,
                        CitrixEventTypes::REGISTERED
                    );
                } else {
                    throw new \HttpException('Unable to register!');
                }
            }
        } else {
            throw new \HttpException('Mandatory lead fields not found!');
        }
    }

}