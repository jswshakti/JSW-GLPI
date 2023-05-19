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

/**
 * @var DB $DB
 * @var Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

// Add ITILValidationTemplatesTargets table
if (!$DB->tableExists('glpi_itilvalidationtemplates_targets')) {
    $query = "CREATE TABLE `glpi_itilvalidationtemplates_targets` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `itilvalidationtemplates_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `itemtype` varchar(100) DEFAULT NULL,
        `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `groups_id` int unsigned DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `itilvalidationtemplates_id` (`itilvalidationtemplates_id`),
        KEY `item` (`itemtype`,`items_id`),
        KEY `groups_id` (`groups_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=$default_charset COLLATE=$default_collation ROW_FORMAT=DYNAMIC;";
    $DB->queryOrDie($query, 'x.x add table glpi_itilvalidationtemplates_targets');
}
