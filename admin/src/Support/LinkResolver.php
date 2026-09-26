<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\Support;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

final class LinkResolver
{
    /**
     * Administrator-menu, eenmaal per request geladen.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $adminMenuById = [];

    /**
     * @var bool
     */
    private bool $adminMenuLoaded = false;

    /**
     * Resolve een SimpleHub-link.
     *
     * @return array{
     *     link:string|null,
     *     status:string,
     *     icon_type:string,
     *     icon:string
     * }
     */
    public function resolve(string $type, string $target): array
    {
        $type   = trim($type);
        $target = trim($target);

        return match ($type) {
            'component' => $this->resolveComponent($target),
            'plugin'    => $this->resolvePlugin($target),
            'module'    => $this->resolveModule($target),
            'article'   => $this->resolveArticle($target),
            'external'  => $this->resolveExternal($target),
            default     => [
                'link'      => null,
                'status'    => 'unavailable',
                'icon_type' => 'class',
                'icon'      => 'link',
            ],
        };
    }

    /**
     * Resolve een externe URL.
     *
     * De URL wordt rechtstreeks gebruikt als link.
     * Het icoon is bewust onafhankelijk van het Joomla-menu:
     * externe links krijgen altijd het globe-icoon.
     *
     * @return array{
     *     link:string|null,
     *     status:string,
     *     icon_type:string,
     *     icon:string
     * }
     */
    private function resolveExternal(string $target): array
    {
        $fallbackIcon = [
            'type'  => 'class',
            'value' => 'globe',
        ];

        if ($target === '') {
            return [
                'link'      => null,
                'status'    => 'missing',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        if (
            !filter_var(
                $target,
                FILTER_VALIDATE_URL
            )
        ) {
            return [
                'link'      => null,
                'status'    => 'unavailable',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        return [
            'link'      => $target,
            'status'    => 'available',
            'icon_type' => $fallbackIcon['type'],
            'icon'      => $fallbackIcon['value'],
        ];
    }

    /**
     * Resolve een Joomla-component of administrator-menu-item.
     *
     * Target mag een component-option zijn (com_contact) of een
     * Joomla-menu-sleutel (com_contact_categories).
     *
     * @return array{
     *     link:string|null,
     *     status:string,
     *     icon_type:string,
     *     icon:string
     * }
     */
    private function resolveComponent(string $target): array
    {
        $fallbackIcon = $this->fallbackIcon();

        if ($target === '') {
            return [
                'link'      => null,
                'status'    => 'missing',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        $menuItem = $this->findAdminMenuItem($target);
        $option   = $this->resolveOption($target, $menuItem);
        $icon     = $this->resolveIcon($menuItem, $option);

        if ($option === '' || !ComponentHelper::isEnabled($option)) {
            $status = ($option !== '' && $this->componentExists($option))
                ? 'disabled'
                : 'missing';

            return [
                'link'      => null,
                'status'    => $status,
                'icon_type' => $icon['type'],
                'icon'      => $icon['value'],
            ];
        }

        $link = '';

        if (
            $menuItem !== null
            && trim((string) $menuItem['link']) !== ''
        ) {
            $link = trim((string) $menuItem['link']);
        } else {
            $link = 'index.php?option=' . $option;
        }

        return [
            'link'      => $link,
            'status'    => 'available',
            'icon_type' => $icon['type'],
            'icon'      => $icon['value'],
        ];
    }

    /**
     * Resolve een Joomla-plugin via het extension-id.
     *
     * @return array{
     *     link:string|null,
     *     status:string,
     *     icon_type:string,
     *     icon:string
     * }
     */
    private function resolvePlugin(string $target): array
    {
        $fallbackIcon = [
            'type'  => 'class',
            'value' => 'puzzle-piece',
        ];

        if (!ctype_digit($target) || (int) $target < 1) {
            return [
                'link'      => null,
                'status'    => 'missing',
                'icon_type' => $fallbackIcon['type'],
                'icon'       => $fallbackIcon['value'],
            ];
        }

        $targetId = (int) $target;

        $db = Factory::getContainer()->get(DatabaseDriver::class);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('extension_id'),
                $db->quoteName('enabled'),
            ])
            ->from($db->quoteName('#__extensions'))
            ->where(
                $db->quoteName('extension_id') . ' = :id'
            )
            ->where(
                $db->quoteName('type') . ' = ' . $db->quote('plugin')
            )
            ->bind(
                ':id',
                $targetId,
                ParameterType::INTEGER
            );

        $db->setQuery($query);

        $plugin = $db->loadObject();

        if (!$plugin) {
            return [
                'link'      => null,
                'status'    => 'missing',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        return [
            'link'      => 'index.php?option=com_plugins&task=plugin.edit&extension_id='
                . (int) $plugin->extension_id,
            'status'    => (int) $plugin->enabled === 1
                ? 'available'
                : 'disabled',
            'icon_type' => $fallbackIcon['type'],
            'icon'      => $fallbackIcon['value'],
        ];
    }

    /**
     * Resolve een Joomla-module-instance via #__modules.id.
     *
     * Alleen gepubliceerde module-instanties zijn geldige SimpleHub-targets.
     * De extensie-status bepaalt of de module actief beschikbaar is.
     *
     * @return array{
     *     link:string|null,
     *     status:string,
     *     icon_type:string,
     *     icon:string
     * }
     */
    private function resolveModule(string $target): array
    {
        $fallbackIcon = [
            'type'  => 'class',
            'value' => 'cube',
        ];

        if (!ctype_digit($target) || (int) $target < 1) {
            return [
                'link'      => null,
                'status'    => 'missing',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        $targetId = (int) $target;

        $db = Factory::getContainer()->get(DatabaseDriver::class);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.client_id'),
                $db->quoteName('a.published'),
                $db->quoteName('e.enabled'),
            ])
            ->from($db->quoteName('#__modules', 'a'))
            ->join(
                'LEFT',
                $db->quoteName('#__extensions', 'e')
                . ' ON '
                . $db->quoteName('e.element')
                . ' = '
                . $db->quoteName('a.module')
                . ' AND '
                . $db->quoteName('e.type')
                . ' = '
                . $db->quote('module')
                . ' AND '
                . $db->quoteName('e.client_id')
                . ' = '
                . $db->quoteName('a.client_id')
            )
            ->where(
                $db->quoteName('a.id') . ' = :id'
            )
            ->bind(
                ':id',
                $targetId,
                ParameterType::INTEGER
            );

        $db->setQuery($query);

        $module = $db->loadObject();

        if (!$module) {
            return [
                'link'      => null,
                'status'    => 'missing',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        if ((int) $module->published !== 1) {
            return [
                'link'      => null,
                'status'    => 'unavailable',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        return [
            'link'      => 'index.php?option=com_modules&task=module.edit&client_id='
                . (int) $module->client_id
                . '&id='
                . (int) $module->id,
            'status'    => (int) $module->enabled === 1
                ? 'available'
                : 'disabled',
            'icon_type' => $fallbackIcon['type'],
            'icon'      => $fallbackIcon['value'],
        ];
    }

    /**
     * Resolve een Joomla-artikel via #__content.id.
     *
     * @return array{
     *     link:string|null,
     *     status:string,
     *     icon_type:string,
     *     icon:string
     * }
     */
    private function resolveArticle(string $target): array
    {
        $fallbackIcon = [
            'type'  => 'class',
            'value' => 'file-text',
        ];

        if (!ctype_digit($target) || (int) $target < 1) {
            return [
                'link'      => null,
                'status'    => 'missing',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        $targetId = (int) $target;

        $db = Factory::getContainer()->get(DatabaseDriver::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__content'))
            ->where(
                $db->quoteName('id') . ' = :id'
            )
            ->bind(
                ':id',
                $targetId,
                ParameterType::INTEGER
            );

        $db->setQuery($query);

        if (!$db->loadResult()) {
            return [
                'link'      => null,
                'status'    => 'missing',
                'icon_type' => $fallbackIcon['type'],
                'icon'      => $fallbackIcon['value'],
            ];
        }

        return [
            'link'      => 'index.php?option=com_content&task=article.edit&id='
                . $targetId,
            'status'    => 'available',
            'icon_type' => $fallbackIcon['type'],
            'icon'      => $fallbackIcon['value'],
        ];
    }

    /**
     * Bepaalt het icoon via een vaste fallback-keten:
     *
     * 1. het gevonden administrator-menu-item
     * 2. de ouders van dat menu-item (parent_id)
     * 3. het menu-item van de bijbehorende extensie (com_contact)
     * 4. het icoon van Joomla's componentengroep
     * 5. class:component
     *
     * De icon-kolom van #__simplehub_items wordt hier niet gebruikt.
     *
     * @param   array<string, mixed>|null  $menuItem
     *
     * @return array{type:string,value:string}
     */
    private function resolveIcon(
        ?array $menuItem,
        string $option
    ): array {
        $icon = $this->iconFromMenuChain($menuItem);

        if ($icon !== null) {
            return $icon;
        }

        if ($option !== '') {
            $extensionItem = $this->findExtensionMenuItem($option);
            $icon          = $this->iconFromMenuChain($extensionItem);

            if ($icon !== null) {
                return $icon;
            }
        }

        return $this->fallbackIcon();
    }

    /**
     * Loopt het menu-item en zijn ouders af tot er een bruikbaar icoon is.
     *
     * @param   array<string, mixed>|null  $item
     *
     * @return array{type:string,value:string}|null
     */
    private function iconFromMenuChain(?array $item): ?array
    {
        $this->loadAdminMenu();

        $seen = [];

        while ($item !== null) {
            $id = (int) ($item['id'] ?? 0);

            if ($id < 1 || isset($seen[$id])) {
                break;
            }

            $seen[$id] = true;

            $icon = $this->parseIconValue(
                $this->iconSourceFromItem($item)
            );

            if ($icon !== null) {
                return $icon;
            }

            $parentId = (int) ($item['parent_id'] ?? 0);

            if ($parentId < 1) {
                break;
            }

            $item = $this->adminMenuById[$parentId] ?? null;
        }

        return null;
    }

    /**
     * Zoekt het administrator-menu-item dat bij de target hoort.
     *
     * Volgorde:
     * - titel (com_contact_categories)
     * - alias (com-contact-categories)
     * - exacte of option-link
     *
     * @return array<string, mixed>|null
     */
    private function findAdminMenuItem(string $target): ?array
    {
        $this->loadAdminMenu();

        $targetLower = strtolower($target);
        $alias       = str_replace('_', '-', $targetLower);

        $optionLink = str_starts_with($targetLower, 'com_')
            ? 'index.php?option=' . $targetLower
            : '';

        foreach ($this->adminMenuById as $item) {
            if (
                strtolower((string) $item['title'])
                === $targetLower
            ) {
                return $item;
            }
        }

        foreach ($this->adminMenuById as $item) {
            if (
                strtolower((string) $item['alias'])
                === $alias
            ) {
                return $item;
            }
        }

        if ($optionLink !== '') {
            foreach ($this->adminMenuById as $item) {
                $link = strtolower(
                    trim((string) $item['link'])
                );

                if (
                    $link === $optionLink
                    || str_starts_with(
                        $link,
                        $optionLink . '&'
                    )
                ) {
                    return $item;
                }
            }
        }

        if (str_starts_with($targetLower, 'index.php')) {
            foreach ($this->adminMenuById as $item) {
                if (
                    strtolower(
                        trim((string) $item['link'])
                    ) === $targetLower
                ) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Zoekt het hoofdmenu-item van een extensie, bijv. com_contact.
     *
     * @return array<string, mixed>|null
     */
    private function findExtensionMenuItem(string $option): ?array
    {
        $this->loadAdminMenu();

        $option      = strtolower($option);
        $optionLink  = 'index.php?option=' . $option;
        $byTitle     = null;
        $byExactLink = null;

        foreach ($this->adminMenuById as $item) {
            if (
                strtolower((string) $item['title'])
                === $option
            ) {
                $byTitle = $item;
                break;
            }
        }

        foreach ($this->adminMenuById as $item) {
            if (
                strtolower(trim((string) $item['link']))
                === $optionLink
            ) {
                $byExactLink = $item;
                break;
            }
        }

        return $byTitle ?? $byExactLink;
    }

    /**
     * Bepaalt de echte component-option.
     *
     * com_contact_categories → com_contact
     * en via extension=com_contact de bijbehorende extensie voor iconen.
     *
     * @param   array<string, mixed>|null  $menuItem
     */
    private function resolveOption(
        string $target,
        ?array $menuItem
    ): string {
        if ($menuItem !== null) {
            $link = trim((string) $menuItem['link']);

            if ($link !== '') {
                $uri    = new Uri($link);
                $option = strtolower(
                    (string) $uri->getVar('option', '')
                );

                if ($option !== '') {
                    return $option;
                }
            }
        }

        return $this->optionFromTarget($target);
    }

    /**
     * Extraheert de langst matching geïnstalleerde component uit de target.
     *
     * com_contact_categories → com_contact
     * com_contact            → com_contact
     */
    private function optionFromTarget(string $target): string
    {
        $target = strtolower(trim($target));

        if (str_starts_with($target, 'index.php')) {
            $uri = new Uri($target);

            return strtolower(
                (string) $uri->getVar('option', '')
            );
        }

        if (!str_starts_with($target, 'com_')) {
            return '';
        }

        $best = '';

        foreach (
            array_keys(ComponentHelper::getComponents())
            as $element
        ) {
            $element = strtolower((string) $element);

            if (
                $target === $element
                || str_starts_with(
                    $target,
                    $element . '_'
                )
            ) {
                if (strlen($element) > strlen($best)) {
                    $best = $element;
                }
            }
        }

        return $best;
    }

    /**
     * Icoon van Joomla's componentengroep, anders class:component.
     *
     * @return array{type:string,value:string}
     */
    private function fallbackIcon(): array
    {
        $this->loadAdminMenu();

        foreach ($this->adminMenuById as $item) {
            $type  = strtolower((string) $item['type']);
            $title = strtoupper((string) $item['title']);

            if (
                $type !== 'container'
                && $title !== 'MOD_MENU_COM_COMPONENTS'
                && $title !== 'MOD_MENU_COMPONENTS'
            ) {
                continue;
            }

            if (
                $type === 'container'
                || $title === 'MOD_MENU_COM_COMPONENTS'
                || $title === 'MOD_MENU_COMPONENTS'
            ) {
                $icon = $this->parseIconValue(
                    $this->iconSourceFromItem($item)
                );

                if ($icon !== null) {
                    return $icon;
                }
            }
        }

        return [
            'type'  => 'class',
            'value' => 'component',
        ];
    }

    /**
     * @param   array<string, mixed>  $item
     */
    private function iconSourceFromItem(array $item): string
    {
        $img = trim((string) ($item['img'] ?? ''));

        if ($img !== '') {
            return $img;
        }

        $params = $item['params'] ?? '';

        if (!$params instanceof Registry) {
            $params = new Registry($params);
        }

        $iconClass = trim(
            (string) $params->get('menu_icon_class', '')
        );

        if ($iconClass !== '') {
            return str_starts_with($iconClass, 'class:')
                ? $iconClass
                : 'class:' . $iconClass;
        }

        return '';
    }

    /**
     * @return array{type:string,value:string}|null
     */
    private function parseIconValue(string $value): ?array
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'class:')) {
            $class = trim(substr($value, 6));

            if (str_starts_with($class, 'icon-')) {
                $class = substr($class, 5);
            }

            if ($this->isSafeCssClass($class)) {
                return [
                    'type'  => 'class',
                    'value' => $class,
                ];
            }

            return null;
        }

        if ($this->isSafeImagePath($value)) {
            return [
                'type'  => 'image',
                'value' => $value,
            ];
        }

        return null;
    }

    private function componentExists(string $option): bool
    {
        $components = ComponentHelper::getComponents();

        return isset($components[$option]);
    }

    private function loadAdminMenu(): void
    {
        if ($this->adminMenuLoaded) {
            return;
        }

        $this->adminMenuLoaded = true;

        $db = Factory::getContainer()->get(DatabaseDriver::class);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('parent_id'),
                $db->quoteName('title'),
                $db->quoteName('alias'),
                $db->quoteName('link'),
                $db->quoteName('img'),
                $db->quoteName('params'),
                $db->quoteName('type'),
            ])
            ->from($db->quoteName('#__menu'))
            ->where(
                $db->quoteName('client_id') . ' = 1'
            )
            ->where(
                $db->quoteName('published') . ' = 1'
            )
            ->order($db->quoteName('lft'));

        $db->setQuery($query);

        $rows = $db->loadAssocList('id') ?: [];

        foreach ($rows as $id => $row) {
            $this->adminMenuById[(int) $id] = $row;
        }
    }

    /**
     * Controleert of een waarde een eenvoudige CSS-classnaam is.
     */
    private function isSafeCssClass(string $value): bool
    {
        return $value !== ''
            && preg_match(
                '/^[a-zA-Z0-9_-]+$/',
                $value
            ) === 1;
    }

    /**
     * Controleert of een waarde eruitziet als een lokaal afbeeldingspad.
     *
     * We accepteren hier uitsluitend relatieve paden naar afbeeldingen.
     * Geen http(s), data:, javascript:, querystrings of andere URL's.
     */
    private function isSafeImagePath(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (
            preg_match(
                '#^(https?:|//|data:|javascript:)#i',
                $value
            )
        ) {
            return false;
        }

        if (
            str_contains($value, '?')
            || str_contains($value, '#')
        ) {
            return false;
        }

        return preg_match(
            '#^[a-zA-Z0-9_./-]+\.(png|gif|jpe?g|svg|webp)$#i',
            $value
        ) === 1;
    }
}
