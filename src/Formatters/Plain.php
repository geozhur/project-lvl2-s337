<?php
namespace Formatters\Plain;

use \Funct\Collection;
use function GenDiff\Differ\encode as encode;

function stringify($obj)
{
    return is_object($obj) ? 'complex value' : encode($obj);
}

function render($ast, $path = '')
{
    $result = Collection\flattenAll(array_reduce($ast, function ($acc, $node) use ($path) {
        if ($node->type == 'node') {
            return [$acc, render($node->children, "{$path}{$node->key}.")];
        } else {
            if ($node->type == 'removed') {
                return [$acc, "Property '{$path}{$node->key}' was removed"];
            }
            if ($node->type == 'added') {
                $newValue = stringify($node->newValue);
                return [$acc, "Property '{$path}{$node->key}' was added with value: '{$newValue}'"];
            }
            if ($node->type == 'changed') {
                $oldValueChanged = stringify($node->oldValue);
                $newValueChanged = stringify($node->newValue);
                return [$acc, "Property '{$path}{$node->key}' was changed. ".
                              "From '{$oldValueChanged}' to '{$newValueChanged}'"];
            }
        }
        return $acc;
    }, []));
    return implode("\n", $result);
}
