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
    $services->set('mautic.page.fixture.page_category', Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData::class, 'mautic.page.fixture.page_category');
    $services->set('mautic.page.fixture.page', Mautic\PageBundle\DataFixtures\ORM\LoadPageData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\PageBundle\DataFixtures\ORM\LoadPageData::class, 'mautic.page.fixture.page');
    $services->set('mautic.page.fixture.page_hit', Mautic\PageBundle\DataFixtures\ORM\LoadPageHitData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\PageBundle\DataFixtures\ORM\LoadPageHitData::class, 'mautic.page.fixture.page_hit');
    $services->set('mautic.page.segment_tracking_subscriber', Mautic\PageBundle\EventListener\SegmentTrackingSubscriber::class);
    $services->set('mautic.page.helper.token', Mautic\PageBundle\Helper\TokenHelper::class);
    $services->set('mautic.page.helper.tracking', Mautic\PageBundle\Helper\TrackingHelper::class);

    $services->get(Mautic\PageBundle\Model\PageModel::class)->call('setCatInUrl', ['%mautic.cat_in_page_url%']);
    $services->alias('mautic.page.model.page', Mautic\PageBundle\Model\PageModel::class);
    $services->alias('mautic.page.model.redirect', Mautic\PageBundle\Model\RedirectModel::class);
    $services->alias('mautic.page.model.trackable', Mautic\PageBundle\Model\TrackableModel::class);
    $services->alias('mautic.page.model.video', Mautic\PageBundle\Model\VideoModel::class);
    $services->alias('mautic.page.model.tracking.404', Mautic\PageBundle\Model\Tracking404Model::class);
    $services->alias('mautic.page.repository.hit', Mautic\PageBundle\Entity\HitRepository::class);
    $services->alias('mautic.page.repository.page', Mautic\PageBundle\Entity\PageRepository::class);
    $services->alias('mautic.page.repository.redirect', Mautic\PageBundle\Entity\RedirectRepository::class);
    $services->set(Mautic\PageBundle\Security\Permissions\PagePermissions::class);
};
