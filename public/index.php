<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Kernel\Kernel;
use Symfony\Component\HttpFoundation\Request;

// Check PHP version not to have trouble
// Need to be the very fist step before any include
if (version_compare(PHP_VERSION, '8.2.0', '<') || version_compare(PHP_VERSION, '8.3.999', '>')) {
    exit('PHP version must be between 8.2 and 8.3.');
}

// Check the resources state before trying to instanciate the Kernel.
// It must be done here as this check must be done even when the Kernel
// cannot be instanciated due to missing dependencies.
require_once dirname(__DIR__) . '/src/Glpi/Application/ResourcesChecker.php';
(new \Glpi\Application\ResourcesChecker(dirname(__DIR__)))->checkResources();

require_once dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel();

$request = Request::createFromGlobals();

$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);
