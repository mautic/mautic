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

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait CitrixStartTrait
{
    /**
     * @var EmailModel
     */
    protected $emailModel;

    /**
     * @param EmailModel $emailModel
     */
    public function setEmailModel(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;
    }

    /**
     * @param string $product
     * @param Lead   $lead
     * @param array  $productsToStart
     * @param  $emailId
     * @param  $actionId
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function startProduct($product, $lead, array $productsToStart, $emailId = null, $actionId = null)
    {
        $leadFields                         = $lead->getProfileFields();
        list($email, $firstname, $lastname) = [
            array_key_exists('email', $leadFields) ? $leadFields['email'] : '',
            array_key_exists('firstname', $leadFields) ? $leadFields['firstname'] : '',
            array_key_exists('lastname', $leadFields) ? $leadFields['lastname'] : '',
        ];

        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
            foreach ($productsToStart as $productToStart) {
                $productId = $productToStart['productId'];

                $hostUrl = CitrixHelper::startToProduct(
                    $product,
                    $productId,
                    $email,
                    $firstname,
                    $lastname
                );

                if ('' !== $hostUrl) {
                    // send email using template from form action properties
                    // and replace the tokens in the body with the hostUrl

                    $emailEntity = $this->emailModel->getEntity($emailId);

                    //make sure the email still exists and is published
                    if (null !== $emailEntity && $emailEntity->isPublished()) {
                        $content = $emailEntity->getCustomHtml();
                        // replace tokens
                        if (CitrixHelper::isAuthorized('Goto'.$product)) {
                            $params = [
                                'product'     => $product,
                                'productLink' => $hostUrl,
                                'productText' => sprintf($this->translator->trans('plugin.citrix.start.producttext'), ucfirst($product)),
                            ];

                            $button = $this->templating->render(
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
                        $options          = ['source' => ['trigger', $actionId]];
                        $this->emailModel->sendEmail($emailEntity, $leadFields, $options);
                    } else {
                        throw new BadRequestHttpException('Unable to load emal template!');
                    }

                    // add event to DB
                    $eventName = CitrixHelper::getCleanString(
                            $productToStart['productTitle']
                        ).'_#'.$productToStart['productId'];

                    $this->citrixModel->addEvent(
                        $product,
                        $email,
                        $eventName,
                        $productToStart['productTitle'],
                        CitrixEventTypes::STARTED,
                        $lead
                    );
                } else {
                    throw new BadRequestHttpException('Unable to start!');
                }
            }
        } else {
            throw new BadRequestHttpException('Mandatory lead fields not found!');
        }
    }
}
