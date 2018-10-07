<?php
namespace Formatters\Tree;

use \Funct\Collection;
use function GenDiff\Differ\stringify as stringify;

function render($ast, $spaces = "")
{
    $result = Collection\flattenAll(array_map(function ($node) use ($spaces) {
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
                return ["  {$spaces}- {$node->key}: {$oldVal}",
                        "  {$spaces}+ {$node->key}: {$newVal}"];
            case 'notChanged':
                return "  {$spaces}  {$node->key}: {$oldVal}";
        }
    }, $ast));
    return implode("\n", array_merge(['{'], $result, ["{$spaces}}"]));
}
