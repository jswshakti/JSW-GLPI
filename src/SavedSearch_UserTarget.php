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

class SavedSearch_UserTarget extends CommonDBRelation
{
    public $auto_message_on_action = false;

    public static $itemtype_1          = 'SavedSearch';
    public static $items_id_1          = 'savedsearches_id';

    public static $itemtype_2          = 'User';
    public static $items_id_2          = 'users_id';

    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
    public static $logs_for_item_2     = false;

    public function prepareInputForUpdate($input)
    {
        return $this->can($input['id'], READ) ? $input : false;
    }

    /**
     * Summary of getDefault
     * @param mixed $users_id id of the user
     * @param mixed $itemtype type of item
     * @return array|boolean same output than SavedSearch::getParameters()
     * @since 9.2
     */
    public static function getDefault($users_id, $itemtype)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iter = $DB->request(['SELECT' => 'savedsearches_id',
            'FROM'   => 'glpi_savedsearches_users',
            'WHERE'  => ['users_id' => $users_id,
                'itemtype' => $itemtype
            ]
        ]);
        if (count($iter)) {
            $row = $iter->current();
            // Load default bookmark for this $itemtype
            $bookmark = new SavedSearch();
            // Only get data for bookmarks
            return $bookmark->getParameters($row['savedsearches_id']);
        }
        return false;
    }

    /**
     * Get users for a saved search
     *
     * @param SavedSearch $savedSearch SavedSearch instance
     *
     * @return array of users linked to a saved search
     **/
    public static function getUsers(SavedSearch $savedSearch)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $results   = [];
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                self::$items_id_1 => $savedSearch->getID()
            ]
        ]);

        foreach ($iterator as $data) {
            $results[$data[self::$items_id_2]][] = $data;
        }
        return $results;
    }
}
