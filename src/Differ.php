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

    $result = Collection\flattenAll(array_map(function ($item) use ($contentArr1, $contentArr2) {
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
        $result = array_map(function ($item) use ($begin, $end, $spaces, &$getTreeIter) {
            $status = getStatusForTree($item->status);
            if (!isset($item->children) || !is_array($item->children)) {
                return "{$spaces}{$status} {$item->key}: {$item->value}";
            } else {
                $tree = $getTreeIter($begin, $item->children, "    {$end}", "    {$spaces}");
                return "{$spaces}{$status} {$item->key}: {$tree}";
            }
        }, $ast);
    
        return implode("\n", array_merge([$begin], $result, [$end]));
    };
    return $getTreeIter('{', $ast, '}', '  ');
}

function getPlain($ast)
{
    $getPlainIter = function ($ast, $path) use (&$getPlainIter) {
        $result = array_reduce($ast, function ($acc, $item) use ($path, &$getPlainIter) {
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
                return  array_merge($acc, $getPlainIter($item->children, "{$path}{$item->key}."));
            }
            return $acc;
        }, []);
        return $result;
    };
    return implode("\n", $getPlainIter($ast, ''));
}

function getJson($ast)
{
    $getJsonIter = function ($begin, $ast, $end, $spaces) use (&$getJsonIter) {
        $result = array_map(function ($item) use ($begin, $end, $spaces, &$getJsonIter) {
            $itemArr = (array)$item;
            $itemKeys =  array_keys($itemArr);
                $node = array_map(function ($elem) use ($spaces, $itemArr, $begin, $end, &$getJsonIter) {
                    if ($elem ==='children' && is_array($itemArr[$elem])) {
                        $tree = $getJsonIter($begin, $itemArr[$elem], "    {$end}", "    {$spaces}");
                        return "{$spaces}\"{$elem}\": {$tree}";
                    }
                    return "{$spaces}\"{$elem}\": \"{$itemArr[$elem]}\"";
                }, $itemKeys);
            $strNode = implode(",\n", $node);
            return "{$spaces}{\n{$strNode}\n{$spaces}}";
        }, $ast);
     
        $strResult = implode(",\n", array_merge($result));
        return "{$begin}\n{$strResult}\n{$end}";
    };
    return $getJsonIter('[', $ast, ']', "   ");
}

function genDiff($pathToFile1, $pathToFile2, $format = 'pretty')
{

    $content1 = file_get_contents($pathToFile1);
    $content2 = file_get_contents($pathToFile2);

    if (!$content1 || !$content2) {
        return "File not found or not read";
    }

    $extension1 = pathinfo($pathToFile1, PATHINFO_EXTENSION);
    $extension2 = pathinfo($pathToFile2, PATHINFO_EXTENSION);

    $contentForExt1 = \GenDiff\Content\parse($content1, $extension1);
    $contentForExt2 = \GenDiff\Content\parse($content2, $extension2);

    if (!$contentForExt1 || !$contentForExt2) {
        return "File not json or not yaml";
    }

    $astDiff = genAstDiff($contentForExt1, $contentForExt2);

    switch ($format) {
        case 'plain':
            return getPlain($astDiff);
        case 'json':
            return getJson($astDiff);
        default:
            return getTree($astDiff);
    }
}
