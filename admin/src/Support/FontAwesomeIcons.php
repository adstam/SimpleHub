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

/**
 * Curated lijst van FontAwesome-iconnamen voor de iconpicker (Sprint 28).
 *
 * Uitsluitend Solid, Brands en de door Joomla meegeleverde Regular-subset
 * (ADR-7) — geen Pro-stijlen. Deze lijst is met de hand samengesteld op
 * basis van de bekende FA6 Free-set en dient bij de implementatiesprint
 * nog kort geverifieerd te worden tegen de daadwerkelijk geladen
 * `joomla-fontawesome`-webasset in de Joomla 6-testomgeving (dezelfde
 * kanttekening als bij ADR-7 zelf) — met name de Regular-subset is
 * bewust klein gehouden, omdat FA Free daar maar een beperkt aantal
 * iconen van meelevert.
 */
final class FontAwesomeIcons
{
    /** @var array<int,string> */
    private const SOLID = [
        'address-book', 'address-card', 'anchor', 'archive', 'arrow-down',
        'arrow-left', 'arrow-right', 'arrow-up', 'bars', 'bell', 'bolt',
        'book', 'bookmark', 'box', 'box-archive', 'briefcase', 'bug',
        'building', 'bullhorn', 'calendar', 'calendar-check', 'camera',
        'chart-bar', 'chart-line', 'chart-pie', 'check', 'chevron-down',
        'chevron-left', 'chevron-right', 'chevron-up', 'circle-info',
        'circle-question', 'clipboard', 'clock', 'cloud', 'code', 'cog',
        'cogs', 'comment', 'comments', 'compass', 'copy', 'credit-card',
        'cube', 'cubes', 'database', 'desktop', 'download', 'envelope',
        'eye', 'eye-slash', 'file', 'file-lines', 'file-pdf', 'filter',
        'flag', 'floppy-disk', 'folder', 'folder-open', 'gauge', 'gear',
        'gears', 'gift', 'globe', 'graduation-cap', 'hammer', 'hand',
        'handshake', 'hard-drive', 'headset', 'heart', 'home', 'hourglass',
        'house', 'id-card', 'image', 'images', 'inbox', 'key', 'keyboard',
        'language', 'layer-group', 'life-ring', 'lightbulb', 'link',
        'list', 'list-check', 'location-dot', 'lock', 'magnifying-glass',
        'map', 'map-pin', 'newspaper', 'note-sticky', 'paper-plane',
        'paperclip', 'pen', 'pen-to-square', 'people-group', 'phone',
        'plug', 'plus', 'print', 'puzzle-piece', 'question', 'rectangle-list',
        'rocket', 'rss', 'screwdriver-wrench', 'server', 'share-nodes',
        'shield', 'shield-halved', 'shop', 'sitemap', 'sliders', 'sort',
        'square-check', 'star', 'store', 'tag', 'tags', 'terminal',
        'thumbs-down', 'thumbs-up', 'toggle-on', 'trash', 'triangle-exclamation',
        'trophy', 'truck', 'unlock', 'upload', 'user', 'user-gear',
        'user-group', 'users', 'wallet', 'wand-magic-sparkles', 'warehouse',
        'wrench', 'x', 'xmark',
    ];

    /** @var array<int,string> */
    private const REGULAR = [
        'address-book', 'address-card', 'bell', 'bell-slash', 'bookmark',
        'building', 'calendar', 'calendar-check', 'circle', 'clipboard',
        'clock', 'clone', 'comment', 'comments', 'compass', 'copy',
        'copyright', 'credit-card', 'envelope', 'envelope-open', 'eye',
        'eye-slash', 'file', 'file-lines', 'flag', 'floppy-disk', 'folder',
        'folder-open', 'hard-drive', 'heart', 'hourglass', 'id-badge',
        'id-card', 'image', 'images', 'keyboard', 'lightbulb', 'map',
        'message', 'newspaper', 'note-sticky', 'paper-plane', 'paste',
        'pen-to-square', 'rectangle-list', 'square', 'star', 'sun', 'moon',
        'thumbs-down', 'thumbs-up', 'trash-can', 'user', 'window-maximize',
        'window-minimize', 'window-restore',
    ];

    /** @var array<int,string> */
    private const BRANDS = [
        'android', 'apple', 'chrome', 'docker', 'facebook', 'figma',
        'firefox', 'git', 'github', 'gitlab', 'google', 'html5', 'instagram',
        'joomla', 'js', 'linkedin', 'linux', 'markdown', 'microsoft',
        'php', 'pinterest', 'python', 'reddit', 'slack', 'stack-overflow',
        'telegram', 'tiktok', 'vimeo', 'vuejs', 'whatsapp', 'wordpress',
        'x-twitter', 'youtube',
    ];

    /**
     * @return array<string,array<int,string>>  ['solid' => [...], 'regular' => [...], 'brands' => [...]]
     */
    public function all(): array
    {
        return [
            'solid'   => self::SOLID,
            'regular' => self::REGULAR,
            'brands'  => self::BRANDS,
        ];
    }
}