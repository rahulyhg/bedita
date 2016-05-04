<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Test\Fixture;

use BEdita\Core\TestSuite\Fixture\TestFixture;

/**
 * Fixture for `auth_providers` table.
 */
class AuthProvidersFixture extends TestFixture
{

    /**
     * {@inheritDoc}
     */
    public $records = [
        [
            'name' => 'example',
            'url' => 'https://example.com/oauth2',
            'params' => '{}',
        ],
        [
            'name' => 'example_2',
            'url' => 'https://example.org/oauth2',
            'params' => '{"param":"value"}',
        ],
    ];
}
