<?php
declare(strict_types=1);

function validate_required(string $value): bool
{
    return trim($value) !== '';
}

function validate_email(string $value): bool
{
    return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
}

function validate_int(string $value): bool
{
    if (trim($value) === '') {
        return false;
    }

    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}
