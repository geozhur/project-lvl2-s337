<?php
namespace GenDiff\Parse;

use Symfony\Component\Yaml\Yaml;

function parse($file, $format = '')
{
    $content = file_get_contents($file);
    if (!$content) {
        return;
    }
    switch ($format) {
        case 'json':
            return json_decode($content);
        case 'yaml':
            return Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
        default:
            $json = json_decode($content);
            return $json ? $json : Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
    }
}
