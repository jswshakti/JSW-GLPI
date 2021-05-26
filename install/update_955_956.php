<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * Update from 9.5.5 to 9.5.6
 *
 * @return bool for success (will die for most error)
 **/
function update955to956() {
   /** @global Migration $migration */
   global $DB, $migration, $CFG_GLPI;

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];
   $init_date = false;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.5.6'));
   $migration->setVersion('9.5.6');

   // Change DC itemtype template_name search option ID from 50 to 61 to prevent duplicate IDs now that those itemtypes have Infocom search options.
   $migration->changeSearchOption(Enclosure::class, 50, 61);
   $migration->changeSearchOption(PassiveDCEquipment::class, 50, 61);
   $migration->changeSearchOption(PDU::class, 50, 61);
   $migration->changeSearchOption(Rack::class, 50, 61);

   /* Add `date` to some glpi_documents_items */
   if (!$DB->fieldExists('glpi_documents_items', 'date')) {
      $init_date = true;
      $migration->addField('glpi_documents_items', 'date', 'timestamp');
      $migration->addKey('glpi_documents_items', 'date');
   }
   /* /Add `date` to glpi_documents_items */

   // ************ Keep it at the end **************
   $migration->executeMigration();

   // Must be run after migration is executed so we can access the new fields
   if ($init_date) {
      // Init date from the parent followup
      $parent_date = new QuerySubQuery([
         'SELECT' => 'date',
         'FROM' => 'glpi_itilfollowups',
         'WHERE' => [
            'id' => new QueryExpression($DB->quoteName('glpi_documents_items.items_id'))
         ]
      ]);
      $DB->update(
         'glpi_documents_items',
         ['date' => new QueryExpression($parent_date->getQuery())],
         ['itemtype' => ['ITILFollowup']]
      );

      // Init date as the value of date_creation for others items
      $DB->update(
         'glpi_documents_items',
         ['date' => new QueryExpression($DB->quoteName('glpi_documents_items.date_creation'))],
         ['itemtype' => ['!=', 'ITILFollowup']]
      );
   }

   return $updateresult;
}
