<?php
namespace GenDiff\Differ;

use \Funct\Collection;
use Exception;

class NotOpenFileException extends \Exception
{
};

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

function node($type, $key, $value, $children = '', $newValue = '')
{
        return (object)['key' => $key,
                        'type' => $type,
                        'value' => $value,
                        'newValue' => $newValue,
                        'children' => $children];
}

function genAstDiff($content1, $content2)
{
    $arr1 = get_object_vars($content1);
    $arr2 = get_object_vars($content2);
    $keys1 = array_keys($arr1);
    $keys2 = array_keys($arr2);
    $keys = array_unique(array_merge($keys1, $keys2));

    $result = Collection\flattenAll(array_map(function ($key) use ($arr1, $arr2) {
        
        $keyInArr1 = array_key_exists($key, $arr1);
        $keyInArr2 = array_key_exists($key, $arr2);
        if ($keyInArr1 && $keyInArr2) {
            if (is_object($arr1[$key]) && is_object($arr2[$key])) {
                return node('node', $key, '', genAstDiff($arr1[$key], $arr2[$key]));
            }
            if ($arr1[$key] === $arr2[$key]) {
                return node('notChanged', $key, encode($arr1[$key]));
            }
            if ($arr1[$key]!== $arr2[$key]) {
                return node('changed', $key, encode($arr1[$key]), '', encode($arr2[$key]));
            }
        }
        if ($keyInArr1 && !$keyInArr2) {
            if (!is_object($arr1[$key])) {
                return node('removed', $key, encode($arr1[$key]));
            } else {
                return node('removed', $key, '', genAstDiff($arr1[$key], $arr1[$key]));
            }
        }
        if (!$keyInArr1 && $keyInArr2) {
            if (!is_object($arr2[$key])) {
                return node('add', $key, encode($arr2[$key]));
            } else {
                return node('add', $key, '', genAstDiff($arr2[$key], $arr2[$key]));
            }
        }
    }, $keys));

    return  $result;
}

function genDiff($pathToFile1, $pathToFile2, $format = 'pretty')
{
    $content1 = file_get_contents($pathToFile1);
    $content2 = file_get_contents($pathToFile2);

    if (!$content2 || !$content1) {
        throw new \Exception("Unable to open file\n");
    }

    $extension1 = pathinfo($pathToFile1, PATHINFO_EXTENSION);
    $extension2 = pathinfo($pathToFile2, PATHINFO_EXTENSION);

    $contentForExt1 = \GenDiff\Parsers\parse($content1, $extension1);
    $contentForExt2 = \GenDiff\Parsers\parse($content2, $extension2);

    $astDiff = genAstDiff($contentForExt1, $contentForExt2);

    $render = "\\Formatters\\".ucfirst($format)."\\render";

    if (function_exists($render)) {
        return $render($astDiff);
    } else {
        return \Formatters\Tree\render($astDiff);
    }
}
