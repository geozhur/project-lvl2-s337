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

function toNode($level, $elem, $key, $value, $children = [])
{
    $node = new \stdClass();//create a new
    $node->key = $key;
    $node->status = $elem;
    $node->level=$level;

    if (!empty($children)) {
        $node->children = $children;
    } else {
        $node->value = $value;
    }

    return $node;
}

function genAstDiff($content1, $content2, $level)
{
    $contentArr1 = get_object_vars($content1);
    $contentKeys1 = array_keys($contentArr1);
    $contentArr2 = get_object_vars($content2);
    $contentKeys2 = array_keys($contentArr2);
    $result1 = Collection\flattenAll(array_map(function ($item) use ($contentArr1, $contentArr2, $level) {
        $value1 = $contentArr1[$item];
        if (array_key_exists($item, $contentArr2)) {
            $value2 = $contentArr2[$item];
            if (is_object($value1)) {
                return toNode($level, '', $item, '', genAstDiff($value1, $value2, $level+1));
            } else {
                if ($value1 == $value2) {
                        return toNode($level, '', $item, encode($value1));
                } else {
                        return [toNode($level, '-', $item, encode($value1)),
                                toNode($level, '+', $item, encode($value2))];
                }
            }
        } else {
            if (!is_object($value1)) {
                return toNode($level, '-', $item, encode($value1));
            } else {
                return toNode($level, '-', $item, '', genAstDiff($value1, $value1, $level+1));
            }
        }
    }, $contentKeys1));

    $result2 = array_map(function ($item) use ($contentArr2, $spaces, $level) {
        $value3 = $contentArr2[$item];
        if (!is_object($value3)) {
            return toNode($level, '+', $item, encode($value3));
        } else {
            return toNode($level, '+', $item, '', genAstDiff($value3, $value3, $level+1));
        }
    }, array_diff($contentKeys2, $contentKeys1));

    $result = array_merge($result1, $result2);
    return  $result;
}

function printTreeIter($ast)
{
    $result2 = Collection\flattenAll(array_map(function ($item) {
        if (!is_array($item->children)) {
            return toStr($item->level*4-1, $item->status, $item->key, $item->value);
        } else {
            $spaces = $item->level*4+1;
            return toStr(
                $item->level*4-1,
                $item->status,
                $item->key,
                sprintf("%s\n%s\n%{$spaces}s", '{', printTreeIter($item->children), '}')
            ) ;
        }
    }, $ast));

    $result= implode("\n", $result2);
    return  $result;
}

function printTree($ast)
{
    return sprintf("%s\n%s\n%s", '{', printTreeIter($ast), '}');
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
    return(printTree((genAstDiff(getContentForExt($pathToFile1), getContentForExt($pathToFile2), 1))));
}
