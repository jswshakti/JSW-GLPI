<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

namespace tests\units\Glpi\Cache;

/**
 * Test class for src/Glpi/Cache/CacheStorageFactory.php.
 */
class CacheStorageFactory extends \GLPITestCase {

   /**
    * Mapping between cache configuration and built adapter.
    *
    * @return array
    */
   protected function factoryProvider(): array {
      return [
         // Case: auto adapter without options
         [
            'config'   => [
                'adapter' => 'auto',
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Apcu::class,
               'options' => [],
               'plugins' => [],
            ],
         ],

         // Case: auto adapter with options
         [
            'config'   => [
                'adapter' => 'auto',
                'options' => [
                   'namespace' => 'app_cache',
                ],
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Apcu::class,
               'options' => [
                  'namespace' => 'app_cache',
               ],
               'plugins' => [
               ],
            ],
         ],

         // Case: dba adapter using default options
         /* Cannot test without extension loaded
         [
            'config'   => [
                'adapter' => 'dba',
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Dba::class,
               'options' => [
                  'pathname' => GLPI_CACHE_DIR . '/_default.data',
               ],
               'plugins' => [
                  'serializer'
               ],
            ],
         ],
         */

         // Case: filesystem adapter using default options
         [
            'config'   => [
                'adapter' => 'filesystem',
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Filesystem::class,
               'options' => [
                  'cache_dir' => GLPI_CACHE_DIR . '/_default',
               ],
               'plugins' => [
                  \Zend\Cache\Storage\Plugin\Serializer::class
               ],
            ],
         ],

         // Case: filesystem adapter using custom namespace
         [
            'config'   => [
                'adapter' => 'filesystem',
                'options' => [
                   'namespace' => 'my_cache',
                ],
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Filesystem::class,
               'options' => [
                  'namespace' => 'my_cache',
                  'cache_dir' => GLPI_CACHE_DIR . '/my_cache',
               ],
               'plugins' => [
                  \Zend\Cache\Storage\Plugin\Serializer::class
               ],
            ],
         ],

         // Case: filesystem adapter using custom directory
         [
            'config'   => [
                'adapter' => 'filesystem',
                'options' => [
                   'namespace' => 'my_cache',
                   'cache_dir' => '/tmp/cache',
                ],
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Filesystem::class,
               'options' => [
                  'namespace' => 'my_cache',
                  'cache_dir' => '/tmp/cache',
               ],
               'plugins' => [
                  \Zend\Cache\Storage\Plugin\Serializer::class
               ],
            ],
         ],

         // Case: redis adapter using default options
         /* Cannot test without extension loaded
         [
            'config'   => [
                'adapter' => 'redis',
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Redis::class,
               'plugins' => [
                  'serializer'
               ],
            ],
         ],
         */
      ];
   }

   /**
    * Test that built adapter matches configuration.
    *
    * @dataProvider factoryProvider
    */
   public function testFactory(array $config, array $expectedAdapter) {

      $this->newTestedInstance(GLPI_CACHE_DIR, '');

      /* @var \Zend\Cache\Storage\Adapter\AbstractAdapter $adapter */
      $adapter = $this->testedInstance->factory($config);

      $this->object($adapter)->isInstanceOf($expectedAdapter['class']);

      if (!empty($expectedAdapter['options'])) {
         $adapterOptions = $adapter->getOptions()->toArray();
         foreach ($expectedAdapter['options'] as $key => $value) {
            $this->array($adapterOptions)->hasKey($key);
            $this->variable($adapterOptions[$key])->isEqualTo($value);
         }
      }

      if (!empty($expectedAdapter['plugins'])) {
         foreach ($expectedAdapter['plugins'] as $pluginClass) {
            $pluginFound = false;
            foreach ($adapter->getPluginRegistry() as $existingPlugin) {
               if ($existingPlugin instanceof $pluginClass) {
                  $pluginFound = true;
               }
            }
            $this->boolean($pluginFound)->isTrue();
         }
      }
   }

   /**
    * Test that factory fallback to filesystem adapter if requested adapter not working.
    */
   public function testFactoryFallbackToFilesystem() {

      $uniqId = uniqid();

      $this->newTestedInstance(GLPI_CACHE_DIR, $uniqId);

      $self = $this;
      $adapter = null;

      $this->when(
         function() use ($self, &$adapter) {
            $adapter = $self->testedInstance->factory(
               [
                  'adapter' => 'invalid'
               ]
            );
         }
      )->error()
         ->withType(E_USER_WARNING)
         ->withPattern('/^Cache adapter instantiation failed, fallback to "filesystem" adapter./')
            ->exists();

      $this->object($adapter)->isInstanceOf(\Zend\Cache\Storage\Adapter\Filesystem::class);

      $adapterOptions = $adapter->getOptions()->toArray();
      $this->array($adapterOptions)->hasKey('namespace');
      $this->variable($adapterOptions['namespace'])->isEqualTo('_default_fallback_' . $uniqId);
      $this->array($adapterOptions)->hasKey('cache_dir');
      $this->variable($adapterOptions['cache_dir'])->isEqualTo(GLPI_CACHE_DIR . '/_default_fallback_' . $uniqId);
   }

   /**
    * Test that factory fallback to memory adapter if filesystem adapter not working.
    */
   public function testFactoryFallbackToMemory() {

      $uniqId = uniqid();

      $this->newTestedInstance(GLPI_CACHE_DIR, $uniqId);

      $self = $this;
      $adapter = null;

      $this->when(
         function() use ($self, &$adapter) {
            $adapter = $self->testedInstance->factory(
               [
                  'adapter' => 'filesystem',
                  'options' => [
                     'cache_dir' => '/this/directory/cannot/be/created',
                  ],
               ]
            );
         }
      )->error()
         ->withType(E_USER_WARNING)
         ->withMessage('Cannot create "/this/directory/cannot/be/created" cache directory.')
            ->exists()
       ->error
         ->withType(E_USER_WARNING)
         ->withPattern('/^Cache adapter instantiation failed, fallback to "memory" adapter./')
            ->exists();

      $this->object($adapter)->isInstanceOf(\Zend\Cache\Storage\Adapter\Memory::class);

      $adapterOptions = $adapter->getOptions()->toArray();
      $this->array($adapterOptions)->hasKey('namespace');
      $this->variable($adapterOptions['namespace'])->isEqualTo('_default_fallback_' . $uniqId);
   }
}
