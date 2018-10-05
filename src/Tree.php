<?php
namespace Formatters\Tree;

function getStatus($status)
{
    switch ($status) {
        case 'add':
        case 'addNode':
            return '+';
        case 'removed':
        case 'removedNode':
            return '-';
        default:
            return ' ';
    }
}

function render($ast)
{
    $getTreeIter = function ($begin, $ast, $end, $spaces) use (&$getTreeIter) {
        $result = array_map(function ($node) use ($begin, $end, $spaces, &$getTreeIter) {
            $status = getStatus($node->type);
            switch ($node->type) {
                case 'notChangedNode':
                case 'addNode':
                case 'removedNode':
                    $tree = $getTreeIter($begin, $node->children, "    {$end}", "    {$spaces}");
                    return "{$spaces}{$status} {$node->key}: {$tree}";
                case 'notChanged':
                case 'removed':
                case 'add':
                    return "{$spaces}{$status} {$node->key}: {$node->value}";
                case 'changed':
                    return "{$spaces}- {$node->key}: {$node->value[0]}\n{$spaces}+ {$node->key}: {$node->value[1]}";
            }
        }, $ast);
    
        return implode("\n", array_merge([$begin], $result, [$end]));
    };
    return $getTreeIter('{', $ast, '}', '  ');
}
