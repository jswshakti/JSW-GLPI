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

use Glpi\Application\ErrorHandler;
use Glpi\Cache\CacheManager;
use Glpi\Cache\SimpleCache;
use Glpi\Kernel\Kernel;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

ini_set('display_errors', 'On'); // Ensure errors happening during test suite bootstrapping are always displayed
error_reporting(E_ALL);

define('GLPI_URI', getenv('GLPI_URI') ?: 'http://localhost:80');
define('GLPI_STRICT_DEPRECATED', true); //enable strict depreciations

define('TU_USER', '_test_user');
define('TU_PASS', 'PhpUnit_4');

define('FIXTURE_DIR', __DIR__ . "/../tests/fixtures");

global $CFG_GLPI, $GLPI_CACHE;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel('testing');
$kernel->loadCommonGlobalConfig();

if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
    die("\nConfiguration file for tests not found\n\nrun: php bin/console database:install --env=testing ...\n\n");
}

//init cache
if (file_exists(GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . CacheManager::CONFIG_FILENAME)) {
   // Use configured cache for cache tests
    $cache_manager = new CacheManager();
    $GLPI_CACHE = $cache_manager->getCoreCacheInstance();
} else {
   // Use "in-memory" cache for other tests
    $GLPI_CACHE = new SimpleCache(new ArrayAdapter());
}

include_once __DIR__ . '/GLPITestCase.php';
include_once __DIR__ . '/DbTestCase.php';
//include_once __DIR__ . '/CsvTestCase.php';
//include_once __DIR__ . '/APIBaseClass.php';
//include_once __DIR__ . '/FrontBaseClass.php';
include_once __DIR__ . '/InventoryTestCase.php';
//include_once __DIR__ . '/functional/CommonITILRecurrent.php';
//include_once __DIR__ . '/functional/Glpi/ContentTemplates/Parameters/AbstractParameters.php';
include_once __DIR__ . '/AbstractRightsDropdown.php';
include_once __DIR__ . '/CommonDropdown.php';

loadDataset();

$tu_oauth_client = new OAuthClient();
$tu_oauth_client->getFromDBByCrit(['name' => 'Test OAuth Client']);
define('TU_OAUTH_CLIENT_ID', $tu_oauth_client->fields['identifier']);
define('TU_OAUTH_CLIENT_SECRET', $tu_oauth_client->fields['secret']);

// There is no need to pollute the output with error messages.
ini_set('display_errors', 'Off');
ErrorHandler::getInstance()->disableOutput();
ErrorHandler::getInstance()->setForwardToInternalHandler(false);
