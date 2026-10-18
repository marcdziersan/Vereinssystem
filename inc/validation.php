<?php
/** Pure Eingabevalidierung fuer Auswahlwerte (unabhaengig von Datenbank und Session). */
function vs_valid_choice(mixed $value, int $min, int $max): bool
{
    if (!is_string($value) && !is_int($value)) return false;
    $n = filter_var($value, FILTER_VALIDATE_INT);
    return $n !== false && $n >= $min && $n <= $max;
}