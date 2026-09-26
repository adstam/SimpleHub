<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Helper\ContentHelper;

final class SimplehubComponent extends MVCComponent
{
    /**
     * Returns the available component capabilities.
     *
     * @return array<string, bool>
     */
    public function getCapabilities(): array
    {
        return [
            'categories' => false,
            'workflow'   => false,
        ];
    }
}