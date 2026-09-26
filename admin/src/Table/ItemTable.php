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

final class ItemTable extends Table
{
    /**
     * Constructor.
     *
     * @param   \JDatabaseDriver  &$db  Database connector.
     */
    public function __construct(&$db)
    {
        parent::__construct('#__simplehub_items', 'id', $db);
    }
}