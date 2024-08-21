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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Glpi\Config\LegacyConfigProviderInterface;
use Glpi\Config\LegacyConfigurators\AssetsAutoloader;
use Glpi\Config\LegacyConfigurators\AssetsBootstrap;
use Glpi\Config\LegacyConfigurators\CleanPHPSelfParam;
use Glpi\Config\LegacyConfigurators\ConfigRest;
use Glpi\Config\LegacyConfigurators\InitializePlugins;
use Glpi\Config\LegacyConfigurators\ProfilerStart;
use Glpi\Config\LegacyConfigurators\SessionConfig;
use Glpi\Config\LegacyConfigurators\SessionStart;
use Glpi\Config\LegacyConfigurators\StandardIncludes;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set('glpi_db')->synthetic();

    $tagName = LegacyConfigProviderInterface::TAG_NAME;

    /*
     * ⚠ Warning!
     * ⚠ Here, ORDER of definition matters!
     */

    $services->set(ProfilerStart::class)->tag($tagName, ['priority' => 180]);
    $services->set(SessionStart::class)->tag($tagName, ['priority' => 170]);
    $services->set(StandardIncludes::class)->tag($tagName, ['priority' => 160]);
    $services->set(CleanPHPSelfParam::class)->tag($tagName, ['priority' => 150]);
    $services->set(SessionConfig::class)->tag($tagName, ['priority' => 130]);

    // Must be done before plugins initialization, to allow plugin to work with concrete class names.
    $services->set(AssetsAutoloader::class)->tag($tagName, ['priority' => 120]);

    $services->set(InitializePlugins::class)->tag($tagName, ['priority' => 110]);

    // Must be done after plugins initialization, to allow plugin to register new capacities.
    $services->set(AssetsBootstrap::class)->tag($tagName, ['priority' => 100]);

    // FIXME: This class MUST stay at the end until the entire config is revamped.
    $services->set(ConfigRest::class)->tag($tagName, ['priority' => 10]);
};
