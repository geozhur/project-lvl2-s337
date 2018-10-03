<?php
namespace GenDiff\Common;

use \Funct\Collection;
use Symfony\Component\Yaml\Yaml;

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

function toNode($level, $status, $key, $value, $children = [])
{
    if (!empty($children)) {
        return (object)['key' => $key,
                        'status' => $status,
                        'level' => $level,
                        'children' => $children];
    } else {
        return (object)['key' => $key,
                        'status' => $status,
                        'level' => $level,
                        'value' => $value];
    }
}

function genAstDiff($content1, $content2, $level = 1)
{
    $contentArr1 = get_object_vars($content1);
    $contentArr2 = get_object_vars($content2);
    $contentArr = $contentArr1 + $contentArr2;
    $contentKeys = array_keys($contentArr);

    $result = Collection\flattenAll(array_map(function ($item) use ($contentArr1, $contentArr2, $level) {
        if (array_key_exists($item, $contentArr1) && array_key_exists($item, $contentArr2)) {
            $value1 = $contentArr1[$item];
            $value2 = $contentArr2[$item];
            if (is_object($value1)) {
                return toNode($level, ' ', $item, '', genAstDiff($value1, $value2, $level+1));
            } else {
                if ($value1 == $value2) {
                        return toNode($level, ' ', $item, encode($value1));
                } else {
                        return [toNode($level, '-', $item, encode($value1)),
                                toNode($level, '+', $item, encode($value2))];
                }
            }
        } elseif (array_key_exists($item, $contentArr1) && !array_key_exists($item, $contentArr2)) {
            $value3 = $contentArr1[$item];
            if (!is_object($value3)) {
                return toNode($level, '-', $item, encode($value3));
            } else {
                return toNode($level, '-', $item, '', genAstDiff($value3, $value3, $level+1));
            }
        } else {
            $value4 = $contentArr2[$item];
            if (!is_object($value4)) {
                return toNode($level, '+', $item, encode($value4));
            } else {
                return toNode($level, '+', $item, '', genAstDiff($value4, $value4, $level+1));
            }
        }
    }, $contentKeys));

    return  $result;
}

function getTree($ast)
{
    $getTreeIter = function ($begin, $ast, $end) use (&$getTreeIter) {
        $result = Collection\flattenAll(array_map(function ($item) use ($begin, $end, &$getTreeIter) {
            $numSpaces = $item->level*4-2;
            $spaces = str_repeat(' ', $numSpaces);
            if (!isset($item->children) || !is_array($item->children)) {
                return "{$spaces}{$item->status} {$item->key}: {$item->value}";
            } else {
                $tree = $getTreeIter($begin, $item->children, "    $end");
                return "{$spaces}{$item->status} {$item->key}: {$tree}";
            }
        }, $ast));
    
        return implode("\n", array_merge([$begin], $result, [$end]));
    };
    return $getTreeIter('{', $ast, '}');
}

function parse($file, $format)
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

function genDiff($pathToFile1, $pathToFile2, $format = 'pretty')
{
    $contentForExt1 = parse($pathToFile1, $format);
    $contentForExt2 = parse($pathToFile2, $format);
    if (!$contentForExt1 || !$contentForExt2) {
        return "File not found or failed to parse";
    }
    $astDiff = genAstDiff($contentForExt1, $contentForExt2);
    $tree = getTree($astDiff);
    return $tree;
}
