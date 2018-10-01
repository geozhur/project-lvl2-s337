<?php
namespace GenDiff\Common;

function encode($data)
{
    switch (gettype($data)) {
        case 'NULL':
            return 'null';
        case 'boolean':
            return ($data ? 'true' : 'false');
        default:
            return $data;
    }
}