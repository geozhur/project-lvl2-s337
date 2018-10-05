<?php
namespace GenDiff\Parsers;

use Symfony\Component\Yaml\Yaml;

function parse($content, $format)
{
    try {
        switch ($format) {
            case 'json':
                return json_decode($content);
            case 'yaml':
                return Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
            default:
                return;
        }
    } catch (ParseException $e) {
        echo "Error: Unable to parse the string " , $e->getMessage();
    }
}
