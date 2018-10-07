<?php
namespace GenDiff\Differ;

use \Funct\Collection;
use Exception;

function encode($data, $quotes = false)
{
    if (!$quotes) {
        return trim(json_encode($data), '" ');
    } else {
        return json_encode($data);
    }
}

function stringify($obj, $spaces, $quotes = false)
{
    if (!is_object($obj)) {
        return encode($obj, $quotes);
    }

    $stringifyIter = function ($obj, $spaces) use (&$stringifyIter, $quotes) {
        $arr = get_object_vars($obj);
        $keys = array_keys($arr);

        $result = array_map(function ($elem) use ($spaces, $arr, $quotes) {
            if (is_object($arr[$elem])) {
                $tree = $stringifyIter($arr[$elem], "    {$spaces}");
                return "{$spaces}    {$key}: {$tree}";
            }
            $value = encode($arr[$elem], $quotes);
            $key = encode($elem, $quotes);
            return "{$spaces}    {$key}: {$value}";
        }, $keys);
        return implode("\n", array_merge(['{'], $result, ["{$spaces}}"]));
    };
    return $stringifyIter($obj, $spaces);
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

        if (!empty($arr1[$key]) && !empty($arr2[$key]) && is_object($arr1[$key]) && is_object($arr2[$key])) {
            return (object)['key' => $key,
                            'type' => 'node',
                            'oldValue' => "",
                            'newValue' => "",
                            'children' => genAstDiff($arr1[$key], $arr2[$key])];
        }
        if ($keyInArr1 && $keyInArr2 && $arr1[$key] === $arr2[$key]) {
            return (object)['key' => $key,
                            'type' => 'notChanged',
                            'oldValue' => $arr1[$key],
                            'newValue' => "",
                            'children' => ""];
        }
        if ($keyInArr1 && $keyInArr2 && $arr1[$key]!== $arr2[$key]) {
            return (object)['key' => $key,
                            'type' => 'changed',
                            'oldValue' => $arr1[$key],
                            'newValue' => $arr2[$key],
                            'children' => ''];
        }
        if ($keyInArr1 && !$keyInArr2) {
            return (object)['key' => $key,
                            'type' => 'removed',
                            'oldValue' => $arr1[$key],
                            'newValue' => "",
                            'children' => ""];
        }
        if (!$keyInArr1 && $keyInArr2) {
            return (object)['key' => $key,
                            'type' => 'added',
                            'oldValue' => "",
                            'newValue' => $arr2[$key],
                            'children' => ""];
        }
    }, $keys));

    return  $result;
}

function runRender($format, $astDiff)
{
    $render = "\\Formatters\\".ucfirst($format)."\\render";
    if (function_exists($render)) {
        return $render($astDiff);
    } else {
        return \Formatters\Tree\render($astDiff);
    }
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

    return runRender($format, $astDiff);
}
