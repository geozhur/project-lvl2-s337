<?php
namespace Formatters\Plain;

use \Funct\Collection;

function render($ast)
{
    $getPlainIter = function ($ast, $path) use (&$getPlainIter) {
        $result = Collection\flattenAll(array_map(function ($node) use ($path, &$getPlainIter) {
            if ($node->type == 'node') {
                return $getPlainIter($node->children, "{$path}{$node->key}.");
            } else {
                $oldValue = is_object($node->oldValue) ? 'complex value' : $node->oldValue;
                $newValue = is_object($node->newValue) ? 'complex value' : $node->newValue;
                return (object)[
                    'type' => $node->type,
                    'path' => "{$path}{$node->key}",
                    'key' => $node->key,
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                ];
            }
        }, $ast));
        return $result;
    };
    $result1 = array_filter($getPlainIter($ast, ''), function ($node) {
        return $node->type!=='notChanged';
    });
    $result2 = array_map(function ($node) {
        switch ($node->type) {
            case 'changed':
                return "Property '{$node->path}' was changed. From '{$node->oldValue}' to '{$node->newValue}'";
            case 'added':
                return "Property '{$node->path}' was added with value: '{$node->newValue}'";
            case 'removed':
                return "Property '{$node->path}' was removed";
        }
    }, $result1);

    return implode("\n", $result2);
}
