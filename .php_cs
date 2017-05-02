<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__.'/app')
    ->exclude(__DIR__.'/app/cache')
    ->exclude(__DIR__.'/app/logs')
    ->exclude(__DIR__.'/app/Resources')
    ->in(__DIR__.'/plugins');

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->finder($finder)
    ->fixers([
        'align_double_arrow',
        'align_equals',
        'ordered_use',
        'short_array_syntax',
    ]);
