<?php

if (!function_exists('t')) {

    /**
     * Translate a string
     * @param string $key
     * @param array $replace
     * @param string|null $context
     * @return string
     */
    function t(string $key, array $replace = [], string $context = null): string
    {
        return app('csv-translator')->get($key, $replace, $context);
    }
}

if (!function_exists('tp')) {

    /**
     * Translate a plural string
     * @param string $key
     * @param mixed $number
     * @param array $replace
     * @param string|null $context
     * @return string
     */
    function tp(string $key, $number, array $replace = [], string $context = null): string
    {
        return app('csv-translator')->getPlural($key, $number, $replace, $context);
    }
}

if (!function_exists('trans_context')) {

    /**
     * Set the current translation context
     * @param string|null $context
     * @return \Sribna\CsvTranslation\Translation
     */
    function trans_context(string $context = null)
    {
        if (!isset($context)) {
            $context = request()->route()->uri();
        }

        return app('csv-translator')->context($context);
    }
}