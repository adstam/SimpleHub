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
use Joomla\CMS\Router\Route;

/** @var \StamPlusJ\Component\Simplehub\Administrator\View\Item\HtmlView $this */
?>

<form
    action="<?= Route::_('index.php?option=com_simplehub&layout=edit'); ?>"
    method="post"
    name="adminForm"
    id="adminForm">

    <?php
    /*
     * Sprint 28: per veld renderen i.p.v. renderFieldset(), zodat het
     * icon-veld (IconpickerField) zijn preview op zijn eigen plek in de
     * veldvolgorde toont, in plaats van los boven de fieldset (Sprint 26).
     */
    foreach ($this->form->getFieldset('basic') as $field) : ?>
        <?= $field->renderField(); ?>
    <?php endforeach; ?>

    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>

</form>