<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileProperties;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class MessageSchedule
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FileProperties
     */
    private $fileProperties;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var Router
     */
    private $router;

    public function __construct(
        TranslatorInterface $translator,
        FileProperties $fileProperties,
        CoreParametersHelper $coreParametersHelper,
        Router $router
    ) {
        $this->translator           = $translator;
        $this->fileProperties       = $fileProperties;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->router               = $router;
    }

    public function getMessage(Report $report, $filePath)
    {
        if ($this->fileCouldBeSend($filePath)) {
            $date = new \DateTime();

            return $this->translator->trans(
                'mautic.report.schedule.email.message',
                ['%report_name%' => $report->getName(), '%date%' => $date->format('Y-m-d')]
            );
        }

        $link = $this->router->generate('mautic_report_view', ['objectId' => $report->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->translator->trans(
            'mautic.report.schedule.email.message_file_not_attached',
            ['%report_name%' => $report->getName(), '%link%' => $link]
        );
    }

    public function getSubject(Report $report)
    {
        $date = new \DateTime();

        return $this->translator->trans(
            'mautic.report.schedule.email.subject',
            ['%report_name%' => $report->getName(), '%date%' => $date->format('Y-m-d')]
        );
    }

    public function fileCouldBeSend($filePath)
    {
        $filesize    = $this->fileProperties->getFileSize($filePath);
        $maxFileSize = $this->coreParametersHelper->getParameter('report_export_max_filesize_in_bytes');

        return $filesize <= $maxFileSize;
    }
}
