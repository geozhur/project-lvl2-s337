<?php
namespace Formatters\Tree;

use \Funct\Collection;

const NUM_OF_SPACES_BEFORE_LEVEL = 4;

function encode($data)
{
    return trim(json_encode($data), '" ');
}

function stringify($obj, $level)
{
    if (!is_object($obj)) {
        return encode($obj);
    }

    $stringifyIter = function ($obj, $level) use (&$stringifyIter) {
        $arr = get_object_vars($obj);
        $keys = array_keys($arr);
        $spaces = str_repeat(" ", $level * NUM_OF_SPACES_BEFORE_LEVEL);

        $result = array_map(function ($elem) use ($spaces, $level, $arr) {
            if (is_object($arr[$elem])) {
                $tree = $stringifyIter($arr[$elem], $level + 1);
                return "{$spaces}    {$key}: {$tree}";
            }
            $value = encode($arr[$elem]);
            $key = encode($elem);
            return "{$spaces}    {$key}: {$value}";
        }, $keys);
        return implode("\n", array_merge(['{'], $result, ["{$spaces}}"]));
    };
    return $stringifyIter($obj, $level);
}


function render($ast, $level = 0)
{
    $spaces = str_repeat(" ", $level * NUM_OF_SPACES_BEFORE_LEVEL);
    $result = Collection\flattenAll(array_map(function ($node) use ($level, $spaces) {
        switch ($node->type) {
            case 'node':
                $tree = render($node->children, $level + 1);
                return "  {$spaces}  {$node->key}: {$tree}";
            case 'removed':
                $oldVal = stringify($node->oldValue, $level + 1);
                return "  {$spaces}- {$node->key}: {$oldVal}";
            case 'added':
                $newVal = stringify($node->newValue, $level + 1);
                return "  {$spaces}+ {$node->key}: {$newVal}";
            case 'changed':
                $oldVal = stringify($node->oldValue, $level + 1);
                $newVal = stringify($node->newValue, $level + 1);
                return ["  {$spaces}- {$node->key}: {$oldVal}",
                        "  {$spaces}+ {$node->key}: {$newVal}"];
            case 'notChanged':
                $oldVal = stringify($node->oldValue, $level + 1);
                return "  {$spaces}  {$node->key}: {$oldVal}";
        }
    }, $ast));
    return implode("\n", array_merge(['{'], $result, ["{$spaces}}"]));
}
