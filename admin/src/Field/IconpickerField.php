<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use StamPlusJ\Component\Simplehub\Administrator\Support\FontAwesomeIcons;

/**
 * Klikbare FontAwesome-iconkeuze voor het icon-veld van een Hub-item.
 *
 * Rendert, op de plek van het veld in de veldvolgorde (Sprint 28):
 * - de preview (override indien gekozen, anders het automatisch bepaalde
 *   icoon — voorrang bepaald door ItemModel::getItem(), niet hier);
 * - een knop die een JoomlaDialog-popup opent met een doorzoekbare,
 *   statisch gerenderde iconengrid (Solid/Brands/Regular-subset, ADR-7);
 * - een verborgen invoerveld met de daadwerkelijk opgeslagen waarde
 *   (leeg = geen override, val terug op automatisch).
 *
 * De interactie (zoeken, kiezen, wissen) zit in media/js/item.js en
 * hergebruikt het bestaande com_simplehub.item-asset.
 */
final class IconpickerField extends FormField
{
    protected $type = 'Iconpicker';

    protected function getInput(): string
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->useScript('com_simplehub.item');

        $value = trim((string) $this->value);
        $data  = $this->form->getData();

        $previewType = (string) ($data->get('resolved_icon_type') ?: 'class');
        $previewIcon = (string) ($data->get('resolved_icon') ?: 'link');
        $isImage     = $previewType === 'image';

        $previewClass = $previewType === 'override'
            ? $previewIcon
            : 'icon-' . $previewIcon;

        $html = [];

        $html[] = '<div class="sh-iconpicker" id="sh-iconpicker">';

        // Preview: definitieve plek in de veldvolgorde (Sprint 28).
        $html[] = '<div class="sh-item-icon-preview d-flex align-items-center mb-2" id="sh-item-icon-preview">';
        $html[] = '<img id="sh-item-icon-image" src="' . ($isImage ? $this->esc($previewIcon) : '') . '"'
            . ' class="sh-icon me-2' . ($isImage ? '' : ' d-none') . '" aria-hidden="true" alt="">';
        $html[] = '<span id="sh-item-icon-class" class="' . $this->esc($previewClass) . ' sh-icon me-2'
            . ($isImage ? ' d-none' : '') . '" aria-hidden="true"></span>';
        $html[] = '<span class="text-muted small">' . Text::_('COM_SIMPLEHUB_ITEM_ICON_PREVIEW_LABEL') . '</span>';
        $html[] = '</div>';

        // Opgeslagen waarde (leeg = automatisch).
        $html[] = '<input type="hidden" name="' . $this->esc($this->name) . '" id="' . $this->esc($this->id)
            . '" value="' . $this->esc($value) . '" data-sh-iconpicker-value>';

        $dialogOptions = [
            'popupType'  => 'inline',
            'src'        => '#sh-iconpicker-modal',
            'textHeader' => Text::_('COM_SIMPLEHUB_ICONPICKER_TITLE'),
            'width'      => '720px',
            'height'     => 'fit-content',
        ];

        $html[] = '<div class="btn-group btn-group-sm">';
        $html[] = '<button type="button" class="btn btn-outline-secondary" data-joomla-dialog=\'' . htmlspecialchars(
            json_encode($dialogOptions, JSON_UNESCAPED_SLASHES),
            ENT_QUOTES,
            'UTF-8'
        ) . '\'>' . Text::_('COM_SIMPLEHUB_ICONPICKER_CHOOSE') . '</button>';
        $html[] = '<button type="button" class="btn btn-outline-secondary" data-sh-iconpicker-clear'
            . ($value === '' ? ' disabled' : '') . '>' . Text::_('COM_SIMPLEHUB_ICONPICKER_CLEAR') . '</button>';
        $html[] = '</div>';

        $html[] = '</div>';

        // JoomlaDialog-template (Core-conventie, zie ARCHITECTURE_APPENDIX.md ADR-1).
        $html[] = '<template id="sh-iconpicker-modal">';
        $html[] = '<div class="p-3" data-sh-iconpicker-dialog>';
        $html[] = '<input type="search" class="form-control mb-2" data-sh-iconpicker-search placeholder="'
            . Text::_('COM_SIMPLEHUB_ICONPICKER_SEARCH') . '">';
        $html[] = '<div class="sh-iconpicker-grid" data-sh-iconpicker-grid>';

        foreach ((new FontAwesomeIcons())->all() as $style => $names) {
            foreach ($names as $name) {
                $title = 'fa-' . $style . ' fa-' . $name;

                $html[] = '<button type="button" class="sh-iconpicker-icon btn btn-light" '
                    . 'data-sh-icon-style="' . $this->esc($style) . '" '
                    . 'data-sh-icon-name="' . $this->esc($name) . '" '
                    . 'title="' . $this->esc($title) . '">'
                    . '<span class="fa-' . $this->esc($style) . ' fa-' . $this->esc($name) . '" aria-hidden="true"></span>'
                    . '</button>';
            }
        }

        $html[] = '</div>';
        $html[] = '<p class="text-muted small mb-0 d-none" data-sh-iconpicker-empty>'
            . Text::_('COM_SIMPLEHUB_ICONPICKER_NO_RESULTS') . '</p>';
        $html[] = '</div>';
        $html[] = '</template>';

        return implode('', $html);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}