<?php
namespace GenDiff\Differ;

use \Funct\Collection;
use Exception;

function genAstDiff($content1, $content2)
{
    $arr1 = get_object_vars($content1);
    $arr2 = get_object_vars($content2);
    $keys1 = array_keys($arr1);
    $keys2 = array_keys($arr2);
    $keys = array_unique(array_merge($keys1, $keys2));

    $result = Collection\flattenAll(array_map(function ($key) use ($arr1, $arr2) {

        $keyOfNode = ['key' => $key ];
        
        if (!empty($arr1[$key]) && !empty($arr2[$key]) && is_object($arr1[$key]) && is_object($arr2[$key])) {
            $partOfNode = ['type' => 'node','children' => genAstDiff($arr1[$key], $arr2[$key])];
        } else {
            $keyInArr1 = array_key_exists($key, $arr1);
            $keyInArr2 = array_key_exists($key, $arr2);

            if ($keyInArr1) {
                if ($keyInArr2) {
                    if ($arr1[$key]!== $arr2[$key]) {
                        $partOfNode = ['type' => 'changed','oldValue' => $arr1[$key],'newValue' => $arr2[$key]];
                    } elseif ($arr1[$key] === $arr2[$key]) {
                        $partOfNode = ['type' => 'notChanged','oldValue' => $arr1[$key]];
                    }
                } elseif (!$keyInArr2) {
                    $partOfNode = ['type' => 'removed','oldValue' => $arr1[$key]];
                }
            } elseif (!$keyInArr1 && $keyInArr2) {
                $partOfNode = ['type' => 'added','newValue' => $arr2[$key]];
            }
        }
        return (object)array_merge($keyOfNode, $partOfNode);
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
