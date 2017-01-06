<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Relation between item and devices
**/
class Item_DeviceHardDrive extends Item_Devices {

   static public $itemtype_2 = 'DeviceHardDrive';
   static public $items_id_2 = 'deviceharddrives_id';

   static protected $notable = false;


   /**
    * @since version 0.85
   **/
   static function getSpecificities($specif='') {

      return array('capacity' => array('long name'  => sprintf(__('%1$s (%2$s)'), __('Capacity'),
                                                               __('Mio')),
                                       'short name' => __('Capacity'),
                                       'size'       => 10,
                                       'id'         => 20),
                   'serial'   => parent::getSpecificities('serial'),
                   'busID'    => parent::getSpecificities('busID'));
   }

}
