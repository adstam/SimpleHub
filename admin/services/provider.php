<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use StamPlusJ\Component\Simplehub\Administrator\Extension\SimplehubComponent;

return new class () implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\StamPlusJ\\Component\\Simplehub'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\StamPlusJ\\Component\\Simplehub'));
        $container->registerServiceProvider(new RouterFactory('\\StamPlusJ\\Component\\Simplehub'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new SimplehubComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );
    }
};