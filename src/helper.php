<?php
/**
 * The Offdev Project
 *
 * Offdev/Csv - Reads, parses and validates CSV files using streams
 *
 * @author      Pascal Severin <pascal@offdev.net>
 * @copyright   Copyright (c) 2018, Pascal Severin
 * @license     Apache License 2.0
 */

/*
 * Create a steam
 */
if (!function_exists('stream')) {
    /**
     * @param object|resource|string $input
     * @return \Offdev\Csv\Stream
     */
    function stream($input = '')
    {
        if (is_string($input) && file_exists($input) && is_readable($input)) {
            return \Offdev\Csv\Stream::factory(fopen($input, 'r'));
        }
        return \Offdev\Csv\Stream::factory($input);
    }
}
