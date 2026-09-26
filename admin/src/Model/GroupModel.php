<?php


/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


namespace StamPlusJ\Component\Simplehub\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

final class GroupModel extends AdminModel
{
    public function getTable($type = 'Group', $prefix = 'Table', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        return $this->loadForm(
            'com_simplehub.group',
            'group',
            [
                'control'   => 'jform',
                'load_data' => $loadData,
            ]
        );
    }

    /**
     * Slaat een Hubgroep op.
     *
     * Nieuwe groepen krijgen de eerstvolgende ordering-waarde, zodat ze
     * onderaan de bestaande groepsvolgorde worden geplaatst.
     *
     * Nieuwe groepen krijgen bij een bestaande titel automatisch een
     * numerieke suffix op basis van het hoogste bestaande suffix.
     *
     * @param   array<string,mixed>  $data  Gegevens van de groep.
     *
     * @return  bool  True bij succesvol opslaan.
     */
    public function save($data): bool
    {
        if (empty($data['id']))
        {
            $query = $this->getDatabase()->getQuery(true)
                ->select('MAX(' . $this->getDatabase()->quoteName('ordering') . ')')
                ->from($this->getDatabase()->quoteName('#__simplehub_groups'));

            $this->getDatabase()->setQuery($query);

            $maxOrdering = (int) $this->getDatabase()->loadResult();
            $data['ordering'] = $maxOrdering + 1;

            $data['title'] = $this->getUniqueTitle($data['title'] ?? '');
        }

        return parent::save($data);
    }

    /**
     * Bepaalt een unieke titel voor een nieuwe groep.
     *
     * Als de titel al bestaat, wordt het hoogste bestaande numerieke suffix
     * gezocht en met 1 verhoogd.
     *
     * Voorbeeld:
     *   Nieuws
     *   Nieuws (1)
     *   Nieuws (4)
     *
     * wordt:
     *   Nieuws (5)
     *
     * @param   string  $title  Gewenste titel.
     *
     * @return  string  Unieke titel.
     */
	private function getUniqueTitle(string $title): string
	{
	$title = trim($title);

	if ($title === '')
	{
		return $title;
	}

	$database = $this->getDatabase();
	$query    = $database->getQuery(true)
		->select($database->quoteName('title'))
		->from($database->quoteName('#__simplehub_groups'));

	$database->setQuery($query);

	$titles = $database->loadColumn();

	$normalizedTitle = mb_strtolower($title, 'UTF-8');
	$titleExists     = false;
	$maxSuffix       = 0;

	foreach ($titles as $existingTitle)
	{
		$normalizedExistingTitle = mb_strtolower($existingTitle, 'UTF-8');

		if ($normalizedExistingTitle === $normalizedTitle)
		{
			$titleExists = true;
			continue;
		}

		$pattern = '/^' . preg_quote($title, '/') . ' \((\d+)\)$/iu';

		if (preg_match($pattern, $existingTitle, $matches))
		{
			$suffix    = (int) $matches[1];
			$maxSuffix = max($maxSuffix, $suffix);
		}
	}

	if (!$titleExists)
	{
		return $title;
	}

	return $title . ' (' . ($maxSuffix + 1) . ')';

	}


    /**
     * Laadt de formulierdata.
     *
     * Eerst wordt gekeken of er formulierdata in de sessie staat
     * (na een validatiefout). Is die er niet, dan wordt het record
     * uit de database geladen.
     */
    protected function loadFormData(): object
    {
        $data = Factory::getApplication()->getUserState(
            'com_simplehub.edit.group.data',
            null
        );

        if ($data === null) {
            $data = $this->getItem();
        }

        return $data;
    }
}
