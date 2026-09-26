<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \StamPlusJ\Component\Simplehub\Administrator\View\Dashboard\HtmlView $this */
?>

<?php
/*
 * Icoon-rendering (Sprint 28): drie mogelijke types, bepaald door
 * HubRepository::getGroups().
 *
 * - image    : lokaal afbeeldingspad uit Joomla's menu (LinkResolver).
 * - class    : automatisch bepaalde, korte aliasnaam (LinkResolver),
 *              gerenderd via Joomla's eigen icon-<naam>-classmapping.
 * - override : door de beheerder gekozen, volledige FontAwesome-
 *              classstring (bv. "fa-solid fa-address-book"), rechtstreeks
 *              als class gebruikt - GEEN icon--prefix, want dat zou de
 *              classstring corrumperen.
 */
$renderItemIcon = function (string $iconType, string $icon): string {
    if ($iconType === 'image') {
        return '<img src="' . $this->escape($icon) . '" class="sh-icon me-2" aria-hidden="true" alt="">';
    }

    $class = $iconType === 'override' ? $icon : 'icon-' . $icon;

    return '<span class="' . $this->escape($class) . ' sh-icon me-2" aria-hidden="true"></span>';
};
?>

<form
    action="<?= Route::_('index.php?option=com_simplehub&view=dashboard'); ?>"
    method="post"
    name="adminForm"
    id="adminForm">

    <div class="card mb-4">

        <div class="card-header">
            <h2 class="mb-0">
                <?= Text::_('COM_SIMPLEHUB_WELCOME_TITLE'); ?>
            </h2>
        </div>

        <div class="card-body">
            <p><?= Text::_('COM_SIMPLEHUB_WELCOME_MESSAGE'); ?></p>
        </div>

    </div>

    <div
        id="sh-order-error"
        class="alert alert-danger d-none"
        role="alert">
    </div>

    <div id="sh-groups">

        <?php foreach ($this->groups as $group) :
            $groupItemCount = (int) ($group['item_count'] ?? 0);
        ?>

            <section
                class="card mb-4 sh-group"
                data-id="<?= (int) $group['id']; ?>">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <div class="d-flex align-items-center">

                        <span
                            class="icon-menu sh-drag-handle me-2"
                            aria-hidden="true"
                            title="<?= Text::_('JGRID_HEADING_ORDERING'); ?>"
                            style="cursor: move;"></span>

                        <h2 class="mb-0">
                            <?= $this->escape($group['title']); ?>
                        </h2>

                    </div>

                    <div
                        class="btn-group btn-group-sm"
                        role="group"
                        aria-label="<?= Text::_('COM_SIMPLEHUB_GROUP_ACTIONS'); ?>">

                        <a
                            class="btn btn-outline-secondary"
                            href="<?= Route::_(
                                'index.php?option=com_simplehub&task=group.edit&id=' . (int) $group['id']
                            ); ?>"
                            title="<?= Text::_('JACTION_EDIT'); ?>">

                            <span
                                class="icon-edit"
                                aria-hidden="true"></span>

                            <span class="visually-hidden">
                                <?= Text::_('JACTION_EDIT'); ?>
                            </span>

                        </a>

                        <?php if ($groupItemCount > 0) : ?>

                            <?php
                            /*
                             * Groep MET items: in plaats van de kale
                             * confirm() een JoomlaDialog-modal tonen met
                             * een expliciete keuze (verwijderen inclusief
                             * items, of eerst verplaatsen). Zie de hidden
                             * <template> hieronder en
                             * ARCHITECTURE_APPENDIX.md, ADR-1.
                             */
                            $deleteModalOptions = [
                                'popupType'  => 'inline',
                                'src'        => '#sh-delete-group-modal-' . (int) $group['id'],
                                'textHeader' => Text::_('COM_SIMPLEHUB_MODAL_DELETE_GROUP_TITLE'),
                                'width'      => '520px',
                                'height'     => 'fit-content',
                            ];
                            ?>

                            <button
                                type="button"
                                class="btn btn-outline-danger"
                                title="<?= Text::_('JACTION_DELETE'); ?>"
                                data-joomla-dialog='<?= htmlspecialchars(
                                    json_encode($deleteModalOptions, JSON_UNESCAPED_SLASHES),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>'>

                                <span
                                    class="icon-trash"
                                    aria-hidden="true"></span>

                                <span class="visually-hidden">
                                    <?= Text::_('JACTION_DELETE'); ?>
                                </span>

                            </button>

                        <?php else : ?>

                            <button
                                type="submit"
                                form="delete-group-<?= (int) $group['id']; ?>"
                                class="btn btn-outline-danger"
                                title="<?= Text::_('JACTION_DELETE'); ?>">

                                <span
                                    class="icon-trash"
                                    aria-hidden="true"></span>

                                <span class="visually-hidden">
                                    <?= Text::_('JACTION_DELETE'); ?>
                                </span>

                            </button>

                        <?php endif; ?>

                    </div>

                </div>

                <div
                    class="list-group list-group-flush sh-items"
                    data-group-id="<?= (int) $group['id']; ?>">

                    <?php foreach ($group['items'] as $item) : ?>

                        <div
                            class="list-group-item d-flex justify-content-between align-items-center sh-item"
                            data-id="<?= (int) $item['id']; ?>">

                            <div class="d-flex align-items-center flex-grow-1">

                                <span
                                    class="icon-menu sh-item-drag-handle me-2"
                                    aria-hidden="true"
                                    title="<?= Text::_('JGRID_HEADING_ORDERING'); ?>"
                                    style="cursor: move;"></span>

                                <?php
                                /*
                                 * Icoon-rendering:
                                 *
                                 * - class = Joomla icon class
                                 * - image = lokaal afbeeldingspad uit Joomla's menu
                                 * - override = door de beheerder gekozen FontAwesome-
                                 *   classstring (icon-kolom), overschrijft het
                                 *   automatisch bepaalde icoon (Sprint 28)
                                 *
                                 * Zie HubRepository::getGroups() voor de
                                 * voorrangsregel (override vóór automatisch).
                                 */
                                $iconType = $item['icon_type'] ?? 'class';
                                $icon     = $item['icon'] ?? 'component';

                                /*
                                 * Bepaal het gewenste target voor de link.
                                 *
                                 * Alleen externe items gebruiken de waarde
                                 * uit external_target. Voor alle andere typen
                                 * blijft de link in hetzelfde venster openen.
                                 *
                                 * De waarde 'popup' is geen geldige HTML
                                 * target-waarde (die zijn beperkt tot
                                 * _blank/_self/_parent/_top) en wordt daarom
                                 * client-side afgehandeld door dashboard.js.
                                 * target="_blank" blijft als progressive-
                                 * enhancement-fallback staan, zodat de link
                                 * ook zonder JavaScript (of bij een
                                 * geblokkeerde popup) altijd bruikbaar blijft.
                                 */
                                $linkTarget = '_self';
                                $isPopupTarget = false;

                                if (
                                    ($item['type'] ?? null) === 'external'
                                    && !empty($item['external_target'])
                                ) {
                                    $externalTarget = (string) $item['external_target'];

                                    if ($externalTarget === 'popup') {
                                        $isPopupTarget = true;
                                        $linkTarget = '_blank';
                                    } else {
                                        $linkTarget = $externalTarget;
                                    }
                                }
                                ?>

                                <?php if (!empty($item['link'])) : ?>

                                    <a
                                        class="d-flex align-items-center text-decoration-none flex-grow-1"
                                        href="<?= Route::_($item['link']); ?>"
                                        target="<?= $this->escape($linkTarget); ?>"
                                        <?php if ($linkTarget === '_blank') : ?>
                                            rel="noopener noreferrer"
                                        <?php endif; ?>
                                        <?php if ($isPopupTarget) : ?>
                                            data-sh-target="popup"
                                        <?php endif; ?>>

                                        <?= $renderItemIcon($iconType, $icon); ?>

                                        <span class="sh-title">
                                            <?= $this->escape($item['title']); ?>
                                        </span>

                                    </a>

                                <?php elseif (($item['status'] ?? null) === 'disabled') : ?>

                                    <span
                                        class="d-flex align-items-center flex-grow-1 text-muted"
                                        title="<?= Text::_('COM_SIMPLEHUB_ITEM_COMPONENT_DISABLED'); ?>">

                                        <?= $renderItemIcon($iconType, $icon); ?>

                                        <span class="sh-title">
                                            <?= $this->escape($item['title']); ?>
                                        </span>

                                        <span
                                            class="badge bg-warning text-dark ms-2">
                                            <?= Text::_('COM_SIMPLEHUB_ITEM_COMPONENT_DISABLED'); ?>
                                        </span>

                                    </span>

                                <?php elseif (($item['status'] ?? null) === 'missing') : ?>

                                    <span
                                        class="d-flex align-items-center flex-grow-1 text-muted"
                                        title="<?= Text::_('COM_SIMPLEHUB_ITEM_COMPONENT_MISSING'); ?>">

                                        <?= $renderItemIcon($iconType, $icon); ?>

                                        <span class="sh-title">
                                            <?= $this->escape($item['title']); ?>
                                        </span>

                                        <span
                                            class="badge bg-danger ms-2">
                                            <?= Text::_('COM_SIMPLEHUB_ITEM_COMPONENT_MISSING'); ?>
                                        </span>

                                    </span>

                                <?php else : ?>

                                    <span
                                        class="d-flex align-items-center flex-grow-1">

                                        <?= $renderItemIcon($iconType, $icon); ?>

                                        <span class="sh-title">
                                            <?= $this->escape($item['title']); ?>
                                        </span>

                                    </span>

                                <?php endif; ?>

                            </div>

                            <div
                                class="btn-group btn-group-sm ms-2"
                                role="group"
                                aria-label="<?= Text::_('COM_SIMPLEHUB_ITEM_ACTIONS'); ?>">

                                <a
                                    class="btn btn-outline-secondary"
                                    href="<?= Route::_(
                                        'index.php?option=com_simplehub&task=item.edit&id=' . (int) $item['id']
                                    ); ?>"
                                    title="<?= Text::_('JACTION_EDIT'); ?>">

                                    <span
                                        class="icon-edit"
                                        aria-hidden="true"></span>

                                    <span class="visually-hidden">
                                        <?= Text::_('JACTION_EDIT'); ?>
                                    </span>

                                </a>

                                <button
                                    type="submit"
                                    form="delete-item-<?= (int) $item['id']; ?>"
                                    class="btn btn-outline-danger"
                                    title="<?= Text::_('JACTION_DELETE'); ?>">

                                    <span
                                        class="icon-trash"
                                        aria-hidden="true"></span>

                                    <span class="visually-hidden">
                                        <?= Text::_('JACTION_DELETE'); ?>
                                    </span>

                                </button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="card-footer text-end">

                    <a
                        class="btn btn-sm btn-outline-primary"
                        href="<?= Route::_(
                            'index.php?option=com_simplehub&task=item.add&group_id=' . (int) $group['id']
                        ); ?>">

                        <span
                            class="icon-plus"
                            aria-hidden="true"></span>

                        <?= Text::_('COM_SIMPLEHUB_NEW_ITEM'); ?>

                    </a>

                </div>

            </section>

        <?php endforeach; ?>

    </div>

    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>

</form>

<?php foreach ($this->groups as $group) :
    $groupItemCount = (int) ($group['item_count'] ?? 0);
?>

    <form
        id="delete-group-<?= (int) $group['id']; ?>"
        action="<?= Route::_('index.php?option=com_simplehub'); ?>"
        method="post"
        <?php if ($groupItemCount < 1) : ?>
        onsubmit="return confirm('<?= Text::_('COM_SIMPLEHUB_CONFIRM_DELETE_GROUP'); ?>');"
        <?php endif; ?>>

        <input
            type="hidden"
            name="task"
            value="group.delete">

        <input
            type="hidden"
            name="id"
            value="<?= (int) $group['id']; ?>">

        <?= HTMLHelper::_('form.token'); ?>

    </form>

<?php endforeach; ?>

<?php
/*
 * Verwijdermodal voor Hubgroepen MET items (Sprint 19).
 *
 * Eén <template> per groep-met-items, gekoppeld via het
 * data-joomla-dialog-attribuut op de bijbehorende trashknop hierboven
 * (src: '#sh-delete-group-modal-<id>'). <template>-inhoud is inert en
 * wordt door JoomlaDialog pas bij het openen gekloond, waardoor het
 * form="..."-attribuut van de knoppen hieronder pas dan wordt
 * gekoppeld aan het bijbehorende, elders in dit bestand gedefinieerde
 * <form>-element (Keuze A: het bestaande delete-group-formulier
 * hierboven; Keuze B: het nieuwe move-then-delete-formulier
 * hieronder). Zie ARCHITECTURE_APPENDIX.md, ADR-1.
 */
?>

<?php foreach ($this->groups as $group) :
    $groupItemCount = (int) ($group['item_count'] ?? 0);

    if ($groupItemCount < 1) {
        continue;
    }

    $otherGroups = array_values(array_filter(
        $this->groups,
        static fn ($otherGroup) => (int) $otherGroup['id'] !== (int) $group['id']
    ));
?>

    <template id="sh-delete-group-modal-<?= (int) $group['id']; ?>">

        <div class="p-3">

            <p class="fw-bold text-danger">
                <?= Text::sprintf(
                    'COM_SIMPLEHUB_MODAL_DELETE_GROUP_WARNING',
                    $groupItemCount
                ); ?>
            </p>

            <div class="d-grid gap-1 mb-3">

                <button
                    type="submit"
                    form="delete-group-<?= (int) $group['id']; ?>"
                    class="btn btn-danger">
                    <?= Text::sprintf(
                        'COM_SIMPLEHUB_MODAL_DELETE_GROUP_CHOICE_A',
                        $groupItemCount
                    ); ?>
                </button>

                <small class="text-muted">
                    <?= Text::_('COM_SIMPLEHUB_MODAL_DELETE_GROUP_CHOICE_A_WARNING'); ?>
                </small>

            </div>

            <?php if ($otherGroups !== []) : ?>

                <hr>

                <form
                    id="move-then-delete-group-<?= (int) $group['id']; ?>"
                    action="<?= Route::_('index.php?option=com_simplehub'); ?>"
                    method="post">

                    <input
                        type="hidden"
                        name="task"
                        value="group.moveItemsAndDelete">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $group['id']; ?>">

                    <?= HTMLHelper::_('form.token'); ?>

                    <label
                        for="sh-target-group-<?= (int) $group['id']; ?>"
                        class="form-label">
                        <?= Text::_('COM_SIMPLEHUB_MODAL_DELETE_GROUP_TARGET_LABEL'); ?>
                    </label>

                    <select
                        id="sh-target-group-<?= (int) $group['id']; ?>"
                        name="target_group_id"
                        class="form-select mb-2"
                        required>

                        <?php foreach ($otherGroups as $otherGroup) : ?>
                            <option value="<?= (int) $otherGroup['id']; ?>">
                                <?= $this->escape($otherGroup['title']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                </form>

                <div class="d-grid">

                    <button
                        type="submit"
                        form="move-then-delete-group-<?= (int) $group['id']; ?>"
                        class="btn btn-outline-primary">
                        <?= Text::_('COM_SIMPLEHUB_MODAL_DELETE_GROUP_CHOICE_B'); ?>
                    </button>

                </div>

            <?php else : ?>

                <div class="alert alert-warning mb-0">
                    <?= Text::_('COM_SIMPLEHUB_MODAL_DELETE_GROUP_NO_TARGET'); ?>
                </div>

            <?php endif; ?>

        </div>

    </template>

<?php endforeach; ?>

<?php foreach ($this->groups as $group) : ?>

    <?php foreach ($group['items'] as $item) : ?>

        <form
            id="delete-item-<?= (int) $item['id']; ?>"
            action="<?= Route::_('index.php?option=com_simplehub'); ?>"
            method="post"
            onsubmit="return confirm('<?= Text::_('COM_SIMPLEHUB_CONFIRM_DELETE_ITEM'); ?>');">

            <input
                type="hidden"
                name="task"
                value="item.delete">

            <input
                type="hidden"
                name="id"
                value="<?= (int) $item['id']; ?>">

            <?= HTMLHelper::_('form.token'); ?>

        </form>

    <?php endforeach; ?>

<?php endforeach; ?>