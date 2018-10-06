<?php
namespace Formatters\Json;

/*
function render($ast)
{
    return json_encode($ast, JSON_PRETTY_PRINT);
}*/

function encode($data)
{
    return json_encode($data);
}

function stringify($obj, $spaces)
{
    if (!is_object($obj)) {
        return encode($obj);
    }

    $stringifyIter = function ($obj, $spaces) use (&$stringifyIter) {
        $arr = get_object_vars($obj);
        $keys = array_keys($arr);

        $result = array_map(function ($elem) use ($spaces, $arr) {
            if ($elem ==='value' && is_object($arr[$elem])) {
                $tree = $stringifyIter($arr[$elem], "    {$spaces}");
            }
            $value = encode($arr[$elem]);
            return "{$spaces}    \"{$elem}\": {$value}";
        }, $keys);
        return implode("\n", array_merge(['{'], $result, ["{$spaces}}"]));
    };
    return $stringifyIter($obj, $spaces);
}

function render($ast, $spaces = '')
{
        $result = array_map(function ($item) use ($spaces) {
            $itemArr = (array)$item;
            $itemKeys =  array_keys($itemArr);
            $type = $item->type;

            $node = array_map(function ($elem) use ($spaces, $type, $itemArr) {
                if ($elem ==='children' && $type == 'node') {
                    $tree = render($itemArr[$elem],"      {$spaces}");
                    return "    {$spaces}\"{$elem}\": {$tree}";
                } 
                if ($elem === 'newValue'&& $type == 'added') {
                    $newVal = stringify($itemArr[$elem], "    {$spaces}");
                    return "    {$spaces}\"{$elem}\": {$newVal}";
                }
                if ($elem === 'oldValue' && $type == 'removed') {
                    $oldVal = stringify($itemArr[$elem], "    {$spaces}");
                    return "    {$spaces}\"{$elem}\": {$oldVal}";
                }
                $value = encode($itemArr[$elem]);
                return "    {$spaces}\"{$elem}\": {$value}";
            }, $itemKeys);

            $strNode = implode(",\n", $node);
            return "  {$spaces}{\n{$strNode}\n  {$spaces}}";
        }, $ast);

        $strResult = implode(",\n", array_merge($result));
        return "[\n{$strResult}\n{$spaces}]";
}
