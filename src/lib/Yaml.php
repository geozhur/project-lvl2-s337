<?php
namespace GenDiff\Yaml;

use Symfony\Component\Yaml\Yaml;

function getYamlContents($pathToFile)
{
    return Yaml::parseFile($pathToFile, Yaml::PARSE_OBJECT_FOR_MAP);
}

function genDiff($pathToFile1, $pathToFile2)
{
    return(\GenDiff\Common\checkFileExtAndDiff($pathToFile1, $pathToFile2));
}
