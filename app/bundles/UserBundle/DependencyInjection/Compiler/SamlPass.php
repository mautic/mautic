<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\DependencyInjection\Compiler;

use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Meta\TrustOptions\TrustOptions;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Store\Credential\StaticCredentialStore;
use LightSaml\Store\TrustOptions\FixedTrustOptionsStore;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;

class SamlPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($xml = $container->getParameter('mautic.saml_idp_metadata')) {
            $certificateContent = $container->getParameter('mautic.saml_idp_own_certificate');
            $privateKeyContent  = $container->getParameter('mautic.saml_idp_own_private_key');
            $keyPassword        = $container->getParameter('mautic.saml_idp_own_password');
            $usingDefaults      = false;
            if (!$certificateContent) {
                $usingDefaults      = true;
                $appPath            = $container->getParameter('kernel.root_dir');
                $certificateContent = file_get_contents($appPath.'/../vendor/lightsaml/lightsaml/web/sp/saml.crt');
                $privateKeyContent  = file_get_contents($appPath.'/../vendor/lightsaml/lightsaml/web/sp/saml.key');
                $keyPassword        = '';
            } else {
                $certificateContent = base64_decode($certificateContent);
                $privateKeyContent  = base64_decode($privateKeyContent);
            }

            $certDefId             = 'mautic.security.saml.own.credential_cert';
            $certificateDefinition = (new Definition(X509Certificate::class))
                ->addMethodCall('loadPem', [$certificateContent]);
            $container->setDefinition($certDefId, $certificateDefinition);

            $privKeyDefId         = 'mautic.security.saml.own.credential_private_key';
            $privateKeyDefinition = (new Definition('LightSaml\Credential\KeyHelper'))
                ->setFactory('LightSaml\Credential\KeyHelper::createPrivateKey')
                ->setArguments(
                    [
                        $privateKeyContent,
                        $keyPassword,
                        false,
                        new Expression('service("'.$certDefId.'").getSignatureAlgorithm()'),
                    ]
                );
            $container->setDefinition($privKeyDefId, $privateKeyDefinition);

            $credId                = 'mautic.security.saml.own.credentials';
            $credentialsDefinition = (new Definition(
                X509Credential::class,
                [
                    new Reference($certDefId),
                    new Reference($privKeyDefId),
                ]
            ))
                ->addMethodCall('setEntityId', ['%mautic.saml_idp_entity_id%']);
            $container->setDefinition($credId, $credentialsDefinition);

            $credentialStore = (new Definition(StaticCredentialStore::class))
                ->addMethodCall('add', [new Reference($credId)])
                ->addTag('lightsaml.own_credential_store');
            $container->setDefinition('mautic.security.saml.own.credential_store', $credentialStore);

            if (!$usingDefaults) {
                $trustId                = 'mautic.security.saml.trust_options';
                $trustOptionsDefinition = (new Definition(TrustOptions::class))
                    ->setMethodCalls(
                        [
                            ['setSignAuthnRequest', [true]],
                            ['setEncryptAssertions', [true]],
                            ['setEncryptAuthnRequest', [true]],
                            ['setSignAssertions', [true]],
                            ['setSignResponse', [true]],
                        ]
                    );
                $container->setDefinition($trustId, $trustOptionsDefinition);

                $trustOptionStoresDefinition = (new Definition(FixedTrustOptionsStore::class, [new Reference($trustId)]))
                    ->addTag('lightsaml.trust_options_store');
                $container->setDefinition('mautic.security.saml.trust_options_store', $trustOptionStoresDefinition);
            }

            // Create the entity descriptor
            $id                         = 'mautic.security.saml.idp_entity_descriptor';
            $xml                        = base64_decode($xml);
            $entityDescriptorDefinition = (new Definition(EntityDescriptor::class))
                ->setFactory(EntityDescriptor::class.'::loadXml')
                ->addArgument($xml);
            $container->setDefinition($id, $entityDescriptorDefinition);

            // Create the entity descriptor store
            $definition = new Definition('LightSaml\Store\EntityDescriptor\FixedEntityDescriptorStore');
            $definition->addTag('lightsaml.idp_entity_store')
                       ->addMethodCall('add', [new Reference($id)]);
            $container->setDefinition('mautic.security.saml.idp_entity_descriptor_store.xml', $definition);

            $container->getDefinition('lightsaml_sp.username_mapper.simple')
                      ->setClass('Mautic\UserBundle\Security\User\UserMapper');
        }
    }
}
