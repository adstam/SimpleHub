<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\Repository;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

final class TitlebarModuleRepository
{
    private const MODULE_NOTE     = 'com_simplehub';
    private const MODULE_POSITION = 'title';

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function isPublished(): ?bool
    {
        $id = $this->findModuleId();

        if ($id === null) {
            return null;
        }

        $published = $this->db->setQuery(
            $this->db->getQuery(true)
                ->select('published')
                ->from('#__modules')
                ->where($this->db->quoteName('id') . ' = ' . $id)
        )->loadResult();

        return $published !== null ? ((int) $published === 1) : null;
    }

    public function setPublished(bool $published): bool
    {
        $id = $this->findModuleId();

        if ($id === null) {
            return false;
        }

        /** @var \Joomla\CMS\Table\Module $table */
        $table = Table::getInstance('Module');
        $table->load($id);
        $table->published = $published ? 1 : 0;

        return (bool) $table->store();
    }

    private function findModuleId(): ?int
    {
        $id = $this->db->setQuery(
            $this->db->getQuery(true)
                ->select('id')
                ->from('#__modules')
                ->where($this->db->quoteName('note') . ' = ' . $this->db->quote(self::MODULE_NOTE))
                ->where($this->db->quoteName('position') . ' = ' . $this->db->quote(self::MODULE_POSITION))
        )->loadResult();

        return $id !== null ? (int) $id : null;
    }
}