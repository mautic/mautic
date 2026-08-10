# Backwards compatibility breaking changes

## Platform requirements
- The minimum required PHP version has been increased from **8.2** to **8.4**.

## Removed code
<<<<<<< HEAD

- Deprecated method `Mautic\LeadBundle\Model\LeadModel::isContactable()` removed. Use `Mautic\LeadBundle\Model\DoNotContact::isContactable()` instead.
=======
- Deprecated class `Mautic\CoreBundle\Helper\EmojiMap\HtmlToUnicodeEmojiMap` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Helper\EmojiMap\ShortToUnicodeEmojiMap` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Helper\EmojiMap\UnicodeToHtmlEmojiMap` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Helper\EmojiMap\UnicodeToShortEmojiMap` removed with no replacement.
>>>>>>> 63caa8d508 ([removal] Document *EmojiMap class removal in UPGRADE-8.0)
