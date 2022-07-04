<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Imports;

use Hyperf\Utils\Collection;
use Hyperf\Utils\Str;
use InvalidArgumentException;

class HeadingRowFormatter
{
    /**
     * @const string
     */
    public const FORMATTER_NONE = 'none';

    /**
     * @const string
     */
    public const FORMATTER_SLUG = 'slug';

    /**
     * @var string
     */
    protected static $formatter;

    /**
     * @var callable[]
     */
    protected static $customFormatters = [];

    /**
     * @var array
     */
    protected static $defaultFormatters = [
        self::FORMATTER_NONE,
        self::FORMATTER_SLUG,
    ];

    public static function format(array $headings): array
    {
        $result = (new Collection($headings))->map(function ($value, $key) {
            return static::callFormatter($value, $key);
        })->toArray();
        return $result;
    }

    /**
     * @param string $name
     */
    public static function default(string $name = null)
    {
        if (null !== $name && ! isset(static::$customFormatters[$name]) && ! in_array($name, static::$defaultFormatters, true)) {
            throw new InvalidArgumentException(sprintf('Formatter "%s" does not exist', $name));
        }

        static::$formatter = $name;
    }

    public static function extend(string $name, callable $formatter)
    {
        static::$customFormatters[$name] = $formatter;
    }

    /**
     * Reset the formatter.
     */
    public static function reset()
    {
        static::default();
    }

    /**
     * @param mixed      $value
     * @param null|mixed $key
     *
     * @return mixed
     */
    protected static function callFormatter($value, $key = null)
    {
        static::$formatter = static::$formatter ?? config('excel.imports.heading_row.formatter', self::FORMATTER_SLUG);

        // Call custom formatter
        if (isset(static::$customFormatters[static::$formatter])) {
            $formatter = static::$customFormatters[static::$formatter];

            return $formatter($value, $key);
        }

        if (self::FORMATTER_SLUG === static::$formatter) {
            return Str::slug($value ?? '', '_');
        }

        // No formatter (FORMATTER_NONE)
        return $value;
    }
}
