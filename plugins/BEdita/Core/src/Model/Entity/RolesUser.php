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

namespace BEdita\Core\Model\Entity;

use Cake\ORM\Entity;

/**
 * RolesUser Entity
 *
 * @property int $id
 * @property int $role_id
 * @property int $user_id
 *
 * @property \BEdita\Core\Model\Entity\Role $role
 * @property \BEdita\Core\Model\Entity\User $user
 */
class RolesUser extends Entity
{

    /**
     * {@inheritDoc}
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];
}
