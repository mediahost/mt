<?php

namespace App\Extensions\Settings\Model\Service;

/**
 * @property-read string $itemsPerPage
 * @property-read string $itemsPerRow
 * @property-read string $rowsPerPage
 */
class PageConfigService extends BaseService
{

	public function getItemsPerPage()
	{
		if (isset($this->defaultStorage->pageConfig->itemsPerPage)) {
			return $this->defaultStorage->pageConfig->itemsPerPage;
		}
		return NULL;
	}

	public function getItemsPerRow()
	{
		if (isset($this->defaultStorage->pageConfig->itemsPerRow)) {
			return $this->defaultStorage->pageConfig->itemsPerRow;
		}
		return NULL;
	}

	public function getRowsPerPage()
	{
		if (isset($this->defaultStorage->pageConfig->rowsPerPage)) {
			return $this->defaultStorage->pageConfig->rowsPerPage;
		}
		return NULL;
	}

}
