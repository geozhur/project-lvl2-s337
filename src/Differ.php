<?php
namespace GenDiff\Differ;

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

function toNode($status, $key, $value, $children = '', $oldValue = '')
{
        return (object)['key' => $key,
                        'status' => $status,
                        'value' => $value,
                        'oldValue' => $oldValue,
                        'children' => $children];
}

function genAstDiff($content1, $content2)
{
    $contentArr1 = get_object_vars($content1);
    $contentArr2 = get_object_vars($content2);
    $contentKeys1 = array_keys($contentArr1);
    $contentKeys2 = array_keys($contentArr2);
    $contentKeys = array_unique(array_merge($contentKeys1, $contentKeys2));

    $result = Collection\flattenAll(array_map(function ($item) use ($contentArr1, $contentArr2, $level) {
        if (array_key_exists($item, $contentArr1) && array_key_exists($item, $contentArr2)) {
            $value1 = $contentArr1[$item];
            $value2 = $contentArr2[$item];
            if (is_object($value1)) {
                return toNode('', $item, '', genAstDiff($value1, $value2));
            } else {
                if ($value1 == $value2) {
                        return toNode('', $item, encode($value1));
                } else {
                        return [toNode('from', $item, encode($value1), '', encode($value2)),
                                toNode('to', $item, encode($value2), '', encode($value1))];
                }
            }
        } elseif (array_key_exists($item, $contentArr1) && !array_key_exists($item, $contentArr2)) {
            $value3 = $contentArr1[$item];
            if (!is_object($value3)) {
                return toNode('remove', $item, encode($value3));
            } else {
                return toNode('remove', $item, '', genAstDiff($value3, $value3));
            }
        } else {
            $value4 = $contentArr2[$item];
            if (!is_object($value4)) {
                return toNode('add', $item, encode($value4));
            } else {
                return toNode('add', $item, '', genAstDiff($value4, $value4));
            }
        }
    }, $contentKeys));

    return  $result;
}

function getStatusForTree($status)
{
    switch ($status) {
        case 'to':
        case 'add':
            return '+';
        case 'from':
        case 'remove':
            return '-';
        default:
            return ' ';
    }
}

function getTree($ast)
{
    $getTreeIter = function ($begin, $ast, $end, $spaces) use (&$getTreeIter) {
        $result = Collection\flattenAll(array_map(function ($item) use ($begin, $end, $spaces, &$getTreeIter) {
            $status = getStatusForTree($item->status);
            if (!isset($item->children) || !is_array($item->children)) {
                return "{$spaces}{$status} {$item->key}: {$item->value}";
            } else {
                $tree = $getTreeIter($begin, $item->children, "    {$end}", "    {$spaces}");
                return "{$spaces}{$status} {$item->key}: {$tree}";
            }
        }, $ast));
    
        return implode("\n", array_merge([$begin], $result, [$end]));
    };
    return $getTreeIter('{', $ast, '}', '  ');
}

function getPlane($ast)
{
    $getPlaneIter = function ($ast, $path) use (&$getPlaneIter) {
        $result = array_reduce($ast, function ($acc, $item) use ($path, &$getPlaneIter) {
            if (!empty($item->status)) {
                switch ($item->status) {
                    case 'from':
                        return array_merge(
                            $acc,
                            ["Property '{$path}{$item->key}' was changed. From '{$item->value}' to '{$item->oldValue}'"]
                        );
                    case 'add':
                        $value = $item->value === '' ? 'complex value' : $item->value;
                        return array_merge(
                            $acc,
                            ["Property '{$path}{$item->key}' was added with value: '{$value}'"]
                        );
                    case 'to':
                        return $acc;
                    case 'remove':
                        return array_merge(
                            $acc,
                            ["Property '{$path}{$item->key}' was removed"]
                        );
                    default:
                        return $acc;
                }
            }
            if (is_array($item->children)) {
                return  array_merge($acc, $getPlaneIter($item->children, "{$path}{$item->key}."));
            }
            return $acc;
        }, []);
        return $result;
    };
    return implode("\n", $getPlaneIter($ast, ''));
}

function getJson($ast)
{
    $getTreeIter = function ($begin, $ast, $end, $spaces) use (&$getTreeIter) {
        $result = array_map(function ($item) use ($begin, $end, $spaces, &$getTreeIter) {
            $status = getStatusForTree($item->status);
            $itemArr = (array)$item;
            $itemKeys =  array_keys($itemArr);
                $node = array_map(function ($elem) use ($spaces, $itemArr, $begin, $end, &$getTreeIter) {
                    if ($elem ==='children' && is_array($itemArr[$elem])) {
                        $tree = $getTreeIter($begin, $itemArr[$elem], "    {$end}", "    {$spaces}");
                        return "{$spaces}\"{$elem}\": {$tree}";
                    }
                    return "{$spaces}\"{$elem}\": \"{$itemArr[$elem]}\"";
                }, $itemKeys);
            return implode("\n", array_merge(["{$spaces}{"], [implode(",\n", $node)], ["{$spaces}}"]));
        }, $ast);
     
        return implode("\n", array_merge([$begin], [implode(",\n", array_merge($result))], [$end]));
    };
    return $getTreeIter('[', $ast, ']', "   ");
}

function genDiff($pathToFile1, $pathToFile2, $format = 'pretty')
{
    $contentForExt1 = \GenDiff\Parse\parse($pathToFile1);
    $contentForExt2 = \GenDiff\Parse\parse($pathToFile2);
    if (!$contentForExt1 || !$contentForExt2) {
        return "File not found or failed to parse";
    }
    $astDiff = genAstDiff($contentForExt1, $contentForExt2);
    if ($format === 'plain') {
        return getPlane($astDiff);
    }
    if ($format === 'json') {
        return getJson($astDiff);
    }
    $tree = getTree($astDiff);
    return $tree;
}
