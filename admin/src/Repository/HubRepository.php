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

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use StamPlusJ\Component\Simplehub\Administrator\Support\LinkResolver;
use UnexpectedValueException;

final class HubRepository
{
    /**
     * @var LinkResolver
     */
    private LinkResolver $resolver;

    /**
     * @var DatabaseDriver
     */
    private DatabaseDriver $db;

    public function __construct(
        ?LinkResolver $resolver = null,
        ?DatabaseDriver $db = null
    ) {
        $this->resolver = $resolver ?? new LinkResolver();
        $this->db       = $db ?? Factory::getContainer()->get(DatabaseDriver::class);
    }

    /**
     * Geeft alle groepen van de Hub terug in het formaat dat de View verwacht.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     */
    public function getGroups(): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                'id',
                'title',
                'ordering',
            ])
            ->from($this->db->quoteName('#__simplehub_groups'))
            ->where($this->db->quoteName('published') . ' = 1')
            ->order($this->db->quoteName('ordering'));

        $this->db->setQuery($query);

        /** @var array<int, array<string,mixed>> $groups */
        $groups = $this->db->loadAssocList();

        if (!is_array($groups)) {
            throw new UnexpectedValueException(
                'Hub groups must return an array.'
            );
        }

        $itemCounts = $this->loadItemCounts();

        $result = [];

        foreach ($groups as $groupIndex => $group) {
            $group['items'] = $this->loadItems((int) $group['id']);

            /*
             * Het WERKELIJKE aantal items in de groep, ongeacht published-
             * status. 'items' hierboven bevat alleen gepubliceerde items
             * (voor de Dashboardweergave); voor de verwijderflow moet de
             * beheerder echter gewaarschuwd worden voor ALLE items die de
             * FK-cascade zou meeverwijderen (zie NEXT_INCREMENT.md,
             * Sprint 19).
             */
            $group['item_count'] = $itemCounts[(int) $group['id']] ?? 0;

            $this->validateGroup($group, $groupIndex);

            $items = [];

            foreach ($group['items'] as $itemIndex => $item) {
                $this->validateItem(
                    $item,
                    $groupIndex,
                    $itemIndex
                );

                /*
                 * Bewaar het type voor de View.
                 *
                 * LinkResolver heeft type en target nodig om de
                 * uiteindelijke link en status te bepalen.
                 */
                $type = (string) $item['type'];

                $resolved = $this->resolver->resolve(
                    $item['type'],
                    $item['target']
                );

                $item['link']   = $resolved['link'];
                $item['status'] = $resolved['status'];

                /*
                 * Sprint 28: een gekozen icoon (icon-kolom) krijgt
                 * voorrang boven het LinkResolver-resultaat, zowel hier
                 * (Dashboard) als in ItemModel::getItem() (Item-formulier).
                 */
                $override = trim((string) ($item['icon'] ?? ''));

                if ($override !== '') {
                    $item['icon_type'] = 'override';
                    $item['icon']      = $override;
                } else {
                    $item['icon_type'] = $resolved['icon_type'];
                    $item['icon']      = $resolved['icon'];
                }

                /*
                 * Alleen externe items gebruiken external_target.
                 *
                 * Voor alle andere itemtypes zetten we expliciet
                 * _self. Daarmee blijft hun bestaande werking
                 * volledig ongewijzigd.
                 */
                if ($type === 'external') {
                    $item['external_target'] = trim(
                        (string) ($item['external_target'] ?? '_blank')
                    );

                    if ($item['external_target'] === '') {
                        $item['external_target'] = '_blank';
                    }
                } else {
                    $item['external_target'] = '_self';
                }

                /*
                 * target is alleen intern nodig geweest voor
                 * LinkResolver en wordt niet aan de View doorgegeven.
                 */
                unset(
                    $item['target']
                );

                $items[] = $item;
            }

            $group['items'] = $items;

            $result[] = $group;
        }

        return $result;
    }

    /**
     * Laadt alle items van één groep.
     *
     * @param   int  $groupId
     *
     * @return array<int, array<string,mixed>>
     */
    private function loadItems(int $groupId): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                'id',
                'title',
                'description',
                'type',
                'target',
                'icon',
                'external_target',
                'ordering',
            ])
            ->from($this->db->quoteName('#__simplehub_items'))
            ->where(
                $this->db->quoteName('group_id')
                . ' = :groupId'
            )
            ->where(
                $this->db->quoteName('published')
                . ' = 1'
            )
            ->order($this->db->quoteName('ordering'))
            ->bind(':groupId', $groupId);

        $this->db->setQuery($query);

        return $this->db->loadAssocList() ?: [];
    }

    /**
     * Telt, per groep, het werkelijke aantal items (ongeacht published-status).
     *
     * @return array<int,int>  Item-aantal per group_id.
     */
    private function loadItemCounts(): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('group_id'),
                'COUNT(*) AS ' . $this->db->quoteName('total'),
            ])
            ->from($this->db->quoteName('#__simplehub_items'))
            ->group($this->db->quoteName('group_id'));

        $this->db->setQuery($query);

        $rows = $this->db->loadAssocList() ?: [];

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['group_id']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Valideert een groep.
     *
     * @param   array<string,mixed>  $group
     * @param   int                  $index
     *
     * @return void
     */
    private function validateGroup(
        array $group,
        int $index
    ): void {
        foreach (
            [
                'id',
                'title',
                'ordering',
                'items',
                'item_count',
            ] as $key
        ) {
            if (!array_key_exists($key, $group)) {
                throw new UnexpectedValueException(
                    sprintf(
                        'Hub group %d is missing key "%s".',
                        $index,
                        $key
                    )
                );
            }
        }

        if (!is_array($group['items'])) {
            throw new UnexpectedValueException(
                sprintf(
                    'Hub group %d "items" must be an array.',
                    $index
                )
            );
        }
    }

    /**
     * Valideert een item.
     *
     * @param   array<string,mixed>  $item
     * @param   int                  $groupIndex
     * @param   int                  $itemIndex
     *
     * @return void
     */
    private function validateItem(
        array $item,
        int $groupIndex,
        int $itemIndex
    ): void {
        foreach (
            [
                'id',
                'title',
                'description',
                'type',
                'target',
                'external_target',
                'ordering',
            ] as $key
        ) {
            if (!array_key_exists($key, $item)) {
                throw new UnexpectedValueException(
                    sprintf(
                        'Hub item %d in group %d is missing key "%s".',
                        $itemIndex,
                        $groupIndex,
                        $key
                    )
                );
            }
        }
    }

    /**
     * Slaat de volgorde van groepen op.
     *
     * @param   array<int,int>  $order
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function saveGroupOrder(array $order): void
    {
        if ($order === []) {
            return;
        }

        $order = array_map('intval', $order);

        if (count($order) !== count(array_unique($order))) {
            throw new \RuntimeException(
                'Duplicate group IDs are not allowed.'
            );
        }

        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__simplehub_groups'))
            ->where(
                $this->db->quoteName('id')
                . ' IN ('
                . implode(',', $order)
                . ')'
            );

        $this->db->setQuery($query);

        $existingIds = array_map(
            'intval',
            $this->db->loadColumn()
        );

        sort($existingIds);

        $expectedIds = $order;
        sort($expectedIds);

        if ($existingIds !== $expectedIds) {
            throw new \RuntimeException(
                'The submitted group order contains invalid group IDs.'
            );
        }

        $this->db->transactionStart();

        try {
            foreach ($order as $index => $groupId) {
                $query = $this->db->getQuery(true)
                    ->update($this->db->quoteName('#__simplehub_groups'))
                    ->set(
                        $this->db->quoteName('ordering')
                        . ' = '
                        . (int) (($index + 1) * -1)
                    )
                    ->where(
                        $this->db->quoteName('id')
                        . ' = '
                        . (int) $groupId
                    );

                $this->db->setQuery($query);
                $this->db->execute();
            }

            foreach ($order as $index => $groupId) {
                $query = $this->db->getQuery(true)
                    ->update($this->db->quoteName('#__simplehub_groups'))
                    ->set(
                        $this->db->quoteName('ordering')
                        . ' = '
                        . (int) ($index + 1)
                    )
                    ->where(
                        $this->db->quoteName('id')
                        . ' = '
                        . (int) $groupId
                    );

                $this->db->setQuery($query);
                $this->db->execute();
            }

            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();

            throw $e;
        }

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('ordering'),
            ])
            ->from($this->db->quoteName('#__simplehub_groups'))
            ->where(
                $this->db->quoteName('id')
                . ' IN ('
                . implode(',', $order)
                . ')'
            )
            ->order($this->db->quoteName('ordering'));

        $this->db->setQuery($query);

        $rows = $this->db->loadAssocList();

        $savedOrder = array_map(
            'intval',
            array_column($rows, 'id')
        );

        if ($savedOrder !== $order) {
            throw new \RuntimeException(
                'The group order could not be verified after saving.'
            );
        }
    }

    /**
     * Slaat de volgorde van Items binnen één Hubgroep op.
     *
     * @param   int            $groupId
     * @param   array<int,int> $order
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function saveItemOrder(
        int $groupId,
        array $order
    ): void {
        if ($groupId < 1) {
            throw new \RuntimeException('Invalid group ID.');
        }

        if ($order === []) {
            return;
        }

        $order = array_map('intval', $order);

        if (count($order) !== count(array_unique($order))) {
            throw new \RuntimeException(
                'Duplicate item IDs are not allowed.'
            );
        }

        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__simplehub_items'))
            ->where(
                $this->db->quoteName('group_id')
                . ' = '
                . (int) $groupId
            )
            ->where(
                $this->db->quoteName('id')
                . ' IN ('
                . implode(',', $order)
                . ')'
            );

        $this->db->setQuery($query);

        $existingIds = array_map(
            'intval',
            $this->db->loadColumn()
        );

        sort($existingIds);

        $expectedIds = $order;
        sort($expectedIds);

        if ($existingIds !== $expectedIds) {
            throw new \RuntimeException(
                'The submitted item order contains invalid item IDs.'
            );
        }

        $this->db->transactionStart();

        try {
            foreach ($order as $index => $itemId) {
                $query = $this->db->getQuery(true)
                    ->update($this->db->quoteName('#__simplehub_items'))
                    ->set(
                        $this->db->quoteName('ordering')
                        . ' = '
                        . (int) (($index + 1) * -1)
                    )
                    ->where(
                        $this->db->quoteName('id')
                        . ' = '
                        . (int) $itemId
                    )
                    ->where(
                        $this->db->quoteName('group_id')
                        . ' = '
                        . (int) $groupId
                    );

                $this->db->setQuery($query);
                $this->db->execute();
            }

            foreach ($order as $index => $itemId) {
                $query = $this->db->getQuery(true)
                    ->update($this->db->quoteName('#__simplehub_items'))
                    ->set(
                        $this->db->quoteName('ordering')
                        . ' = '
                        . (int) ($index + 1)
                    )
                    ->where(
                        $this->db->quoteName('id')
                        . ' = '
                        . (int) $itemId
                    )
                    ->where(
                        $this->db->quoteName('group_id')
                        . ' = '
                        . (int) $groupId
                    );

                $this->db->setQuery($query);
                $this->db->execute();
            }

            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();

            throw $e;
        }

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('ordering'),
            ])
            ->from($this->db->quoteName('#__simplehub_items'))
            ->where(
                $this->db->quoteName('group_id')
                . ' = '
                . (int) $groupId
            )
            ->where(
                $this->db->quoteName('id')
                . ' IN ('
                . implode(',', $order)
                . ')'
            )
            ->order($this->db->quoteName('ordering'));

        $this->db->setQuery($query);

        $rows = $this->db->loadAssocList();

        $savedOrder = array_map(
            'intval',
            array_column($rows, 'id')
        );

        if ($savedOrder !== $order) {
            throw new \RuntimeException(
                'The item order could not be verified after saving.'
            );
        }
    }

    /**
     * Verplaatst alle items van één Hubgroep naar een andere Hubgroep.
     *
     * Wordt gebruikt door de "Keuze B"-afhandeling van de robuustere
     * groep-verwijderflow (Sprint 19): eerst de items verplaatsen, pas
     * daarna de (dan lege) bronproep verwijderen via het bestaande
     * GroupModel::delete()-pad. Zie ARCHITECTURE_APPENDIX.md, ADR-2.
     *
     * De verplaatste items worden achteraan de bestaande items van de
     * doelgroep geplaatst, analoog aan hoe GroupModel::save() nieuwe
     * groepen achteraan plaatst en ItemModel::save() nieuwe items
     * achteraan hun groep plaatst.
     *
     * @param   int  $sourceGroupId  De groep waarvan de items verplaatst worden.
     * @param   int  $targetGroupId  De groep waar de items naartoe verplaatst worden.
     *
     * @return  void
     *
     * @throws \RuntimeException
     */
    public function moveItemsToGroup(
        int $sourceGroupId,
        int $targetGroupId
    ): void {
        if ($sourceGroupId < 1 || $targetGroupId < 1) {
            throw new \RuntimeException('Invalid group ID.');
        }

        if ($sourceGroupId === $targetGroupId) {
            throw new \RuntimeException(
                'Source and target group must differ.'
            );
        }

        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__simplehub_groups'))
            ->where(
                $this->db->quoteName('id') . ' = :targetGroupId'
            )
            ->bind(':targetGroupId', $targetGroupId);

        $this->db->setQuery($query);

        if (!$this->db->loadResult()) {
            throw new \RuntimeException(
                'The target group does not exist.'
            );
        }

        $query = $this->db->getQuery(true)
            ->select('id')
            ->from($this->db->quoteName('#__simplehub_items'))
            ->where(
                $this->db->quoteName('group_id') . ' = :sourceGroupId'
            )
            ->order($this->db->quoteName('ordering'))
            ->bind(':sourceGroupId', $sourceGroupId);

        $this->db->setQuery($query);

        $itemIds = array_map('intval', $this->db->loadColumn() ?: []);

        if ($itemIds === []) {
            // Niets te verplaatsen: de groep is al leeg.
            return;
        }

        $query = $this->db->getQuery(true)
            ->select(
                'COALESCE(MAX(' . $this->db->quoteName('ordering') . '), 0)'
            )
            ->from($this->db->quoteName('#__simplehub_items'))
            ->where(
                $this->db->quoteName('group_id') . ' = :targetGroupId'
            )
            ->bind(':targetGroupId', $targetGroupId);

        $this->db->setQuery($query);

        $maxOrdering = (int) $this->db->loadResult();

        $this->db->transactionStart();

        try {
            foreach ($itemIds as $index => $itemId) {
                $query = $this->db->getQuery(true)
                    ->update($this->db->quoteName('#__simplehub_items'))
                    ->set(
                        $this->db->quoteName('group_id')
                        . ' = '
                        . (int) $targetGroupId
                    )
                    ->set(
                        $this->db->quoteName('ordering')
                        . ' = '
                        . (int) ($maxOrdering + $index + 1)
                    )
                    ->where(
                        $this->db->quoteName('id') . ' = ' . (int) $itemId
                    )
                    ->where(
                        $this->db->quoteName('group_id')
                        . ' = '
                        . (int) $sourceGroupId
                    );

                $this->db->setQuery($query);
                $this->db->execute();
            }

            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();

            throw $e;
        }

        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__simplehub_items'))
            ->where(
                $this->db->quoteName('group_id') . ' = :sourceGroupId'
            )
            ->bind(':sourceGroupId', $sourceGroupId);

        $this->db->setQuery($query);

        if ((int) $this->db->loadResult() !== 0) {
            throw new \RuntimeException(
                'The items could not be verified as moved after saving.'
            );
        }
    }
}