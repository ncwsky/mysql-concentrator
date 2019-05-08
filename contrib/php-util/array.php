<?php

function array_fetch($array, $index, $default = null)
{
    if (array_key_exists($index, $array)) {
        return $array[$index];
    }
    return $default;
}
