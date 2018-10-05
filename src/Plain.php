<?php
namespace Formatters\Plain;

use \Funct\Collection;

function render($ast)
{
    $getPlainIter = function ($ast, $path) use (&$getPlainIter) {
        $result = Collection\flattenAll(array_map(function ($item) use ($path, &$getPlainIter) {
            switch ($item->type) {
                case 'changed':
                    return "Property '{$path}{$item->key}' was changed. " .
                           "From '{$item->value}' to '{$item->newValue}'\n";
                case 'add':
                case 'addNode':
                    $value = $item->value === '' ? 'complex value' : $item->value;
                    return "Property '{$path}{$item->key}' was added with value: '{$value}'\n";
                case 'removed':
                case 'removedNode':
                    return "Property '{$path}{$item->key}' was removed\n";
                case 'notChangedNode':
                    return  $getPlainIter($item->children, "{$path}{$item->key}.");
            }
        }, $ast));
        return $result;
    };
    return trim(implode("", $getPlainIter($ast, '')));
}
