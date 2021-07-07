<?php

namespace Submtd\LaravelRequestScope;

use RuntimeException;

class ColumnNameSanitizer
{
    /**
     * Based on maximum column name length.
     */
    public const MAX_COLUMN_NAME_LENGTH = 64;

    /**
     * Column names are alphanumeric strings that can contain
     * underscores (`_`) but can't start with a number.
     */
    private const VALID_COLUMN_NAME_REGEX = '/^(?![0-9])[A-Za-z0-9_-]*$/';

    public static function sanitize(string $column): string
    {
        if (strlen($column) > self::MAX_COLUMN_NAME_LENGTH) {
            throw new RuntimeException(
                sprintf(
                    'Given column name `%s` exceeds the maximum column name length of %d characters.',
                    $column,
                    self::MAX_COLUMN_NAME_LENGTH
                )
            );
        }

        if (! preg_match(self::VALID_COLUMN_NAME_REGEX, $column)) {
            throw new RuntimeException(sprintf('Column name `%s` may contain only alphanumerics or underscores, and may not begin with a digit.', $column));
        }

        // JSONAPI expects properties with a hyphen but that doesn't mesh with
        // our database column naming.
        $column = str_replace('-', '_', $column);

        return $column;
    }

    public static function sanitizeArray(array $columns): array
    {
        return array_map([self::class, 'sanitize'], $columns);
    }
}
