<?php
/*
* @version $Id$
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// NetworkPortFiberchannel class : Fiberchannel instantiation of NetworkPort
/// @since 0.91
class NetworkPortFiberchannel extends NetworkPortInstantiation {


   static function getTypeName($nb=0) {
      return __('Fiber channel port');
   }


   function getNetworkCardInterestingFields() {
      return array('link.`mac`' => 'mac');
   }


   function prepareInput($input) {

      if (isset($input['speed']) && ($input['speed'] == 'speed_other_value')) {
         $speed = self::transformPortSpeed($input['speed_other_value'], false);
         if ($speed === false) {
            unset($input['speed']);
         } else {
            $input['speed'] = $speed;
         }
      }
      return $input;
   }


   function prepareInputForAdd($input) {
      return parent::prepareInputForAdd($this->prepareInput($input));
   }


   function prepareInputForUpdate($input) {
      return parent::prepareInputForUpdate($this->prepareInput($input));
   }


   /**
    * @see NetworkPortInstantiation::showInstantiationForm()
    */
   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'>";
         $this->showNetpointField($netport, $options, $recursiveItems);
         $this->showNetworkCardField($netport, $options, $recursiveItems);
         echo "</tr>\n";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('World Wide Name') . "</td><td>\n";
      Html::autocompletionTextField($this, 'wwn', array('value' => $this->fields['wwn']));
      echo "</td>";
      echo "<td>" . __('Fiber channel port speed') . "</td><td>\n";

      $standard_speeds = self::getPortSpeed();
      if (!isset($standard_speeds[$this->fields['speed']])
          && !empty($this->fields['speed'])) {
         $speed = self::transformPortSpeed($this->fields['speed'], true);
      } else {
         $speed = true;
      }

      Dropdown::showFromArray('speed', $standard_speeds,
                              array('value' => $this->fields['speed'],
                                    'other' => $speed));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>\n";
      $this->showMacField($netport, $options);
      echo "<td>".__('Connected to').'</td><td>';
      self::showConnection($netport, true);
      echo "</td></tr>\n";
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTableHeaders
   **/
   function getInstantiationHTMLTableHeaders(HTMLTableGroup $group, HTMLTableSuperHeader $super,
                                             HTMLTableSuperHeader $internet_super=NULL,
                                             HTMLTableHeader $father=NULL,
                                             array $options=array()) {

      $display_options = &$options['display_options'];
      $header          = $group->addHeader('Connected', __('Connected to'), $super);

      DeviceNetworkCard::getHTMLTableHeader('NetworkPortFiberchannel', $group, $super, $header,
                                            $options);

      $group->addHeader('speed', __('Fiber channel port speed'), $super, $header);
      $group->addHeader('wwn', __('World Wide Name'), $super, $header);

      Netpoint::getHTMLTableHeader('NetworkPortFiberchannel', $group, $super, $header, $options);

      $group->addHeader('Outlet', __('Network outlet'), $super, $header);

      parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $header, $options);
      return $header;
   }


   /**
    * @see NetworkPortInstantiation::getPeerInstantiationHTMLTable()
    **/
   protected function getPeerInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                                    HTMLTableCell $father=NULL,
                                                    array $options=array()) {

      DeviceNetworkCard::getHTMLTableCellsForItem($row, $this, $father, $options);

      if (!empty($this->fields['speed'])) {
         $row->addCell($row->getHeaderByName('Instantiation', 'speed'),
                       self::getPortSpeed($this->fields["speed"]), $father);
      }

      if (!empty($this->fields['wwn'])) {
         $row->addCell($row->getHeaderByName('Instantiation', 'wwn'), $this->fields["wwn"], $father);
      }

      parent::getInstantiationHTMLTable($netport, $row, $father, $options);
      Netpoint::getHTMLTableCellsForItem($row, $this, $father, $options);
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTable()
    **/
   function getInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                      HTMLTableCell $father=NULL, array $options=array()) {

      return parent::getInstantiationHTMLTableWithPeer($netport, $row, $father, $options);
   }


   // TODO why this? you don't have search engine for this object
   function getSearchOptions() {

      $tab                      = array();
      $tab['common']            = __('Characteristics');

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'mac';
      $tab[10]['datatype']      = 'mac';
      $tab[10]['name']          = __('MAC');
      $tab[10]['massiveaction'] = false;

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'wwn';
      $tab[11]['name']          = __('World Wide Name');
      $tab[11]['massiveaction'] = false;

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'speed';
      $tab[12]['name']          = __('Fiber channel port speed');
      $tab[12]['massiveaction'] = false;
      $tab[12]['datatype']      = 'specific';

      return $tab;
   }


   /**
    * Transform a port speed from string to integerer and vice-versa
    *
    * @param $val       port speed (integer or string)
    * @param $to_string (boolean) true if we must transform the speed to string
    *
    * @return integer or string (regarding what is requested)
   **/
   static function transformPortSpeed($val, $to_string) {

      if ($to_string) {
         if (($val % 1000) == 0) {
            //TRANS: %d is the speed
            return sprintf(__('%d Gbit/s'), $val / 1000);
         }

         if ((($val % 100) == 0) && ($val > 1000)) {
            $val /= 100;
            //TRANS: %f is the speed
            return sprintf(__('%.1f Gbit/s'), $val / 10);
         }

         //TRANS: %d is the speed
         return sprintf(__('%d Mbit/s'), $val);
      }

      $val = preg_replace( '/\s+/', '', strtolower($val));

      $number = sscanf($val, "%f%s", $speed, $unit);
      if ($number != 2) {
         return false;
      }

      if (($unit == 'mbit/s') || ($unit == 'mb/s')) {
         return (int)$speed;
      }

      if (($unit == 'gbit/s') || ($unit == 'gb/s')) {
         return (int)($speed * 1000);
      }

      return false;
   }


   /**
    * Get the possible value for Fiberchannel port speed
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getPortSpeed($val=NULL) {

      $tmp = array(0     => '',
                   //TRANS: %d is the speed
                   10    => sprintf(__('%d Mbit/s'),10),
                   100   => sprintf(__('%d Mbit/s'),100),
                   //TRANS: %d is the speed
                   1000  => sprintf(__('%d Gbit/s'),1),
                   10000 => sprintf(__('%d Gbit/s'),10));

      if (is_null($val)) {
         return $tmp;
      }
      if (isset($tmp[$val])) {
         return $tmp[$val];
      }
      return self::transformPortSpeed($val, true);
   }


   /**
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'speed':
            return self::getPortSpeed($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param $field
    * @param $name            (default '')
    * @param $values          (defaul '')
    * @param $options   array
    */
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {

         case 'speed':
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, self::getPortSpeed(), $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @param $tab         array
    * @param $joinparams  array
    * @param $itemtype
   **/
   static function getSearchOptionsToAddForInstantiation(array &$tab, array $joinparams,
                                                         $itemtype) {

      $tab[22]['table']         = 'glpi_netpoints';
      $tab[22]['field']         = 'name';
      $tab[22]['datatype']      = 'dropdown';
      $tab[22]['name']          = __('Network outlet');
      $tab[22]['forcegroupby']  = true;
      $tab[22]['massiveaction'] = false;
      $tab[22]['joinparams']    = array('jointype'   => 'standard',
                                        'beforejoin' => array('table' => 'glpi_networkportfiberchannels',
                                                              'joinparams'
                                                                      => $joinparams));
   }

}
