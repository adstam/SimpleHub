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

/** @var \StamPlusJ\Component\Simplehub\Administrator\View\Group\HtmlView $this */

?>

<form
    action="<?= Route::_('index.php?option=com_simplehub&layout=edit'); ?>"
    method="post"
    name="adminForm"
    id="adminForm">

    <?= $this->form->renderFieldset('basic'); ?>

    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>

</form>