<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class GroupTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__simplehub_groups', 'id', $db);
    }
}