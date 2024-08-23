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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Override;

final class RequestSourceFieldConfig implements JsonFieldInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY = 'strategy';
    public const REQUEST_SOURCE = 'request_source';

    public function __construct(
        private RequestSourceFieldStrategy $strategy,
        private ?int $specific_request_source = null,
    ) {
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategy = RequestSourceFieldStrategy::tryFrom($data[self::STRATEGY] ?? "");
        if ($strategy === null) {
            $strategy = RequestSourceFieldStrategy::FROM_TEMPLATE;
        }

        return new self(
            strategy: $strategy,
            specific_request_source: $data[self::REQUEST_SOURCE],
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY => $this->strategy->value,
            self::REQUEST_SOURCE => $this->specific_request_source,
        ];
    }

    public function getStrategy(): RequestSourceFieldStrategy
    {
        return $this->strategy;
    }

    public function getSpecificRequestSource(): ?int
    {
        return $this->specific_request_source;
    }
}
