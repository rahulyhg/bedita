<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2017 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Model\Validation;

use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Utility\Hash;
use Cake\Validation\Validation as CakeValidation;
use League\JsonGuard\Validator as JsonSchemaValidator;
use League\JsonReference\Dereferencer as JsonSchemaDereferencer;
use League\JsonReference\Loader\ArrayLoader;
use League\JsonReference\Loader\ChainedLoader;

/**
 * Reusable class to check for reserved names.
 * Used for object types and properties.
 *
 * @since 4.0.0
 */
class Validation
{
    /**
     * The list of reserved names
     *
     * @var string[]|null
     */
    protected static $reserved = null;

    /**
     * Clear reserved names list
     *
     * @return void
     */
    public static function clear()
    {
        static::$reserved = null;
    }

    /**
     * Load list of reserved names in `$reserved`
     *
     * @return string[]
     */
    protected static function reservedWords()
    {
        if (static::$reserved === null) {
            static::$reserved = (new PhpConfig())->read('BEdita/Core.reserved');
        }

        return static::$reserved;
    }

    /**
     * Check if a value is not reserved
     *
     * @param mixed $value Value to check
     * @return bool
     */
    public static function notReserved($value)
    {
        if ($value && in_array($value, static::reservedWords())) {
            return false;
        }

        return true;
    }

    /**
     * Checks that a value is a valid URL or custom url as myapp://
     *
     * @param string $value The url to check
     * @return bool
     */
    public static function url($value)
    {
        // check for a valid scheme (https://, myapp://,...)
        $regex = '/(?<scheme>^[a-z][a-z0-9+\-.]*:\/\/).*/';
        if (!preg_match($regex, $value, $matches)) {
            return false;
        }

        // if scheme is not an URL protocol then it's a custom url (myapp://) => ok
        if (!preg_match('/^(https?|ftps?|sftp|file|news|gopher:\/\/)/', $matches['scheme'])) {
            return true;
        }

        return CakeValidation::url($value, true);
    }

    /**
     * Validate using JSON Schema.
     *
     * @param mixed $value Value being validated.
     * @param mixed $schema Schema to validate against.
     * @return true|string
     */
    public static function jsonSchema($value, $schema)
    {
        if (is_string($schema)) {
            $cacheLoader = new ArrayLoader([
                'json-schema.org/draft-06/schema' => json_decode(file_get_contents(__DIR__ . DS . 'schemas' . DS . 'draft-06.json')),
            ]);

            $dereferencer = JsonSchemaDereferencer::draft6();
            $loaderManager = $dereferencer->getLoaderManager();
            $loaderManager->registerLoader('http', new ChainedLoader(
                $cacheLoader,
                $loaderManager->getLoader('http')
            ));
            $loaderManager->registerLoader('https', new ChainedLoader(
                $cacheLoader,
                $loaderManager->getLoader('https')
            ));

            $schema = $dereferencer->dereference($schema);
        }
        if (empty($schema)) {
            return true;
        }

        $value = json_decode(json_encode($value));
        $schema = json_decode(json_encode($schema));
        $validator = new JsonSchemaValidator($value, $schema);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $error = reset($errors);

            return sprintf('%s (in: %s)', $error->getMessage(), $error->getDataPath());
        }

        return true;
    }

    /**
     * Validate language tag using `I18n` configuration.
     *
     * @param string $tag Language tag
     * @return true|string
     */
    public static function languageTag($tag)
    {
        $languages = Hash::normalize((array)Configure::read('I18n.languages'));
        if (!empty($languages)) {
            if (!array_key_exists($tag, $languages)) {
                return __d('bedita', 'Invalid language tag "{0}"', $tag);
            }
        }

        return true;
    }
}
