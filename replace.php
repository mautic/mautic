<?php

class finder
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param string $directory
     *
     * @return SplFileInfo[]
     */
    public function searchAndReplace(bool $dryRun=true): string
    {
        $result   = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path), RecursiveIteratorIterator::SELF_FIRST);
        /** @var $fileInfo SplFileInfo */
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                if (!$fileContent = file_get_contents($fileInfo->getPathName())) {
                    echo 'Error reading file: '.$fileInfo->getPathName()."\n";
                }

                $updated = $this->replace($fileContent, $fileInfo, $dryRun);
                if ($updated) {
                    $result[] = $fileInfo->getPathName();
                }
            }
        }

        if (count($result) > 0) {
            $this->searchAndReplace($dryRun);
        }

        return 'All files have been updated';
    }

    public function replace(string &$fileContent, SplFileInfo $fileInfo, bool $dryRun=true): bool
    {
        //Make sure the file has .html.twig within its contents, if not skip it
        if (false === strpos($fileContent, '.html.twig')) {
            return false;
        }

        if (preg_match_all("/'[^']*:[^']*:[^']*.twig'/", $fileContent, $keys, PREG_PATTERN_ORDER)) {
            foreach ($keys[0] as $key) {
                $replace = str_replace('Bundle', '', $key);
                $replace = str_replace('Mautic', '@Mautic', $replace);
                $replace = str_replace(':', '/', $replace);
                if ($dryRun) {
                    return true;
                } else {
                    $content = str_replace($key, $replace, $fileContent);

                    if (!file_put_contents($fileInfo->getPathName(), $content)) {
                        echo 'Error writing file: '.$fileInfo->getPathName()."\n";
                        $result[] = $fileInfo->getPathName();
                    }

                    return true;
                }
            }
        } else {
            return false;
        }
    }
}

$finder  = new finder('/var/www/html/app/bundles/DashboardBundle');
$results = $finder->searchAndReplace(false);
echo $results."\n";
