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

use Glpi\Application\View\TemplateRenderer;

class PrintPreview extends CommonDBTM
{
    /**
     * @param CommonGLPI $item
     * @param int $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            if ($item instanceof CommonDBTM) {
                $nb = 0;
                return self::createTabEntry(__('Print preview'), $nb, $item::getType(), 'ti ti-file-unknown');
            }
        }
        return '';
    }

    /**
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     * @return bool
     */
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {

        if ($item instanceof CommonDBTM) {
            if (isset($_SESSION['preview_printable_type' . $item->getType() . $item->getID()])) {
                $options = $_SESSION['preview_printable_typeComputer' . $item->getID()];
                self::showPreview($item, $options);
            } else {
                echo "Pas de preview";
            }
        }
        return true;
    }
    public static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = 0, CommonDBTM $checkitem = null)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $action_prefix = 'PrintPreview' . MassiveAction::CLASS_ACTION_SEPARATOR;
        $item = new $itemtype();
        if ($item instanceof CommonDBTM) {
            $actions[$action_prefix . 'print_preview'] = '<i class="' . self::getIcon() . '"> </i>' . __('Display to a printable view');
        }
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'print_preview':
                $items = $ma->getItems();
                $itemtypes = array_keys($items);
                $firstItemtype = reset($itemtypes);
                $firstitem = reset($items[$firstItemtype]);
                TemplateRenderer::getInstance()->display('pages/tools/print_preview.html.twig', [
                    'is_render' => false,
                    'tabs'      => self::getPrintableTabs($firstItemtype, $firstitem),
                    'items_id' => $firstitem,
                    'itemtype' => $firstItemtype,
                ]);
                return true;
        }
        return false;
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'print_preview':
                TemplateRenderer::getInstance()->render('pages/tools/print_preview.html.twig', [
                    'is_render' => true,
                ]);
        }
    }

    public static function showPreview($item, $options = [])
    {
        $unprintable = [
            'items_id' => '',
            'itemtype' => '',
            'csrf_token' => '',
            'generate_preview' => '',
        ];

        echo '<div class="preview">';
        $html = TemplateRenderer::getInstance()->render('generic_show_form.html.twig', [
            'item'   => $item,
            'params' => $options + ['formfooter' => false],
            'no_header' => false,
            'no_inventory_footer' => true,
            'no_form_buttons'   => true,
            'preview'        => true,
        ]);
        echo '
            <div class="card my-0 border-0 shadow-none">
                <div class="card-header">
                    <h4 class="card-title ps-4">
                        <i class="' . $item->getIcon() . '"></i> &nbsp' . $item->getTypeName(1) . '
                    </h4>
                </div>
            </div>
        ';
        echo $html;

        foreach (array_diff($options, $unprintable) as $key => $value) {
            if (
                (int) $value == 1
                && class_exists($key)
                && $key != $options['itemtype']
            ) {
                echo '
                    <div class="break"></div>
                    <div class="card my-0 border-0 shadow-none">
                        <div class="card-header">
                            <h4 class="card-title ps-4">
                                <i class="' . $key::getIcon() . '"></i> &nbsp' . ($key == 'Item_Devices' ? 'Components' : $key::getTypeName(0)) . '
                            </h4>
                        </div>
                    </div>
                ';
                $key::displayTabContentForItem($item, 0);
            }
        }
        echo '</div>';

        $js = <<<JS
            $(document).ready(() => {
                var preview = document.querySelector('.preview');
                console.log(preview);
                if (preview) {
                    preview.querySelectorAll('input').forEach(input => {
                        input.setAttribute('readonly', true);
                    });

                    var selectors = 'button,' +
                                    'input[type="button"],' +
                                    'input[type="submit"],' +
                                    'input[type="reset"],' +
                                    'input[type="checkbox"],' +
                                    '[class*="btn "],' +
                                    '[class*="search-pager"],' +
                                    '[class*="tab_cadre_pager"],' +
                                    '[class*="alert-"],' +
                                    '[class*="selection__arrow"],' +
                                    '[class*="fileupload"],' +
                                    '[class*="add_relation"],' +
                                    'form[action="contract_item"],' +
                                    'form[action*="asset_peripheralasset"],' +
                                    'form[action*="socket"],' +
                                    'form[action*="document"],' +
                                    'form[action*="item_line"],' +
                                    'form[action*="networkport"],' +
                                    'form[action*="item_softwarelicense"],' +
                                    'form[action*="item_softwareversion"],' +
                                    'form[action*="contract_item"],' +
                                    'form[action*="knowbaseitem_item"],' +
                                    'form[action*="certificate_item"],' +
                                    'form[action*="domain"],' +
                                    'form[action*="appliance_item"],' +
                                    'label[for*="pictures_"],' +
                                    'form[id*="form_device_add"],' +
                                    'span[class*="fa-plus pointer"]';

                    var elements = document.querySelectorAll(selectors);

                    elements.forEach(function(element) {
                        element.style.display = 'none';
                    });

                    var tables = preview.querySelectorAll('table, .table');
                    console.log(tables);

                    tables.forEach(function(table) {
                        table.style.width = '100%';
                        table.style.maxWidth = '100%';
                        table.style.boxSizing = 'border-box';
                        table.style.tableLayout = 'fixed';
                        table.style.overflowX = 'hidden';

                        var children = table.querySelectorAll('td, a, span');
                        children.forEach(function(child) {
                            child.style.whiteSpace = 'normal';
                            child.style.wordWrap = 'break-word';
                            child.style.overflowWrap = 'break-word';
                        });

                        var tfootAndNoHoverRows = table.querySelectorAll('tfoot, tr[class*="noHover"]');
                        tfootAndNoHoverRows.forEach(function(element) {
                            element.style.display = 'none';
                        });
                        var maxColumnCount = 0;

                        table.querySelectorAll('tr').forEach(function(row) {
                            var columnCount = row.children.length;
                            maxColumnCount = Math.max(maxColumnCount, columnCount);
                        });

                        if(maxColumnCount > 3) {
                            table.querySelectorAll('th').forEach(function(th) {
                                if(!th.hasAttribute('colspan') || th.getAttribute('colspan') < 3) {
                                    th.style.writingMode = 'vertical-rl';
                                    th.style.transform = 'rotate(180deg)';
                                    th.style.whiteSpace = 'nowrap';
                                    th.style.verticalAlign = 'top';
                                }
                            });
                        }
                    });
                }
            });
JS;
        echo Html::scriptBlock($js);

        return true;
    }

    /**
     * Get the form page URL for the current classe
     *
     * @param boolean $full  path or relative one
     **/
    public static function getFormURL($full = true)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');
        return "$dir/front/print_preview.form.php";
    }

    public static function getPrintableTypeLabel($itemtype, $item)
    {
        $instance = new $itemtype();
        $label = $instance->getTabNameForItem($item);
        if (is_array($label)) {
            $label = reset($label);
        }
        if (empty($label)) {
            $label = '<span><i class="' . $itemtype::getIcon() . ' me-2"></i>' . $itemtype::getTypeName(0) . '</span>';
        }
        return $label;
    }

    public static function getPrintableTabs($itemtype, $item_id)
    {
        $item = new $itemtype();
        $item->getFromDB($item_id);

        $tabKeys = array_keys($item->defineTabs());
        $tabKeys = array_map(function ($value) {
            $parts = explode('$', $value);
            return $parts[0];
        }, $tabKeys);
        $tabKeys = array_diff($tabKeys, self::getUnprintableTypes());

        foreach ($tabKeys as $tabKey) {
            $cleanTabs[$tabKey] = self::getPrintableTypeLabel($tabKey, $item);
        }
        return $cleanTabs ?? [];
    }

    public static function getUnprintableTypes()
    {
        return [
            \Impact::class,
            \Log::class,
            self::class,
        ];
    }

    public static function getIcon()
    {
        return 'ti ti-file-unknown';
    }
}
