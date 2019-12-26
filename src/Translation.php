<?php

namespace Sribna\CsvTranslation;

use Countable;
use Illuminate\Support\Str;
use Illuminate\Translation\MessageSelector;
use RuntimeException;

/**
 * Class Translation
 * @package Sribna\CsvTranslation
 */
class Translation
{

    /**
     * The current locale code
     * @var string
     */
    protected $locale;

    /**
     * Default locale (language) used as a translation key
     * @var string
     */
    protected $default_locale = 'en';

    /**
     * The current translation context
     * @var string
     */
    protected $context;

    /**
     * Whether the translation files are prepared
     * @var bool
     */
    protected $prepared = false;

    /**
     * Array of added translation keys (translations has not been found)
     * @var array
     */
    protected $added_keys = [];

    /**
     * Array of loaded translations keyed by context file paths
     * @var array
     */
    protected $loaded = [];

    /**
     * Translation root directory
     * @var string
     */
    protected $directory;

    /**
     * CSV field delimiter
     * @var string
     */
    protected $csv_delimiter = ',';

    /**
     * CSV field enclosure
     * @var string
     */
    protected $csv_enclosure = '"';

    /**
     * @var MessageSelector
     */
    protected $message_selector;

    /**
     * Translation constructor.
     * @param MessageSelector $messageSelector
     */
    public function __construct(MessageSelector $messageSelector)
    {
        $this->message_selector = $messageSelector;
    }

    /**
     * Set up language and context
     * @return bool
     */
    public function init(): bool
    {
        return $this->prepare();
    }

    /**
     * Set/get a CSV enclosure character
     * @param string $char
     * @return $this|string
     */
    public function csvEnclosure(string $char = null)
    {
        if (!isset($char)) {
            return $this->csv_enclosure;
        }

        $this->csv_enclosure = $char;
        return $this;
    }

    /**
     * Set/get a CSV delimiter character
     * @param string $char
     * @return $this|string
     */
    public function csvDelimiter(string $char = null)
    {
        if (!isset($char)) {
            return $this->csv_delimiter;
        }

        $this->csv_delimiter = $char;
        return $this;
    }

    /**
     * Sets translation root directory
     * @param string $path
     * @return $this|string
     */
    public function directory(string $path = null)
    {
        if (!isset($path)) {
            return $this->directory;
        }

        $this->directory = $path;
        return $this;
    }

    /**
     * Sets the current translation context
     * @param string $context
     * @return $this|string
     */
    public function context(string $context = null)
    {
        if (!isset($context)) {
            return $this->context;
        }

        $this->context = $context;
        return $this;
    }

    /**
     * Set the current locale
     * @param string $locale
     * @return $this|string
     */
    public function locale(string $locale = null)
    {
        if (!isset($locale)) {
            return $this->locale;
        }

        $this->locale = $locale;
        return $this;
    }

    /**
     * Sets a default locale
     * @param string $locale
     * @return $this|string
     */
    public function defaultLocale(string $locale = null)
    {
        if (!isset($locale)) {
            return $this->default_locale;
        }

        $this->default_locale = $locale;
        return $this;
    }

    /**
     * Prepare all necessary files for the current locale
     * @return bool
     */
    protected function prepare(): bool
    {
        if (empty($this->locale)) {
            throw new RuntimeException('Locale is not set');
        }

        if ($this->locale == $this->default_locale) {
            return $this->prepared = false;
        }

        $contextDir = $this->getContextDirectory();

        if (!file_exists($contextDir) && !mkdir($contextDir, 0775, true)) {
            throw new RuntimeException("Cannot create directory $contextDir");
        }

        $parentFile = $this->getContextParentFile();

        if (is_file($parentFile)) {
            return $this->prepared = true;
        }

        $parentFileDir = dirname($parentFile);

        if (!file_exists($parentFileDir) && !mkdir($parentFileDir, 0775, true)) {
            throw new RuntimeException("Cannot create directory $parentFileDir");
        }

        $originalFile = $this->getTranslationFile();

        if (is_file($originalFile) && !copy($originalFile, $parentFile)) {
            throw new RuntimeException("Cannot copy $originalFile to $parentFile");
        }

        return $this->prepared = true;
    }

    /**
     * Returns a locale directory
     * @return string
     */
    public function getLocaleDirectory(): string
    {
        return "{$this->directory}/{$this->locale}";
    }

    /**
     * Returns a path to a translation file
     * @return string
     */
    public function getTranslationFile()
    {
        return $this->getLocaleDirectory() . "/{$this->locale}.csv";
    }

    /**
     * Returns the path to a context parent translation file
     * @return string
     */
    public function getContextParentFile(): string
    {
        return $this->getContextDirectory() . "/_{$this->locale}.csv";
    }

    /**
     * Translates a string
     * @param string $key
     * @param array $replace
     * @param string|null $context
     * @return string
     */
    public function get(string $key, array $replace = [], $context = null): string
    {
        if (!$this->prepared) {
            return $this->format($key, $replace);
        }

        if ($contextFile = $this->getContextFile($context)) {

            $contextTranslations = $this->load($contextFile);

            if (isset($contextTranslations[$key])) {
                return $this->format($contextTranslations[$key][0] ?? $key, $replace);
            }
        }

        $parentFile = $this->getContextParentFile();
        $parentTranslations = $this->load($parentFile);

        if (isset($parentTranslations[$key])) {
            $this->addKey($key, $parentTranslations, $contextFile);
            return $this->format($parentTranslations[$key][0] ?? $key, $replace);
        }

        $this->addKey($key, $parentTranslations, $contextFile);
        $this->addKey($key, $parentTranslations, $parentFile);

        return $this->format($key, $replace);
    }

    /**
     * Translates a plural string
     * @param string $string
     * @param mixed $number
     * @param array $replace
     * @param string|null $context
     * @return string
     */
    public function getPlural(string $string, $number, array $replace = [], $context = null)
    {
        $string = $this->get($string, $replace, $context);

        if (is_array($number) || $number instanceof Countable) {
            $number = count($number);
        }

        $replace['count'] = $number;

        return $this->format(
            $this->message_selector->choose($string, $number, $this->locale),
            $replace
        );
    }

    /**
     * Formats a string using an array of replacements
     * @param string $string
     * @param array $replace
     * @return string
     */
    protected function format(string $string, array $replace)
    {
        if (empty($replace)) {
            return $string;
        }

        uksort($replace, function ($a, $b) {
            return strcmp($b, $a);
        });

        foreach ($replace as $key => $value) {
            $string = str_replace(
                [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $string
            );
        }

        return $string;
    }

    /**
     * Returns a context translation file
     * @param string|null $context
     * @return string|null
     */
    public function getContextFile($context = null): string
    {
        if (!isset($context)) {
            $context = $this->context;
        }

        if (empty($context)) {
            return null;
        }

        static $files = [];

        $key = "{$context}{$this->locale}";;

        if (!isset($files[$key])) {
            $filename = Str::slug($context);
            $directory = $this->getContextDirectory();
            $files[$key] = "$directory/$filename.csv";
        }

        return $files[$key];
    }

    /**
     * Returns a path to a directory containing context translations
     * @return string
     */
    public function getContextDirectory(): string
    {
        return $this->getLocaleDirectory() . '/context';
    }

    /**
     * Returns an array of translations from CSV files
     * @param string $file
     * @return array
     */
    public function load(string $file): array
    {
        if (isset($this->loaded[$file])) {
            return $this->loaded[$file];
        }

        $this->loaded[$file] = [];

        if (!is_file($file)) {
            return [];
        }

        foreach ($this->parseCsv($file) as $row) {
            $key = array_shift($row);
            $this->loaded[$file][$key] = $row;
        }

        return $this->loaded[$file];
    }

    /**
     * Parse a CSV file
     * @param string $file
     * @return array
     */
    public function parseCsv(string $file): array
    {
        $handle = fopen($file, 'r');

        if (!is_resource($handle)) {
            throw new RuntimeException("Cannot open translation file $file");
        }

        $content = [];

        while (($data = fgetcsv(
                $handle,
                0,
                $this->csv_delimiter,
                $this->csv_enclosure)) !== false) {

            $content[] = $data;
        }

        fclose($handle);
        return $content;
    }

    /**
     * Append a translation key string to a translation file
     * @param string $key
     * @param array $translations
     * @param string $file
     * @return bool
     */
    protected function addKey(string $key, array $translations, string $file): bool
    {
        if (isset($this->added_keys[$file][$key])) {
            return false;
        }

        $data = [$key];

        if (isset($translations[$key][0]) && $translations[$key][0] !== '') {
            $data = $translations[$key];
            array_unshift($data, $key);
        }

        $this->writeCsv($file, $data);
        $this->added_keys[$file][$key] = true;

        return true;
    }

    /**
     * Removes context translation files
     * @param null|string $locale
     * @return bool
     */
    public function refresh(string $locale = null): bool
    {
        $this->deleteDirectory($this->getContextDirectory());

        if (isset($locale)) {
            $this->locale($locale);
        }

        return $this->prepare();
    }

    /**
     * Delete a directory with all its content
     * @param string $path
     */
    protected function deleteDirectory(string $path)
    {
        foreach (glob($path) as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory("$file/*");
                rmdir($file);
            } else {
                unlink($file);
            }
        }

    }

    /**
     * Merge two translations
     * @param string $file
     */
    public function merge(string $file)
    {
        $parentFile = $this->getContextParentFile();
        $mergeTranslations = $this->load($file);
        $parentTranslations = $this->load($parentFile);

        foreach ($mergeTranslations as $source => $translation) {
            if (!isset($parentTranslations[$source])) {
                array_unshift($translation, $source);
                $this->writeCsv($parentFile, $translation);
            }
        }

    }

    /**
     * Append a line to a CSV file
     * @param string $file
     * @param array $data
     */
    protected function writeCsv(string $file, array $data)
    {
        $handle = fopen($file, 'a+');
        fputcsv($handle, $data, $this->csv_delimiter, $this->csv_enclosure);
        fclose($handle);
    }
}