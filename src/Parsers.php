<?php
namespace GenDiff\Parsers;

use Symfony\Component\Yaml\Yaml;
use Exception;

function parse($content, $format)
{
    switch ($format) {
        case 'json':
            return json_decode($content);
        case 'yaml':
            return Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
        default:
            throw new \Exception("Unable parse to string\n");
    }
}
