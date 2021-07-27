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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;
use Glpi\ContentTemplates\Parameters\ParametersTypes\ArrayParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\ObjectParameter;
use Glpi\Toolbox\Sanitizer;
use Item_Ticket;
use KnowbaseItem;
use KnowbaseItem_Item;
use Location;
use OLA;
use RequestType;
use Session;
use SLA;
use Ticket;
use TicketValidation;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Parameters for "Ticket" items.
 *
 * @since 10.0.0
 */
class TicketParameters extends CommonITILObjectParameters
{
   public static function getDefaultNodeName(): string {
      return 'ticket';
   }

   public static function getObjectLabel(): string {
      return Ticket::getTypeName(1);
   }

   protected function getTargetClasses(): array {
      return [Ticket::class];
   }

   public function defineParameters(): array {
      return array_merge(parent::defineParameters(), [
         new AttributeParameter("type", _n('Type', 'Types', 1)),
         new AttributeParameter("global_validation", _n('Approval', 'Approvals', 1)),
         new AttributeParameter("tto", __('Time to own'), 'date("d/m/y H:i")'),
         new AttributeParameter("ttr", __('Time to resolve'), 'date("d/m/y H:i")'),
         new ObjectParameter(new SLAParameters(), 'sla_tto'),
         new ObjectParameter(new SLAParameters(), 'sla_ttr'),
         new ObjectParameter(new OLAParameters(), 'ola_tto'),
         new ObjectParameter(new OLAParameters(), 'ola_ttr'),
         new ObjectParameter(new RequestTypeParameters()),
         new ObjectParameter(new LocationParameters()),
         new ArrayParameter("knowbaseitems", new KnowbaseItemParameters(), KnowbaseItem_Item::getTypeName(Session::getPluralNumber())),
         new ArrayParameter("assets", new AssetParameters(), Item_Ticket::getTypeName(Session::getPluralNumber())),
      ]);
   }

   protected function defineValues(CommonDBTM $ticket): array {
      /** @var Ticket $ticket  */

      // Output "unsanitized" values
      $fields = Sanitizer::unsanitize($ticket->fields);

      $values = parent::defineValues($ticket);

      $values['type'] = $ticket::getTicketTypeName($fields['type']);
      $values['global_validation'] = TicketValidation::getStatus($fields['global_validation']);
      $values['tto'] = $fields['time_to_own'];
      $values['ttr'] = $fields['time_to_resolve'];

      // Add ticket's SLA / OLA
      $sla_parameters = new SLAParameters();
      if ($sla = SLA::getById($fields['slas_id_tto'])) {
         $values['sla_tto'] = $sla_parameters->getValues($sla);
      }
      if ($sla = SLA::getById($fields['slas_id_ttr'])) {
         $values['sla_ttr'] = $sla_parameters->getValues($sla);
      }
      $ola_parameters = new OLAParameters();
      if ($ola = OLA::getById($fields['olas_id_tto'])) {
         $values['ola_tto'] = $ola_parameters->getValues($ola);
      }
      if ($ola = OLA::getById($fields['olas_id_ttr'])) {
         $values['ola_ttr'] = $ola_parameters->getValues($ola);
      }

      // Add ticket's request type
      if ($requesttype = RequestType::getById($fields['requesttypes_id'])) {
         $requesttype_parameters = new RequestTypeParameters();
         $values['requesttype'] = $requesttype_parameters->getValues($requesttype);
      }

      // Add location
      if ($location = Location::getById($fields['locations_id'])) {
         $location_parameters = new LocationParameters();
         $values['location'] = $location_parameters->getValues($location);
      }

      // Add KBs
      $kbis = KnowbaseItem_Item::getItems($ticket);
      $values['knowbaseitems'] = [];
      foreach ($kbis as $data) {
         if ($kbi = KnowbaseItem::getById($data['id'])) {
            $kbi_parameters = new KnowbaseItemParameters();
            $values['knowbaseitems'][] = $kbi_parameters->getValues($kbi);
         }
      }

      // Add assets
      $values['assets'] = [];
      $items_ticket = Item_Ticket::getItemsAssociatedTo($ticket::getType(), $fields['id']);
      foreach ($items_ticket as $item_ticket) {
         $itemtype = $item_ticket->fields['itemtype'];
         if ($item = $itemtype::getById($item_ticket->fields['items_id'])) {
            $asset_parameters = new AssetParameters();
            $values['assets'][] = $asset_parameters->getValues($item);
         }
      }

      return $values;
   }
}
