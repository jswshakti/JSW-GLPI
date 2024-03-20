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

namespace tests\units;

use DbTestCase;

/* Test for inc/networkport.class.php */

class NetworkPort extends DbTestCase
{
    public function testAddSimpleNetworkPort()
    {
        $this->login();

        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $networkport = new \NetworkPort();

       // Be sure added
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new_id = $networkport->add([
            'items_id'           => $computer1->getID(),
            'itemtype'           => 'Computer',
            'entities_id'        => $computer1->fields['entities_id'],
            'is_recursive'       => 0,
            'logical_number'     => 1,
            'mac'                => '00:24:81:eb:c6:d0',
            'instantiation_type' => 'NetworkPortEthernet',
            'name'               => 'eth1',
        ]);
        $this->integer((int)$new_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       // check data in db
        $all_netports = getAllDataFromTable('glpi_networkports', ['ORDER' => 'id']);
        $current_networkport = end($all_netports);
        unset($current_networkport['id']);
        unset($current_networkport['date_mod']);
        unset($current_networkport['date_creation']);
        $expected = [
            'items_id' => $computer1->getID(),
            'itemtype' => 'Computer',
            'entities_id' => $computer1->fields['entities_id'],
            'is_recursive' => 0,
            'logical_number' => 1,
            'name' => 'eth1',
            'instantiation_type' => 'NetworkPortEthernet',
            'mac' => '00:24:81:eb:c6:d0',
            'comment' => null,
            'is_deleted' => 0,
            'is_dynamic' => 0,
            'ifmtu' => 0,
            'ifspeed' => 0,
            'ifinternalstatus' => null,
            'ifconnectionstatus' => 0,
            'iflastchange' => null,
            'ifinbytes' => 0,
            'ifinerrors' => 0,
            'ifoutbytes' => 0,
            'ifouterrors' => 0,
            'ifstatus' => null,
            'ifdescr' => null,
            'ifalias' => null,
            'portduplex' => null,
            'trunk' => 0,
            'lastup' => null
        ];
        $this->array($current_networkport)->isIdenticalTo($expected);

        $all_netportethernets = getAllDataFromTable('glpi_networkportethernets', ['ORDER' => 'id']);
        $networkportethernet = end($all_netportethernets);
        $this->boolean($networkportethernet)->isFalse();

       // be sure added and have no logs
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new_id = $networkport->add([
            'items_id'           => $computer1->getID(),
            'itemtype'           => 'Computer',
            'entities_id'        => $computer1->fields['entities_id'],
            'logical_number'     => 2,
            'mac'                => '00:24:81:eb:c6:d1',
            'instantiation_type' => 'NetworkPortEthernet',
        ], [], false);
        $this->integer((int)$new_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isIdenticalTo($nb_log);
    }

    public function testAddCompleteNetworkPort()
    {
        $this->login();

        $computer1 = getItemByTypeName('Computer', '_test_pc01');

       // Do some installations
        $networkport = new \NetworkPort();

       // Be sure added
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new_id = $networkport->add([
            'items_id'                    => $computer1->getID(),
            'itemtype'                    => 'Computer',
            'entities_id'                 => $computer1->fields['entities_id'],
            'is_recursive'                => 0,
            'logical_number'              => 3,
            'mac'                         => '00:24:81:eb:c6:d2',
            'instantiation_type'          => 'NetworkPortEthernet',
            'name'                        => 'em3',
            'comment'                     => 'Comment me!',
            'items_devicenetworkcards_id' => 0,
            'type'                        => 'T',
            'speed'                       => 1000,
            'speed_other_value'           => '',
            'NetworkName_name'            => 'test1',
            'NetworkName_comment'         => 'test1 comment',
            'NetworkName_fqdns_id'        => 0,
            'NetworkName__ipaddresses'    => ['-1' => '192.168.20.1'],
            '_create_children'            => true // automatically add instancation, networkname and ipadresses
        ]);
        $this->integer($new_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       // check data in db
       // 1 -> NetworkPortEthernet
        $all_netportethernets = getAllDataFromTable('glpi_networkportethernets', ['ORDER' => 'id']);
        $networkportethernet = end($all_netportethernets);
        unset($networkportethernet['id']);
        unset($networkportethernet['date_mod']);
        unset($networkportethernet['date_creation']);
        $expected = [
            'networkports_id'             => $new_id,
            'items_devicenetworkcards_id' => 0,
            'type'                        => 'T',
            'speed'                       => 1000,
        ];
        $this->array($networkportethernet)->isIdenticalTo($expected);

       // 2 -> NetworkName
        $all_networknames = getAllDataFromTable('glpi_networknames', ['ORDER' => 'id']);
        $networkname = end($all_networknames);
        $networknames_id = $networkname['id'];
        unset($networkname['id']);
        unset($networkname['date_mod']);
        unset($networkname['date_creation']);
        $expected = [
            'entities_id'   => $computer1->fields['entities_id'],
            'items_id'      => $new_id,
            'itemtype'      => 'NetworkPort',
            'name'          => 'test1',
            'comment'       => 'test1 comment',
            'fqdns_id'      => 0,
            'ipnetworks_id' => 0,
            'is_deleted'    => 0,
            'is_dynamic'    => 0,
        ];
        $this->array($networkname)->isIdenticalTo($expected);

       // 3 -> IPAddress
        $all_ipadresses = getAllDataFromTable('glpi_ipaddresses', ['ORDER' => 'id']);
        $ipadress = end($all_ipadresses);
        unset($ipadress['id']);
        unset($ipadress['date_mod']);
        unset($ipadress['date_creation']);
        $expected = [
            'entities_id'  => $computer1->fields['entities_id'],
            'items_id'     => $networknames_id,
            'itemtype'     => 'NetworkName',
            'version'      => 4,
            'name'         => '192.168.20.1',
            'binary_0'     => 0,
            'binary_1'     => 0,
            'binary_2'     => 65535,
            'binary_3'     => 3232240641,
            'is_deleted'   => 0,
            'is_dynamic'   => 0,
            'mainitems_id' => $computer1->getID(),
            'mainitemtype' => 'Computer',
        ];
        $this->array($ipadress)->isIdenticalTo($expected);

       // be sure added and have no logs
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new_id = $networkport->add([
            'items_id'                    => $computer1->getID(),
            'itemtype'                    => 'Computer',
            'entities_id'                 => $computer1->fields['entities_id'],
            'is_recursive'                => 0,
            'logical_number'              => 4,
            'mac'                         => '00:24:81:eb:c6:d4',
            'instantiation_type'          => 'NetworkPortEthernet',
            'name'                        => 'em4',
            'comment'                     => 'Comment me!',
            'sockets_id'                => 0,
            'items_devicenetworkcards_id' => 0,
            'type'                        => 'T',
            'speed'                       => 1000,
            'speed_other_value'           => '',
            'NetworkName_name'            => 'test2',
            'NetworkName_fqdns_id'        => 0,
            'NetworkName__ipaddresses'    => ['-1' => '192.168.20.2']
        ], [], false);
        $this->integer((int)$new_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isIdenticalTo($nb_log);
    }

    public function testClone()
    {
        $this->login();

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $computer1 = getItemByTypeName('Computer', '_test_pc01');

       // Do some installations
        $networkport = new \NetworkPort();

       // Be sure added
        $nb_log = (int)countElementsInTable('glpi_logs');
        $new_id = $networkport->add([
            'items_id'                    => $computer1->getID(),
            'itemtype'                    => 'Computer',
            'entities_id'                 => $computer1->fields['entities_id'],
            'is_recursive'                => 0,
            'logical_number'              => 3,
            'mac'                         => '00:24:81:eb:c6:d2',
            'instantiation_type'          => 'NetworkPortEthernet',
            'name'                        => 'em3',
            'comment'                     => 'Comment me!',
            'sockets_id'                => 0,
            'items_devicenetworkcards_id' => 0,
            'type'                        => 'T',
            'speed'                       => 1000,
            'speed_other_value'           => '',
            'NetworkName_name'            => 'test1',
            'NetworkName_comment'         => 'test1 comment',
            'NetworkName_fqdns_id'        => 0,
            'NetworkName__ipaddresses'    => ['-1' => '192.168.20.1'],
            '_create_children'            => true // automatically add instancation, networkname and ipadresses
        ]);
        $this->integer($new_id)->isGreaterThan(0);
        $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

       // Test item cloning
        $added = $networkport->clone();
        $this->integer((int)$added)->isGreaterThan(0);

        $clonedNetworkport = new \NetworkPort();
        $this->boolean($clonedNetworkport->getFromDB($added))->isTrue();

        $fields = $networkport->fields;

       // Check the networkport values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->variable($clonedNetworkport->getField($k))->isNotEqualTo($networkport->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedNetworkport->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->dateTime($dateClone)->isEqualTo($expectedDate);
                    break;
                case 'name':
                    $this->variable($clonedNetworkport->getField($k))->isEqualTo("{$networkport->getField($k)} (copy)");
                    break;
                default:
                    $this->variable($clonedNetworkport->getField($k))->isEqualTo($networkport->getField($k));
            }
        }

        $instantiation = $networkport->getInstantiation();
        $clonedInstantiation = $clonedNetworkport->getInstantiation();
        $instantiationFields = $networkport->fields;

       // Check the networkport instantiation values. Id, networkports_id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->variable($clonedInstantiation->getField($k))->isNotEqualTo($instantiation->getField($k));
                    break;
                case 'networkports_id':
                    $this->variable($clonedInstantiation->getField($k))->isNotEqualTo($instantiation->getField($k));
                    $this->variable($clonedInstantiation->getField($k))->isEqualTo($clonedNetworkport->getID());
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedInstantiation->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->dateTime($dateClone)->isEqualTo($expectedDate);
                    break;
                default:
                    $this->variable($clonedInstantiation->getField($k))->isEqualTo($instantiation->getField($k));
            }
        }
    }

    public function testClearSavedInputAfterUpdate()
    {
        $this->login();

        // Check that there is no saveInput already
        if (isset($_SESSION['saveInput']) && is_array($_SESSION['saveInput'])) {
            $this->array($_SESSION['saveInput'])->notHasKey('NetworkPort');
        }
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $networkport = new \NetworkPort();

        // Be sure added
        $np_id = $networkport->add([
            'items_id'           => $computer1->getID(),
            'itemtype'           => 'Computer',
            'entities_id'        => $computer1->fields['entities_id'],
            'is_recursive'       => 0,
            'logical_number'     => 5,
            'mac'                => '00:24:81:eb:c6:d5',
            'instantiation_type' => 'NetworkPortEthernet',
            'name'               => 'eth1',
        ]);
        $this->integer((int)$np_id)->isGreaterThan(0);

        $result = $networkport->update([
            'id'                 => $np_id,
            'comment'            => 'test',
        ]);
        $this->boolean($result)->isTrue();

        // Check that there is no savedInput after update
        if (isset($_SESSION['saveInput']) && is_array($_SESSION['saveInput'])) {
            $this->array($_SESSION['saveInput'])->notHasKey('NetworkPort');
        }
    }

    public function testShowForItem()
    {
        $this->login();

        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $netport = new \NetworkPort();

        // Add a network port
        $np_id = $netport->add([
            'items_id'           => $computer1->getID(),
            'itemtype'           => 'Computer',
            'entities_id'        => $computer1->fields['entities_id'],
            'is_recursive'       => 0,
            'logical_number'     => 6,
            'mac'                => '00:24:81:eb:c6:d6',
            'instantiation_type' => 'NetworkPortEthernet',
            'name'               => 'eth1',
        ]);
        $this->integer((int)$np_id)->isGreaterThan(0);

        // Display all columns
        $so = $netport->rawSearchOptions();
        $displaypref = new \DisplayPreference();
        $so_display = [];
        foreach ($so as $column) {
            if (isset($column['field'])) {
                $input = [
                    'itemtype' => 'NetworkPort',
                    'users_id' => \Session::getLoginUserID(),
                    'num' => $column['id'],
                ];
                $this->integer((int) $displaypref->add($input))->isGreaterThan(0);
                $so_display[] = $column;
            }
        }

        // Check that all columns are displayed and correct values
        foreach (['showForItem', 'displayTabContentForItem'] as $method) {
            $result = $this->output(
                function () use ($method, $computer1) {
                    \NetworkPort::$method($computer1);
                }
            );
            foreach ($so_display as $column) {
                $result->contains($column['name']);
                if (isset($netport->fields[$column['field']])) {
                    $result->contains($netport->fields[$column['field']]);
                }
            }
        }
    }
}
