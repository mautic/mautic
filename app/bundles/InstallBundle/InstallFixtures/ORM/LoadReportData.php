<?php

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\ReportBundle\Entity\Report;

final class LoadReportData extends Fixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['group_install', 'group_mautic_install_data'];
    }

    public function load(ObjectManager $manager): void
    {
        $reports = CsvHelper::csv_to_array(__DIR__.'/fakereportdata.csv');
        foreach ($reports as $count => $rows) {
            $report = new Report();
            $key    = $count + 1;
            foreach ($rows as $col => $val) {
                if ('NULL' != $val) {
                    $setter = 'set'.ucfirst($col);
                    if (in_array($col, ['columns', 'filters', 'graphs', 'tableOrder'])) {
                        $val = Serializer::decode(stripslashes($val));
                    }
                    $report->{$setter}($val);
                }
            }

            $manager->persist($report);

            $this->setReference('report-'.$key, $report);
        }
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 5;
    }
}
