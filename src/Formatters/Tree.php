<?php
namespace Formatters\Tree;

use function GenDiff\Differ\encode as encode;

function stringify($obj, $spaces)
{
    if (!is_object($obj)) {
        return encode($obj);
    }

    $stringifyIter = function ($obj, $spaces) use (&$stringifyIter) {
        $arr = get_object_vars($obj);
        $keys = array_keys($arr);

        $result = array_map(function ($elem) use ($spaces, $arr) {
            if (is_object($arr[$elem])) {
                $tree = $stringifyIter($arr[$elem], "    {$spaces}");
            }
            return "{$spaces}    {$elem}: {$arr[$elem]}";
        }, $keys);
        return implode("\n", array_merge(['{'], $result, ["{$spaces}}"]));
    };
    return $stringifyIter($obj, $spaces);
}

function render($ast, $spaces = "")
{
    $result = array_map(function ($node) use ($spaces) {
        $oldVal = stringify($node->oldValue, "    {$spaces}");
        $newVal = stringify($node->newValue, "    {$spaces}");
        switch ($node->type) {
            case 'node':
                $tree = render($node->children, "    {$spaces}");
                return "  {$spaces}  {$node->key}: {$tree}";
            case 'removed':
                return "  {$spaces}- {$node->key}: {$oldVal}";
            case 'added':
                return "  {$spaces}+ {$node->key}: {$newVal}";
            case 'changed':
                return "  {$spaces}- {$node->key}: {$oldVal}\n  {$spaces}+ {$node->key}: {$newVal}";
            case 'notChanged':
                return "  {$spaces}  {$node->key}: {$oldVal}";
        }
    }, $ast);
    return implode("\n", array_merge(['{'], $result, ["{$spaces}}"]));
}
