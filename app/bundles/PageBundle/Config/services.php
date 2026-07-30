<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
    ];

    $services->load('Mautic\\PageBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\PageBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->set(Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->set(Mautic\PageBundle\DataFixtures\ORM\LoadPageData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->set(Mautic\PageBundle\DataFixtures\ORM\LoadPageHitData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->set(Mautic\PageBundle\EventListener\SegmentTrackingSubscriber::class);
    $services->set(Mautic\PageBundle\Helper\TokenHelper::class);
    $services->set(Mautic\PageBundle\Helper\TrackingHelper::class);

    $services->get(Mautic\PageBundle\Model\PageModel::class)->call('setCatInUrl', ['%mautic.cat_in_page_url%']);
    $services->alias('mautic.page.model.page', Mautic\PageBundle\Model\PageModel::class);
    $services->alias('mautic.page.model.redirect', Mautic\PageBundle\Model\RedirectModel::class);
    $services->alias('mautic.page.model.trackable', Mautic\PageBundle\Model\TrackableModel::class);
    $services->alias('mautic.page.model.video', Mautic\PageBundle\Model\VideoModel::class);
};
