<?php
namespace GenDiff\Content;

use Symfony\Component\Yaml\Yaml;

function parse($content, $format)
{
    switch ($format) {
        case 'json':
            return json_decode($content);
        case 'yaml':
            return Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
        default:
            return;
    }
}
