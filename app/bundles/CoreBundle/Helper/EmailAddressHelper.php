<?php

namespace Mautic\CoreBundle\Helper;

class EmailAddressHelper
{
    /**
     * Clean the email for comparison.
     *
     * @param string $email
     */
    public function cleanEmail($email): string
    {
        return strtolower(preg_replace("/[^a-z0-9\+\.@]/i", '', $email));
    }

    /**
     * @return array<string>
     */
    public function getVariations(string $email): array
    {
        $emails = [$email, $this->cleanEmail($email)];
        // email without suffix
        preg_match('#^(.*?)\+(.*?)@(.*?)$#', $email, $parts);
        if (!empty($parts)) {
            $emails[] = $parts[1].'@'.$parts[3];
        }

        return array_values(array_unique($emails));
    }
}
