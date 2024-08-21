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

namespace Glpi\Http;

use Session;

/**
 * @since 10.0.10
 */
final class Firewall implements FirewallInterface
{
    /**
     * Nothing to check. Entrypoint accepts anonymous access.
     */
    public const STRATEGY_NO_CHECK = 'no_check';

    /**
     * Check that user is authenticated.
     */
    public const STRATEGY_AUTHENTICATED = 'authenticated';

    /**
     * Check that user is authenticated and is using a profile based on central interface.
     */
    public const STRATEGY_CENTRAL_ACCESS = 'central_access';

    /**
     * Check that user is authenticated and is using a profile based on simplified interface.
     */
    public const STRATEGY_HELPDESK_ACCESS = 'helpdesk_access';

    /**
     * Check that FAQ access is allowed (unauthenticated if public FAQ is enabled, or by checking rights).
     */
    public const STRATEGY_FAQ_ACCESS = 'faq_access';

    /**
     * Security strategy to apply by default on core ajax/front scripts.
     */
    private const STRATEGY_DEFAULT_FOR_CORE = self::STRATEGY_AUTHENTICATED;

    /**
     * Security strategy to apply by default on plugin ajax/front scripts.
     *
     * @TODO In GLPI 11.0, raise default level to `self::STRATEGY_AUTHENTICATED`.
     */
    private const STRATEGY_DEFAULT_FOR_PLUGINS = self::STRATEGY_NO_CHECK;

    /**
     * GLPI URLs path prefix.
     * @var string
     */
    private string $path_prefix;

    /**
     * GLPI root directory.
     * @var string
     */
    private string $root_dir;

    /**
     * GLPI plugins root directories.
     * @var string[]
     */
    private array $plugins_dirs;

    /**
     * @param string  $path_prefix   GLPI URLs path prefix
     * @param ?string $root_dir      GLPI root directory on filesystem
     * @param ?array  $plugins_dirs  GLPI plugins root directories on filesystem
     */
    public function __construct(string $path_prefix, ?string $root_dir = null, ?array $plugins_dirs = null)
    {
        $this->path_prefix = $path_prefix;
        $this->root_dir = $root_dir ?? \GLPI_ROOT;
        $this->plugins_dirs = $plugins_dirs ?? \PLUGINS_DIRECTORIES;
    }

    public static function createDefault(): self
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        return new Firewall($CFG_GLPI['root_doc']);
    }

    public function applyStrategy(string $path, ?string $strategy): void
    {
        if ($strategy === null) {
            // If no strategy is defined for the route, use the fallback value.
            $strategy = $this->computeFallbackStrategy($path);
        }

        switch ($strategy) {
            case self::STRATEGY_AUTHENTICATED:
                Session::checkLoginUser();
                break;
            case self::STRATEGY_CENTRAL_ACCESS:
                Session::checkCentralAccess();
                break;
            case self::STRATEGY_HELPDESK_ACCESS:
                Session::checkHelpdeskAccess();
                break;
            case self::STRATEGY_FAQ_ACCESS:
                Session::checkFaqAccess();
                break;
            case self::STRATEGY_NO_CHECK:
                // nothing to do
                break;
            default:
                trigger_error(sprintf('Invalid `%s` strategy.', $strategy), E_USER_WARNING);
                break;
        }
    }

    /**
     * Compute the fallback strategy for given path.
     *
     * @param string $path  URL path
     * @return string
     */
    private function computeFallbackStrategy(string $path): string
    {
        if ($strategy = $this->computeSpecificStrategyForLegacyPaths($path)) {
            return $strategy;
        }

        // Check if entrypoint is a legacy GLPI core ajax/front script.
        if (
            str_starts_with($path, $this->path_prefix . '/ajax/')
            || str_starts_with($path, $this->path_prefix . '/front/')
        ) {
            return self::STRATEGY_DEFAULT_FOR_CORE;
        }

        // Check if entrypoint is a legacy plugin ajax/front script.
        foreach ($this->plugins_dirs as $plugins_dir) {
            $relative_path = preg_replace(
                '/^' . preg_quote($this->normalizePath($this->root_dir), '/') . '/',
                '',
                $this->normalizePath($plugins_dir)
            );

            if (preg_match('/^' . preg_quote($this->path_prefix . $relative_path, '/') . '\/[^\/]+\/(ajax|front)\/' . '/', $path) === 1) {
                // Entrypoint is a plugin ajax/front script.
                return self::STRATEGY_DEFAULT_FOR_PLUGINS;
            }
        }

        // No default security strategy for other entrypoints.
        return self::STRATEGY_NO_CHECK;
    }

    /**
     * Normalize a path, to make comparisons and relative paths computation easier.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $realpath = realpath($path);
        if ($realpath !== false) {
            // Use realpath if possible.
            // As `realpath()` will return `false` on streams, we cannot always use it, or we will not be able to do unit tests on this method.
            $path = $realpath;
        }

        // Normalize all directory separators to `/`.
        $path = preg_replace('/\\\/', '/', $path);
        return $path;
    }

    /**
     * Compute the specific strategy for legacy `/ajax` and `/front` paths.
     */
    private function computeSpecificStrategyForLegacyPaths(string $path): ?string
    {
        if (isset($_GET["embed"], $_GET["dashboard"]) && str_starts_with($path, $this->path_prefix . '/front/central.php')) {
            // Allow anonymous access for embed dashboards.
            return 'no_check';
        }

        if (isset($_GET["token"]) && str_starts_with($path, $this->path_prefix . '/front/planning.php')) {
            // Token based access for ical/webcal access can be made anonymously.
            return 'no_check';
        }

        $paths = [
            '/ajax/knowbase.php' => self::STRATEGY_FAQ_ACCESS,
            '/front/helpdesk.faq.php' => self::STRATEGY_FAQ_ACCESS,

            '/ajax/common.tabs.php' => self::STRATEGY_NO_CHECK, // specific checks done later to allow anonymous access to public FAQ tabs
            '/ajax/dashboard.php' => self::STRATEGY_NO_CHECK, // specific checks done later to allow anonymous access to embed dashboards
            '/ajax/telemetry.php' => self::STRATEGY_NO_CHECK, // Must be available during installation. This script already checks for permissions when the flag usually set by the installer is missing.
            '/front/cron.php' => self::STRATEGY_NO_CHECK, // in GLPI mode, cronjob can also be triggered from public pages
            '/front/css.php' => self::STRATEGY_NO_CHECK, // CSS must be accessible also on public pages
            '/front/document.send.php' => self::STRATEGY_NO_CHECK, // may allow unauthenticated access, for public FAQ images
            '/front/form/form_renderer.php' => self::STRATEGY_NO_CHECK, // Since forms may be available to unauthenticated users, we trust the `canAnswerForm` method to do the required session checks.
            '/front/helpdesk.php' => self::STRATEGY_NO_CHECK, // Anonymous access may be allowed by configuration.
            '/front/inventory.php' => self::STRATEGY_NO_CHECK, // allow anonymous requests from inventory agent
            '/front/locale.php' => self::STRATEGY_NO_CHECK, // locales must be accessible also on public pages
            '/front/login.php' => self::STRATEGY_NO_CHECK,
            '/front/logout.php' => self::STRATEGY_NO_CHECK,
            '/front/lostpassword.php' => self::STRATEGY_NO_CHECK,
            '/front/tracking.injector.php' => self::STRATEGY_NO_CHECK, // Anonymous access may be allowed by configuration.
            '/front/updatepassword.php' => self::STRATEGY_NO_CHECK,
        ];

        foreach ($paths as $checkPath => $strategy) {
            if (\str_starts_with($path, $this->path_prefix . $checkPath)) {
                return $strategy;
            }
        }

        return null;
    }
}
