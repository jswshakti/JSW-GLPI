<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Dashboard\Filters;

use Session;
use Location;
use DBmysql;

class LocationFilter extends AbstractFilter
{
    /**
     * Get the filter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return Location::getTypeName(Session::getPluralNumber());
    }

    /**
     * Get the filter id
     *
     * @return string
     */
    public static function getId(): string
    {
        return "location";
    }

    /**
     * Get the filter criteria
     * @return array
     */
    public static function getCriteria(DBmysql $DB, string $table = "", array $apply_filters = []): array
    {
        $criteria = [
            "WHERE" => [],
            "JOIN"  => [],
        ];

        if (
            $DB->fieldExists($table, 'locations_id')
            && isset($apply_filters[self::getId()])
            && (int) $apply_filters[self::getId()] > 0
        ) {
            $criteria["WHERE"] += [
                "$table.locations_id" => (int) $apply_filters[self::getId()]
            ];
        }

        return $criteria;
    }

    /**
     * Get the search filter criteria
     *
     * @return array
     */
    public static function getSearchCriteria(DBmysql $DB, string $table = "", array $apply_filters = []): array
    {
        $criteria = [];

        if (
            $DB->fieldExists($table, 'locations_id')
            && isset($apply_filters[self::getId()])
            && (int) $apply_filters[self::getId()] > 0
        ) {
            $criteria[] = [
                'link'       => 'AND',
                'field'      => self::getSearchOptionID($table, 'locations_id', 'glpi_locations'), // location
                'searchtype' => 'equals',
                'value'      => (int) $apply_filters[self::getId()]
            ];
        }

        return $criteria;
    }

    public static function getHtml($value): string
    {
        return self::displayList(
            self::getName(),
            is_string($value) ? $value : "",
            'location',
            Location::class
        );
    }
}
