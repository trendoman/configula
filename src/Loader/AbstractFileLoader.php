<?php

namespace Configula\Loader;
use Configula\ConfigValues;
use Configula\Exception\ConfigLoaderException;

/**
 * Class AbstractFileLoader
 * @package Configula\Loader
 */
abstract class AbstractFileLoader implements FileLoaderInterface
{
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var bool
     */
    private $required;

    /**
     * AbstractFileLoader constructor.
     * @param string $filePath
     * @param bool $required    If TRUE, this file is required to exist
     */
    public function __construct(string $filePath, bool $required = true)
    {
        $this->filePath = $filePath;
        $this->required = $required;
    }

    /**
     * Load config
     *
     * @return ConfigValues
     */
    public function load(): ConfigValues
    {
        if (! is_readable($this->filePath)) {
            if ($this->required) {
                throw new ConfigLoaderException("Could not read configuration file: " . $this->filePath);
            } else {
                return new ConfigValues([]);
            }
        }

        $values = $this->parse(file_get_contents($this->filePath));
        return new ConfigValues($values ?? []);
    }

    /**
     * Parse the contents
     * @param string $rawFileContents
     * @return array
     */
    abstract protected function parse(string $rawFileContents): array;

    /**
     * @return string
     */
    protected function getFilePath(): string
    {
        return $this->filePath;
    }
}