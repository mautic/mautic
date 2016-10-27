<?php
/**
 * Created by PhpStorm.
 * User: Werner
 * Date: 10/26/2016
 * Time: 12:44 PM
 */

namespace MauticPlugin\MauticCitrixBundle\Helper;


use HttpException;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;

trait CitrixStartTrait
{

    /**
     * @param string $product
     * @param Lead $lead
     * @param array $productsToStart
     * @param  $emailId
     * @param  $actionId
     * @throws \HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public static function startProduct($product, $lead, array $productsToStart, $emailId = null, $actionId = null)
    {
        $leadFields = $lead->getProfileFields();
        list($email, $firstname, $lastname) = [
            array_key_exists('email', $leadFields) ? $leadFields['email'] : '',
            array_key_exists('firstname', $leadFields) ? $leadFields['firstname'] : '',
            array_key_exists('lastname', $leadFields) ? $leadFields['lastname'] : '',
        ];

        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
            foreach ($productsToStart as $productToStart) {
                $productId = $productToStart['productId'];

                $hostUrl = CitrixHelper::startProduct(
                    $product,
                    $productId,
                    $email,
                    $firstname,
                    $lastname
                );

                if ('' !== $hostUrl) {
                    // send email using template from form action properties
                    // and replace the tokens in the body with the hostUrl
                    $container = CitrixHelper::getContainer();
                    $translator = $container->get('translator');
                    /** @var ModelFactory $factory */
                    $factory = $container->get('mautic.model.factory');

                    /** @var \Mautic\EmailBundle\Model\EmailModel $model */
                    $model = $factory->getModel('email');
                    $emailEntity = $model->getEntity($emailId);

                    //make sure the email still exists and is published
                    if (null !== $emailEntity && $emailEntity->isPublished()) {
                        $content = $emailEntity->getCustomHtml();
                        // replace tokens
                        if (CitrixHelper::isAuthorized('Goto'.$product)) {
                            $params = [
                                'product' => $product,
                                'productLink' => $hostUrl,
                                'productText' =>
                                    sprintf($translator->trans('plugin.citrix.start.producttext'), ucfirst($product)),
                            ];

                            $button = $container->get('templating')->render(
                                'MauticCitrixBundle:SubscribedEvents\EmailToken:token.html.php',
                                $params
                            );
                            $content = str_replace('{'.$product.'_button}', $button, $content);
                        } else {
                            // remove the token
                            $content = str_replace('{'.$product.'_button}', '', $content);
                        }

                        // set up email data
                        $emailEntity->setCustomHtml($content);
                        $leadFields['id'] = $lead->getId();
                        $options = ['source' => ['trigger', $actionId]];
                        $model->sendEmail($emailEntity, $leadFields, $options);
                    } else {
                        throw new HttpException('Unable to load emal template!');
                    }

                    // add event to DB
                    $eventName = CitrixHelper::getCleanString(
                            $productToStart['productTitle']
                        ).'_#'.$productToStart['productId'];
                    /** @var CitrixModel $citrixModel */
                    $citrixModel = $factory->getModel('citrix.citrix');

                    $citrixModel->addEvent(
                        $product,
                        $email,
                        $eventName,
                        CitrixEventTypes::STARTED
                    );
                } else {
                    throw new HttpException('Unable to start!');
                }
            }
        } else {
            throw new HttpException('Mandatory lead fields not found!');
        }
    }

}