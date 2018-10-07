<?php
namespace Formatters\Tree;

use function GenDiff\Differ\stringify as stringify;

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
