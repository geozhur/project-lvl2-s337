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

function toNode($level, $status, $key, $value, $children = '', $oldValue = '')
{
        return (object)['key' => $key,
                        'status' => $status,
                        'level' => $level,
                        'value' => $value,
                        'oldValue' => $oldValue,
                        'children' => $children];
}

function genAstDiff($content1, $content2, $level = 1)
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
                return toNode($level, '', $item, '', genAstDiff($value1, $value2, $level+1));
            } else {
                if ($value1 == $value2) {
                        return toNode($level, '', $item, encode($value1));
                } else {
                        return [toNode($level, 'from', $item, encode($value1), '', encode($value2)),
                                toNode($level, 'to', $item, encode($value2), '', encode($value1))];
                }
            }
        } elseif (array_key_exists($item, $contentArr1) && !array_key_exists($item, $contentArr2)) {
            $value3 = $contentArr1[$item];
            if (!is_object($value3)) {
                return toNode($level, 'remove', $item, encode($value3));
            } else {
                return toNode($level, 'remove', $item, '', genAstDiff($value3, $value3, $level+1));
            }
        } else {
            $value4 = $contentArr2[$item];
            if (!is_object($value4)) {
                return toNode($level, 'add', $item, encode($value4));
            } else {
                return toNode($level, 'add', $item, '', genAstDiff($value4, $value4, $level+1));
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
    $getTreeIter = function ($begin, $ast, $end) use (&$getTreeIter) {
        $result = Collection\flattenAll(array_map(function ($item) use ($begin, $end, &$getTreeIter) {
            $numSpaces = $item->level*4-2;
            $spaces = str_repeat(' ', $numSpaces);
            $status = getStatusForTree($item->status);
            if (!isset($item->children) || !is_array($item->children)) {
                return "{$spaces}{$status} {$item->key}: {$item->value}";
            } else {
                $tree = $getTreeIter($begin, $item->children, "    $end");
                return "{$spaces}{$status} {$item->key}: {$tree}";
            }
        }, $ast));
    
        return implode("\n", array_merge([$begin], $result, [$end]));
    };
    return $getTreeIter('{', $ast, '}');
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

function genDiff($pathToFile1, $pathToFile2, $format = 'pretty')
{
    $contentForExt1 = \GenDiff\Parse\parse($pathToFile1);
    $contentForExt2 = \GenDiff\Parse\parse($pathToFile2);
    if (!$contentForExt1 || !$contentForExt2) {
        return "File not found or failed to parse";
    }
    $astDiff = genAstDiff($contentForExt1, $contentForExt2);
    if ($format === 'plane') {
        return getPlane($astDiff);
    }
    $tree = getTree($astDiff);
    return $tree;
}
