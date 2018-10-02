<?php
namespace GenDiff\Json;

use GenDiff\Common;

function getJSONContents($pathToFile)
{
    return json_decode(file_get_contents($pathToFile));
}

function genDiff($pathToFile1, $pathToFile2)
{
    return(\GenDiff\Common\checkFileExtAndDiff($pathToFile1, $pathToFile2));
}
