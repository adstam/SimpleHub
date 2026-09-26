<?php


/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


namespace StamPlusJ\Component\Simplehub\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use StamPlusJ\Component\Simplehub\Administrator\Repository\HubRepository;

final class GroupsController extends BaseController
{
    /**
     * Slaat de nieuwe groepsvolgorde via Ajax op.
     *
     * @return void
     */
    public function saveOrderAjax(): void
    {

        $this->checkToken();

        $app = Factory::getApplication();

        $order = $app->input->post->get(
            'order',
            [],
            'array'
        );

        $order = array_map('intval', $order);

        if ($order === []) {
            echo new JsonResponse(
                null,
                Text::_('COM_SIMPLEHUB_ERROR_NO_GROUP_ORDER'),
                true
            );

            $app->close();
        }

        try {
            $repository = new HubRepository();

            $repository->saveGroupOrder($order);

            echo new JsonResponse([
                'order' => $order,
            ]);
        } catch (\Throwable $e) {
            echo new JsonResponse(
                null,
                $e->getMessage(),
                true
            );
        }

        $app->close();
    }
}
