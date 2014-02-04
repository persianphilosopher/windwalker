<?php

use Windwalker\DI\Container;
use Windwalker\Model\Filter\FilterHelper;
use Windwalker\Model\ListModel;

/**
 * Class FlowerModelSakuras
 *
 * @since 1.0
 */
class FlowerModelSakuras extends ListModel
{
	/**
	 * configureTables
	 *
	 * @return  void
	 */
	protected function configureTables()
	{
		$queryHelper = $this->getContainer()->get('model.sakuras.helper.query', Container::FORCE_NEW);

		$queryHelper->addTable('sakura', '#__flower_sakuras')
			->addTable('category',  '#__categories', 'sakura.catid      = category.id')
			->addTable('user',      '#__users',      'sakura.created_by = user.id')
			->addTable('viewlevel', '#__viewlevels', 'sakura.access     = viewlevel.id')
			->addTable('lang',      '#__languages',  'sakura.language   = lang.lang_code');

		$this->filterFields = array_merge($this->filterFields, $queryHelper->getFilterFields());
	}

	/**
	 * populateState
	 *
	 * @param null $ordering
	 * @param null $direction
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Build ordering prefix
		if (!$ordering)
		{
			$table = $this->getTable('Sakura');

			$ordering = property_exists($table, 'ordering') ? 'sakura.ordering' : 'sakura.id';

			$ordering = property_exists($table, 'catid') ? 'sakura.catid, ' . $ordering : $ordering;
		}

		parent::populateState($ordering, 'ASC');
	}

	/**
	 * processFilters
	 *
	 * @param JDatabaseQuery $query
	 * @param array          $filters
	 *
	 * @return  JDatabaseQuery
	 */
	protected function processFilters(\JDatabaseQuery $query, $filters = array())
	{
		// If no state filter, set published >= 0
		if (!isset($filters['sakura.published']) && property_exists($this->getTable(), 'published'))
		{
			$query->where($query->quoteName('sakura.published') . ' >= 0');
		}

		return parent::processFilters($query, $filters);
	}

	/**
	 * configureFilters
	 *
	 * @param FilterHelper $filterHelper
	 *
	 * @return  void
	 */
	protected function configureFilters($filterHelper)
	{
	}

	/**
	 * configureSearches
	 *
	 * @param \Windwalker\Model\Filter\SearchHelper $searchHelper
	 *
	 * @return  void
	 */
	protected function configureSearches($searchHelper)
	{
	}
}