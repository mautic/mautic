<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;

/**
 * Builder images are uploaded into the media images directory and referenced by absolute URL inside the
 * email HTML and builder JSON. This helper bridges export and import of those images:
 *
 *  - on export it resolves the references to the local files so they can be packed into the archive;
 *  - on import it makes the packed files available again under the media images directory and rewrites the
 *    references to host-relative URLs so they resolve on any instance.
 *
 * The export/import plumbing restores archived assets into the media files directory, which is deny-all and
 * therefore cannot be served to the browser; the builder serves images from the media images directory, so
 * on import the files are relocated there.
 */
final readonly class EmailMediaImageHelper
{
    // The builder emits URLs under "media/images" regardless of a remote instance's configured image_path,
    // so this segment is what we detect in incoming content to stay compatible across installations.
    private const SOURCE_IMAGE_SEGMENT = 'media/images';

    private const IMAGE_EXTENSIONS = 'png|jpe?g|gif|webp|svg';

    public function __construct(
        private PathsHelper $pathsHelper,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * Resolves builder image references in the given content to existing local files under the media images
     * directory, guarding against path traversal. Used to collect files for the export archive.
     *
     * @return list<string> absolute file paths
     */
    public function extractLocalImageFiles(string $content): array
    {
        $imageDir     = rtrim($this->pathsHelper->getImagePath(), '/');
        $realImageDir = realpath($imageDir);

        if (false === $realImageDir || !preg_match_all($this->pattern(), $content, $matches)) {
            return [];
        }

        $files = [];
        foreach (array_unique($matches['path']) as $relativePath) {
            $candidate = realpath($imageDir.'/'.ltrim($relativePath, '/'));
            if (false !== $candidate && is_file($candidate) && str_starts_with($candidate, $realImageDir.'/')) {
                $files[$candidate] = $candidate;
            }
        }

        return array_values($files);
    }

    /**
     * Rewrites builder image references in an HTML string to host-relative URLs and ensures each referenced
     * file is available under the media images directory.
     */
    public function restoreInHtml(string $html): string
    {
        return preg_replace_callback(
            $this->pattern(),
            fn (array $matches): string => $this->restoreReference($matches['path']),
            $html,
        ) ?? $html;
    }

    /**
     * Recursively rewrites builder image references inside the structured builder content.
     *
     * @param array<mixed, mixed> $content
     *
     * @return array<mixed, mixed>
     */
    public function restoreInContent(array $content): array
    {
        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $content[$key] = $this->restoreInHtml($value);
            } elseif (is_array($value)) {
                $content[$key] = $this->restoreInContent($value);
            }
        }

        return $content;
    }

    private function restoreReference(string $relativePath): string
    {
        // Archived images are flattened to their basename, so that is where they land on restore and where
        // the rewritten URL must point.
        $basename    = basename($relativePath);
        $imageDir    = rtrim($this->pathsHelper->getImagePath(), '/');
        $destination = $imageDir.'/'.$basename;

        if (!is_file($destination)) {
            $restored = rtrim($this->pathsHelper->getMediaPath(), '/').'/files/'.$basename;
            if (is_file($restored)) {
                @copy($restored, $destination);
            }
        }

        return '/'.$this->imagePathSegment().'/'.$basename;
    }

    private function imagePathSegment(): string
    {
        return trim((string) $this->coreParametersHelper->get('image_path', self::SOURCE_IMAGE_SEGMENT), '/');
    }

    private function pattern(): string
    {
        return '#[^\s"\'()<>]*?'.self::SOURCE_IMAGE_SEGMENT.'/(?<path>[\w\-./]+\.(?:'.self::IMAGE_EXTENSIONS.'))#i';
    }
}
