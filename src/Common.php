<?php
namespace GenDiff\Common;

use \Funct\Collection;

function encode($data)
{
    switch (gettype($data)) {
        case 'NULL':
            return 'null';
        case 'boolean':
            return ($data ? 'true' : 'false');
        default:
            return $data;
    }
}

function genDiff($content1, $content2, $level)
{
    $contentArr1 = get_object_vars($content1);
    $contentKeys1 = array_keys($contentArr1);
    $contentArr2 = get_object_vars($content2);
    $contentKeys2 = array_keys($contentArr2);
    $spases = $level*4-1;
    $result1 = Collection\flattenAll(array_map(function ($item) use ($contentArr1, $contentArr2, $level, $spases) {

        if (array_key_exists($item, $contentArr2)) {
            if (is_object($contentArr1[$item])) {
                return sprintf(
                    "%{$spases}s %s: %s",
                    '',
                    $item,
                    genDiff($contentArr1[$item], $contentArr2[$item], $level+1)
                );
            } else {
                if ($contentArr1[$item] == $contentArr2[$item]) {
                        return sprintf("%{$spases}s %s: %s", '', $item, encode($contentArr1[$item]));
                } else {
                        return [sprintf("%{$spases}s %s: %s", '-', $item, encode($contentArr1[$item])),
                                sprintf("%{$spases}s %s: %s", '+', $item, encode($contentArr2[$item]))];
                }
            }
        } else {
            if (!is_object($contentArr1[$item])) {
                return sprintf("%{$spases}s %s: %s", '-', $item, encode($contentArr1[$item]));
            } else {
                return sprintf(
                    "%{$spases}s %s: %s",
                    '-',
                    $item,
                    genDiff($contentArr1[$item], $contentArr1[$item], $level+1)
                );
            }
        }
    }, $contentKeys1));

    $result2 = array_map(function ($item) use ($contentArr2, $spases, $level) {
        if (!is_object($contentArr2[$item])) {
            return sprintf("%{$spases}s %s: %s", '+', $item, encode($contentArr2[$item]));
        } else {
            return sprintf(
                "%{$spases}s %s: %s",
                '+',
                $item,
                genDiff($contentArr2[$item], $contentArr2[$item], $level+1)
            );
        }
    }, array_diff($contentKeys2, $contentKeys1));

    $result = implode("\n", array_merge($result1, $result2));
    $spases2 = $spases - 2;
    return  sprintf("%s\n%s\n%{$spases2}s", '{', $result, '}');
}

function getContentForExt($file)
{
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'json':
            return \GenDiff\Json\getJSONContents($file);
        case 'yaml':
            return \GenDiff\Yaml\getYamlContents($file);
    }

    return getJSONContents($file);
}


function checkFileExtAndDiff($pathToFile1, $pathToFile2)
{
    return(\GenDiff\Common\genDiff(getContentForExt($pathToFile1), getContentForExt($pathToFile2), 1));
}
