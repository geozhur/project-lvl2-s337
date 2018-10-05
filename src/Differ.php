<?php
namespace GenDiff\Differ;

use \Funct\Collection;
use Exception;

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

function checkType($arr1, $arr2, $key)
{

    if (array_key_exists($key, $arr1) && array_key_exists($key, $arr2)
            &&is_object($arr1[$key]) && is_object($arr2[$key])) {
        $func = function ($value1, $value2) {
            return genAstDiff($value1, $value2);
        };
        return ['notChangedNode', '', $func, ''];
    }
    if (array_key_exists($key, $arr1) && array_key_exists($key, $arr2)
                                      && $arr1[$key] === $arr2[$key]) {
        return ['notChanged', encode($arr1[$key]) ,function () {
            return '';
        }, ''];
    }

    if (array_key_exists($key, $arr1) && array_key_exists($key, $arr2)
                                      && $arr1[$key]!== $arr2[$key]) {
        return ['changed', encode($arr1[$key]), function () {
            return '';
        }, encode($arr2[$key])];
    }

    if (array_key_exists($key, $arr1) && !array_key_exists($key, $arr2)) {
        if (!is_object($arr1[$key])) {
            return ['removed', encode($arr1[$key]), function ($value1, $value2) {
                return '';
            }, ''];
        } else {
            return ['removedNode', '', function ($value1, $value2) {
                return genAstDiff($value1, $value1);
            }, ''];
        }
    }

    if (!array_key_exists($key, $arr1) && array_key_exists($key, $arr2)) {
        if (!is_object($arr2[$key])) {
            return ['add', encode($arr2[$key]), function ($value1, $value2) {
                return '';
            }, ''];
        } else {
            return ['addNode', '', function ($value1, $value2) {
                return genAstDiff($value2, $value2);
            }, ''];
        }
    }
}

function genAstDiff($content1, $content2)
{
    $contentArr1 = get_object_vars($content1);
    $contentArr2 = get_object_vars($content2);
    $contentKeys1 = array_keys($contentArr1);
    $contentKeys2 = array_keys($contentArr2);
    $contentKeys = array_unique(array_merge($contentKeys1, $contentKeys2));
    $result = Collection\flattenAll(array_map(function ($key) use ($contentArr1, $contentArr2) {
        $value1 = isset($contentArr1[$key]) ? $contentArr1[$key]: '';
        $value2 = isset($contentArr2[$key]) ? $contentArr2[$key]: '';
        [$type, $value, $funcChild, $newValue] = checkType($contentArr1, $contentArr2, $key);
        return node($type, $key, $value, $funcChild($value1, $value2), $newValue);
    }, $contentKeys));

    return  $result;
}

function genDiff($pathToFile1, $pathToFile2, $format = 'pretty')
{
    try {
        if (file_exists($pathToFile1) && file_exists($pathToFile2)) {
            $content1 = file_get_contents($pathToFile1);
            $content2 = file_get_contents($pathToFile2);
        }


        $extension1 = pathinfo($pathToFile1, PATHINFO_EXTENSION);
        $extension2 = pathinfo($pathToFile2, PATHINFO_EXTENSION);

        $contentForExt1 = \GenDiff\Parsers\parse($content1, $extension1);
        $contentForExt2 = \GenDiff\Parsers\parse($content2, $extension2);

        $astDiff = genAstDiff($contentForExt1, $contentForExt2);

        switch ($format) {
            case 'plain':
                return \Formatters\Plain\render($astDiff);
            case 'json':
                return \Formatters\Json\render($astDiff);
            default:
                return \Formatters\Tree\render($astDiff);
        }
    } catch (Exception $e) {
        echo "Error: " , $e->getMessage();
    }
}
