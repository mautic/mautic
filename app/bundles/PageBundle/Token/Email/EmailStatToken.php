<?php

namespace Mautic\PageBundle\Token\Email;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Token\DTO\ClickthroughDTO;

class EmailStatToken
{
    public const FROM_EMAIL_ID_START_REPLACE_TOKENS = 'from_email_id_start_replace_tokens';

    private CoreParametersHelper $coreParametersHelper;

    private EmailModel $emailModel;

    private ?Stat $stat = null;

    public function __construct(EmailModel $emailModel, CoreParametersHelper $coreParametersHelper)
    {
        $this->emailModel           = $emailModel;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function replace(string $clickthrough, string $url)
    {
        if ($this->shouldReplace($clickthrough)) {
            return str_replace(array_keys($this->stat->getTokens()), $this->stat->getTokens(), $url);
        }

        return $url;
    }

    private function shouldReplace(string $clickthrough): bool
    {
        $clickthroughDTO = new ClickthroughDTO($clickthrough);

        if ('email' != $clickthroughDTO->getChannel()) {
            return false;
        }

        if (!$clickthroughDTO->getStat()) {
            return false;
        }

        if (!$this->stat = $this->emailModel->getEmailStatus($clickthroughDTO->getStat())) {
            return false;
        }
        $fromEmailIdStartReplaceTokens = $this->coreParametersHelper->get(self::FROM_EMAIL_ID_START_REPLACE_TOKENS);
        if ($fromEmailIdStartReplaceTokens && !$this->stat->getEmail()) {
            return false;
        }

        if ($fromEmailIdStartReplaceTokens && $this->stat->getEmail()->getId() <= $fromEmailIdStartReplaceTokens) {
            return false;
        }

        return true;
    }
}
