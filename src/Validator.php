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

namespace Offdev\Csv;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator as ActualValidator;

/**
 * Class Validator
 * @package Offdev\CsvParser
 */
class Validator
{
    /** @var array */
    private $rules = [];

    /**
     * Validator constructor.
     * @param array $rules
     */
    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * @param Collection $data
     * @return bool
     */
    public function isValid(Collection $data): bool
    {
        return (new ActualValidator(
            $this->getTranslator(),
            $data->all(),
            $this->rules
        ))->passes();
    }

    /**
     * @return Translator
     * @codeCoverageIgnore
     */
    private function getTranslator(): Translator
    {
        return new class implements Translator
        {
            public function trans($key, array $replace = [], $locale = null)
            {
                return $key;
            }
            public function transChoice($key, $number, array $replace = [], $locale = null)
            {
                return $key;
            }
            public function getLocale()
            {
                return 'en_US.UTF-8';
            }
            public function setLocale($locale)
            {
            }
        };
    }
}
