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

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin(
    'BEdita/API',
    [
        'path' => '/',
        '_namePrefix' => 'api:',
    ],
    function (RouteBuilder $routes) {
        // Home.
        $routes->redirect(
            '/',
            ['_name' => 'api:home'],
            ['persist' => true]
        );
        $routes->connect(
            '/home',
            ['controller' => 'Home', 'action' => 'index'],
            ['_name' => 'home']
        );

        // Status.
        $routes->connect(
            '/status',
            ['controller' => 'Status', 'action' => 'index'],
            ['_name' => 'status']
        );

        // Roles and Users rules that must be on top
        $routes->connect(
            '/users/:user_id/roles',
            ['controller' => 'Roles', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'users:roles']
        );
        $routes->connect(
            '/roles/:role_id/users',
            ['controller' => 'Users', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'roles:users']
        );

        // Roles.
        $routes->connect(
            '/roles',
            ['controller' => 'Roles', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'roles:index']
        );
        $routes->connect(
            '/roles/*',
            ['controller' => 'Roles', 'action' => 'view', '_method' => 'GET'],
            ['_name' => 'roles:view']
        );
        $routes->connect(
            '/roles',
            ['controller' => 'Roles', 'action' => 'add', '_method' => 'POST'],
            ['_name' => 'roles:add']
        );
        $routes->connect(
            '/roles/*',
            ['controller' => 'Roles', 'action' => 'edit', '_method' => 'PATCH'],
            ['_name' => 'roles:edit']
        );
        $routes->connect(
            '/roles/*',
            ['controller' => 'Roles', 'action' => 'delete', '_method' => 'DELETE'],
            ['_name' => 'roles:delete']
        );
        $routes->connect(
            '/roles/:id/relationships/:relationship',
            ['controller' => 'Roles', 'action' => 'relationships'],
            ['_name' => 'roles:relationships']
        );


        // Object Types and Properties rules that must be on top
        $routes->connect(
            '/object_types/:object_type_id/properties',
            ['controller' => 'Properties', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'object_types:properties']
        );
        $routes->connect(
            '/properties/:property_id/object_types',
            ['controller' => 'ObjectTypes', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'properties:object_types']
        );
        // Object Types.
        $routes->connect(
            '/object_types',
            ['controller' => 'ObjectTypes', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'object_types:index']
        );
        $routes->connect(
            '/object_types/*',
            ['controller' => 'ObjectTypes', 'action' => 'view', '_method' => 'GET'],
            ['_name' => 'object_types:view']
        );
        $routes->connect(
            '/object_types',
            ['controller' => 'ObjectTypes', 'action' => 'add', '_method' => 'POST'],
            ['_name' => 'object_types:add']
        );
        $routes->connect(
            '/object_types/*',
            ['controller' => 'ObjectTypes', 'action' => 'edit', '_method' => 'PATCH'],
            ['_name' => 'object_types:edit']
        );
        $routes->connect(
            '/object_types/*',
            ['controller' => 'ObjectTypes', 'action' => 'delete', '_method' => 'DELETE'],
            ['_name' => 'object_types:delete']
        );

        $routes->connect(
            '/object_types/:id/relationships/:relationship',
            ['controller' => 'ObjectTypes', 'action' => 'relationships'],
            ['_name' => 'object_types:relationships']
        );
        // Properties.
        $routes->connect(
            '/properties',
            ['controller' => 'Properties', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'properties:index']
        );
        $routes->connect(
            '/properties/*',
            ['controller' => 'Properties', 'action' => 'view', '_method' => 'GET'],
            ['_name' => 'properties:view']
        );
        $routes->connect(
            '/properties',
            ['controller' => 'Properties', 'action' => 'add', '_method' => 'POST'],
            ['_name' => 'properties:add']
        );
        $routes->connect(
            '/properties/*',
            ['controller' => 'Properties', 'action' => 'edit', '_method' => 'PATCH'],
            ['_name' => 'properties:edit']
        );
        $routes->connect(
            '/properties/*',
            ['controller' => 'Properties', 'action' => 'delete', '_method' => 'DELETE'],
            ['_name' => 'properties:delete']
        );
        $routes->connect(
            '/properties/:id/relationships/:relationship',
            ['controller' => 'Properties', 'action' => 'relationships'],
            ['_name' => 'properties:relationships']
        );



        // Users.
        $routes->connect(
            '/users',
            ['controller' => 'Users', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'users:index']
        );
        $routes->connect(
            '/users/*',
            ['controller' => 'Users', 'action' => 'view', '_method' => 'GET'],
            ['_name' => 'users:view']
        );
        $routes->connect(
            '/users',
            ['controller' => 'Users', 'action' => 'add', '_method' => 'POST'],
            ['_name' => 'users:add']
        );
        $routes->connect(
            '/users/*',
            ['controller' => 'Users', 'action' => 'edit', '_method' => 'PATCH'],
            ['_name' => 'users:edit']
        );
        $routes->connect(
            '/users/*',
            ['controller' => 'Users', 'action' => 'delete', '_method' => 'DELETE'],
            ['_name' => 'users:delete']
        );
        $routes->connect(
            '/users/:id/relationships/:relationship',
            ['controller' => 'Users', 'action' => 'relationships'],
            ['_name' => 'users:relationships']
        );

        // Login.
        $routes->connect(
            '/auth',
            ['controller' => 'Login', 'action' => 'login', '_method' => 'POST'],
            ['_name' => 'login']
        );
        $routes->connect(
            '/auth',
            ['controller' => 'Login', 'action' => 'whoami', '_method' => 'GET'],
            ['_name' => 'login:whoami']
        );

        // Trash.
        $routes->connect(
            '/trash',
            ['controller' => 'Trash', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'trash:index']
        );
        $routes->connect(
            '/trash/*',
            ['controller' => 'Trash', 'action' => 'view', '_method' => 'GET'],
            ['_name' => 'trash:view']
        );
        $routes->connect(
            '/trash/*',
            ['controller' => 'Trash', 'action' => 'restore', '_method' => 'PATCH'],
            ['_name' => 'trash:restore']
        );
        $routes->connect(
            '/trash/*',
            ['controller' => 'Trash', 'action' => 'delete', '_method' => 'DELETE'],
            ['_name' => 'trash:delete']
        );

        // Objects.
        $routes->connect(
            '/:object_type',
            ['controller' => 'Objects', 'action' => 'index', '_method' => 'GET'],
            ['_name' => 'objects:index']
        );
        $routes->connect(
            '/:object_type/*',
            ['controller' => 'Objects', 'action' => 'view', '_method' => 'GET'],
            ['_name' => 'objects:view']
        );
        $routes->connect(
            '/:object_type',
            ['controller' => 'Objects', 'action' => 'add', '_method' => 'POST'],
            ['_name' => 'objects:add']
        );
        $routes->connect(
            '/:object_type/*',
            ['controller' => 'Objects', 'action' => 'edit', '_method' => 'PATCH'],
            ['_name' => 'objects:edit']
        );
        $routes->connect(
            '/:object_type/*',
            ['controller' => 'Objects', 'action' => 'delete', '_method' => 'DELETE'],
            ['_name' => 'objects:delete']
        );
    }
);
