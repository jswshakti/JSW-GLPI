<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @var \DBmysql $DB
 * @var \Migration $migration
 * @var array $DELFROMDISPLAYPREF
 */

$table = SavedSearch::getTable();
$field = 'is_private';
if ($DB->fieldExists($table, $field)) {
    $obj = new SavedSearch();
    $entity_table = Entity_SavedSearch::getTable();
    foreach ($obj->find(['is_private' => 0]) as $search) {
        $DB->insertOrDie(
            $entity_table,
            [
                'savedsearches_id' => $search['id'],
                'entities_id' => $search['entities_id'],
                'is_recursive' => $search['is_recursive']
            ],
            'Create link between saved search and entity'
        );
    }

    $migration->dropField($table, $field);

    $DELFROMDISPLAYPREF['SavedSearch'] = 4;
}
