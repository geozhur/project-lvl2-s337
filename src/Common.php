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

function toStr($spaces, $elem, $key, $value)
{
    return sprintf("%{$spaces}s %s: %s", $elem, $key, $value);
}

function genDiff($content1, $content2, $level)
{
    $contentArr1 = get_object_vars($content1);
    $contentKeys1 = array_keys($contentArr1);
    $contentArr2 = get_object_vars($content2);
    $contentKeys2 = array_keys($contentArr2);
    $spaces = $level*4-1;
    $result1 = Collection\flattenAll(array_map(function ($item) use ($contentArr1, $contentArr2, $level, $spaces) {
        $value1 = $contentArr1[$item];
        if (array_key_exists($item, $contentArr2)) {
            $value2 = $contentArr2[$item];
            if (is_object($value1)) {
                return toStr($spaces, '', $item, genDiff($value1, $value2, $level+1));
            } else {
                if ($value1 == $value2) {
                        return toStr($spaces, '', $item, encode($value1));
                } else {
                        return [toStr($spaces, '-', $item, encode($value1)),
                                toStr($spaces, '+', $item, encode($value2))];
                }
            }
        } else {
            if (!is_object($value1)) {
                return toStr($spaces, '-', $item, encode($value1));
            } else {
                return toStr($spaces, '-', $item, genDiff($value1, $value1, $level+1));
            }
        }
    }, $contentKeys1));

    $result2 = array_map(function ($item) use ($contentArr2, $spaces, $level) {
        $value3 = $contentArr2[$item];
        if (!is_object($value3)) {
            return toStr($spaces, '+', $item, encode($value3));
        } else {
            return toStr($spaces, '+', $item, genDiff($value3, $value3, $level+1));
        }
    }, array_diff($contentKeys2, $contentKeys1));

    $result = implode("\n", array_merge($result1, $result2));
    $spaces2 = $spaces - 2;
    return  sprintf("%s\n%s\n%{$spaces2}s", '{', $result, '}');
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
