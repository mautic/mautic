<?php

if ($argc != 2) {
    echo 'You need to supply filename';
    exit(1);
}

if (!file_exists($filename = $argv[1])) {
    echo 'File not found.';
    exit(1);
}

$xml        = simplexml_load_file($filename);
$headerSent = false;
$rows       = [];

foreach ($xml->children() as $node) {
    $response = $node->responseData->__toString();

    $matches = null;

    $check = preg_match("/\[Select Statement\] select ([0-9]+) as id, ([0-9]+) as version from \((.*)\)/", $node->samplerData->__toString(), $matches);

    if (!$check) {
        throw new \Exception('Invalid data');
    }
    $attributes = $node->attributes();

    if (isset($rows[$matches[1]][$matches[2]])) {
        $imSum                          = ($rows[$matches[1]][$matches[2]] + $attributes['t']->__toString()) / (count($attributes['t']->__toString()) + 1);
        $rows[$matches[1]][$matches[2]] = $imSum;
    }
    $rows[$matches[1]][$matches[2]] = $attributes['t']->__toString();
}

foreach ($rows as $segmentId=>$times) {
    if (isset($times[1]) && isset($times[2])) {
        printf("%d;%s;%s\n", $segmentId, $times[1], $times[2]);
    }
}
