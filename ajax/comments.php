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

$AJAX_INCLUDE = 1;
include('../inc/includes.php');

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

/** @global array $CFG_GLPI */

if (
    isset($_POST["itemtype"])
    && isset($_POST["value"])
) {
   // Security
    if (!is_subclass_of($_POST["itemtype"], "CommonDBTM")) {
        exit();
    }

    switch ($_POST["itemtype"]) {
        case User::getType():
            if ($_POST['value'] == 0) {
                $tmpname = [
                    'link'    => $CFG_GLPI['root_doc'] . "/front/user.php",
                    'comment' => "",
                ];
            } else {
                if (is_array($_POST["value"])) {
                    $comments = [];
                    foreach ($_POST["value"] as $users_id) {
                        $username   = getUserName($users_id, 2);
                        $comments[] = $username['comment'] ?? "";
                    }
                    $tmpname = [
                        'comment' => implode("<br>", $comments),
                    ];
                    unset($_POST['withlink']);
                } else {
                    $tmpname = getUserName($_POST["value"], 2);
                }
            }
            echo $tmpname["comment"]; // `comment` field from `getUserName()` is expected to be a safe HTML string

            if (isset($_POST['withlink'])) {
                echo "<script>";
                echo Html::jsGetElementbyID($_POST['withlink']) . ".attr('href', " . json_encode($tmpname['link']) . ");";
                echo "</script>";
            }
            break;

        default:
            if ($_POST["value"] > 0) {
                if (
                    !Session::validateIDOR([
                        'itemtype'    => $_POST['itemtype'],
                        '_idor_token' => $_POST['_idor_token'] ?? ""
                    ])
                ) {
                    exit();
                }

                $itemtype = $_POST['itemtype'];
                if (is_subclass_of($itemtype, 'Rule')) {
                    $table = Rule::getTable();
                } else {
                    $table = getTableForItemType($_POST['itemtype']);
                }
                $tmpname = Dropdown::getDropdownName($table, $_POST["value"], 1);
                if (is_array($tmpname) && isset($tmpname["comment"])) {
                    echo $tmpname["comment"]; // `comment` field from `Dropdown::getDropdownName()` is expected to be a safe HTML string
                }

                if (isset($_POST['withlink'])) {
                    echo "<script>";
                    echo Html::jsGetElementbyID($_POST['withlink'])
                        . ".attr('href', " . json_encode($_POST['itemtype']::getFormURLWithID($_POST["value"])) . ");";
                    echo "</script>";
                }

                if (isset($_POST['with_dc_position'])) {
                    $item = new $_POST['itemtype']();
                    echo "<script>";

                    //if item have a DC position (reload url to it's rack)
                    if ($rack = $item->isRackPart($_POST['itemtype'], $_POST["value"], true)) {
                        $link = '&nbsp;<a class="fas fa-crosshairs" href="' . $rack->getLinkURL() . '"></a>';
                        echo Html::jsGetElementbyID($_POST['with_dc_position']) . ".html(" . json_encode($link) . ");";
                    } else {
                        //remove old dc position
                        echo Html::jsGetElementbyID($_POST['with_dc_position']) . ".empty();";
                    }
                    echo "</script>";
                }
            }
    }
}
