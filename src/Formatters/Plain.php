<?php
namespace Formatters\Plain;

use \Funct\Collection;

function stringify($obj)
{
    return is_object($obj) ? 'complex value' : trim(json_encode($obj), '" ');
    ;
}

function render($ast, $path = '')
{
    $result = Collection\flattenAll(array_reduce($ast, function ($acc, $node) use ($path) {
        switch ($node->type) {
            case 'node':
                return [$acc, render($node->children, "{$path}{$node->key}.")];
            case 'removed':
                return [$acc, "Property '{$path}{$node->key}' was removed"];
            case 'added':
                $newValue = stringify($node->newValue);
                return [$acc, "Property '{$path}{$node->key}' was added with value: '{$newValue}'"];
            case 'changed':
                $oldValue = stringify($node->oldValue);
                $newValue = stringify($node->newValue);
                return [$acc, "Property '{$path}{$node->key}' was changed. ".
                              "From '{$oldValue}' to '{$newValue}'"];
        }
        return $acc;
    }, []));
    return implode("\n", $result);
}
