<?php
namespace Formatters\Tree;

use function GenDiff\Differ\encode as encode;

function getStatus($status)
{
    switch ($status) {
        case 'added':
        case 'addNode':
            return '+';
        case 'removed':
        case 'removedNode':
            return '-';
        default:
            return ' ';
    }
}

function buildVal($begin, $obj, $end, $spaces)
{
    $arr = get_object_vars($obj);
    $keys = array_keys($arr);
    $getBuildValIter = function ($begin, $keys, $end, $spaces) use ($arr) {
            $result = array_map(function ($elem) use ($begin, $end, $spaces, $arr) {
                if ($elem ==='value' && is_object($arr[$elem])) {
                    $tree = buildVal($begin, $arr[$elem], "    {$end}", "    {$spaces}");
                }
                return "{$spaces}  {$elem}: {$arr[$elem]}";
            }, $keys);
            return implode("\n", array_merge([$begin], $result, [$end]));
    };
    return $getBuildValIter($begin, $keys, $end, $spaces);
}


function render($ast)
{
    $getTreeIter = function ($begin, $ast, $end, $spaces) use (&$getTreeIter) {
        $result = array_map(function ($node) use ($begin, $end, $spaces, &$getTreeIter) {
            $status = getStatus($node->type);
            $val = is_object($node->value) ?
             buildVal($begin, $node->value, "    {$end}", "    {$spaces}") :
             encode($node->value);
            switch ($node->type) {
                case 'node':
                    $tree = $getTreeIter($begin, $node->children, "    {$end}", "    {$spaces}");
                    return "{$spaces}{$status} {$node->key}: {$tree}";
                case 'removed':
                case 'added':
                    return "{$spaces}{$status} {$node->key}: {$val}";
                case 'changed':
                    return "{$spaces}- {$node->key}: {$val}\n{$spaces}+ {$node->key}: {$node->newValue}";
                case 'notChanged':
                    return "{$spaces}{$status} {$node->key}: {$val}";
            }
        }, $ast);
        return implode("\n", array_merge([$begin], $result, [$end]));
    };
    return $getTreeIter('{', $ast, '}', '  ');
}
