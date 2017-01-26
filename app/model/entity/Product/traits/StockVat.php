<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Vat;

trait StockVat
{

	/** @ORM\ManyToOne(targetEntity="Vat") */
	private $vat; // TODO: delete

	/**
	ALTER TABLE `stock`
	CHANGE `vat_a_id` `vat_a_id` int(11) NULL AFTER `vat_id`,
	CHANGE `vat_b_id` `vat_b_id` int(11) NULL AFTER `vat_a_id`,
	CHANGE `purchase_price` `purchase_price` double NULL AFTER `vat_b_id`,
	CHANGE `synchronize_price_a1` `synchronize_price_a1` tinyint(1) NOT NULL AFTER `default_price_b3`,
	CHANGE `synchronize_price_a2` `synchronize_price_a2` tinyint(1) NOT NULL AFTER `synchronize_price_a1`,
	CHANGE `synchronize_price_b1` `synchronize_price_b1` tinyint(1) NOT NULL AFTER `synchronize_price_a2`,
	CHANGE `synchronize_price_b2` `synchronize_price_b2` tinyint(1) NOT NULL AFTER `synchronize_price_b1`,
	CHANGE `synchronize_price_b3` `synchronize_price_b3` tinyint(1) NOT NULL AFTER `synchronize_price_b2`,
	CHANGE `default_price_a1` `default_price_a1` double NULL AFTER `purchase_price`,
	CHANGE `default_price_a2` `default_price_a2` double NULL AFTER `default_price_a1`,
	CHANGE `default_price_b1` `default_price_b1` double NULL AFTER `default_price_a2`,
	CHANGE `default_price_b2` `default_price_b2` double NULL AFTER `default_price_b1`,
	CHANGE `default_price_b3` `default_price_b3` double NULL AFTER `default_price_b2`,
	CHANGE `gift` `gift` varchar(50) COLLATE 'utf8_unicode_ci' NULL AFTER `deleted_at`,
	CHANGE `barcode` `barcode` varchar(50) COLLATE 'utf8_unicode_ci' NULL AFTER `gift`,
	CHANGE `active_a` `active_a` tinyint(1) NOT NULL AFTER `active`,
	CHANGE `active_b` `active_b` tinyint(1) NOT NULL AFTER `active_a`;
	ALTER TABLE `shop_variant`
	CHANGE `price_number` `price_number` smallint(6) NOT NULL AFTER `shop_id`;
	ALTER TABLE `payment`
	CHANGE `shop_variant_id` `shop_variant_id` int(11) NULL AFTER `id`,
	CHANGE `name` `name` varchar(255) COLLATE 'utf8_unicode_ci' NULL AFTER `shop_variant_id`,
	CHANGE `active` `active` tinyint(1) NOT NULL AFTER `name`,
	CHANGE `vat_id` `vat_id` int(11) NULL AFTER `active`,
	CHANGE `price` `price` double NULL AFTER `vat_id`,
	CHANGE `percent_price` `percent_price` double NULL AFTER `price`,
	CHANGE `use_cond1` `use_cond1` tinyint(1) NOT NULL AFTER `percent_price`,
	CHANGE `use_cond2` `use_cond2` tinyint(1) NOT NULL AFTER `use_cond1`,
	CHANGE `free_price` `free_price` double NULL AFTER `use_cond2`,
	CHANGE `need_address` `need_address` tinyint(1) NOT NULL AFTER `free_price`,
	CHANGE `is_card` `is_card` tinyint(1) NOT NULL AFTER `need_address`,
	CHANGE `is_on_delivery` `is_on_delivery` tinyint(1) NOT NULL AFTER `is_card`,
	CHANGE `is_homecredit_sk` `is_homecredit_sk` tinyint(1) NOT NULL AFTER `is_on_delivery`;
	ALTER TABLE `shipping`
	CHANGE `shop_variant_id` `shop_variant_id` int(11) NULL AFTER `id`,
	CHANGE `name` `name` varchar(255) COLLATE 'utf8_unicode_ci' NULL AFTER `shop_variant_id`,
	CHANGE `active` `active` tinyint(1) NOT NULL AFTER `name`,
	CHANGE `vat_id` `vat_id` int(11) NULL AFTER `active`,
	CHANGE `price` `price` double NULL AFTER `vat_id`,
	CHANGE `percent_price` `percent_price` double NULL AFTER `price`,
	CHANGE `use_cond1` `use_cond1` tinyint(1) NOT NULL AFTER `percent_price`,
	CHANGE `use_cond2` `use_cond2` tinyint(1) NOT NULL AFTER `use_cond1`,
	CHANGE `free_price` `free_price` double NULL AFTER `use_cond2`,
	CHANGE `need_address` `need_address` tinyint(1) NOT NULL AFTER `free_price`,
	CHANGE `locality` `locality` varchar(2) COLLATE 'utf8_unicode_ci' NULL AFTER `need_address`;

	ALTER TABLE `shop`
	CHANGE `price_letter` `price_letter` varchar(1) COLLATE 'utf8_unicode_ci' NOT NULL AFTER `id`;

	UPDATE `order` SET `shop_variant_id` = '1', `shop_id` = '1';

	UPDATE `stock` SET `vat_a_id` = `vat_id`, `default_price_a1` = `default_price`,
	                   `synchronize_price_a1` = '1', `synchronize_price_a2` = '1',
	                   `synchronize_price_b1` = '1', `synchronize_price_b2` = '1', `synchronize_price_b3` = '1',
	                   `active_a` = `active`, `active_b` = `active`;
	UPDATE `stock` SET `vat_b_id` = '4' WHERE `vat_a_id` = '1';
	UPDATE `stock` SET `vat_b_id` = '5' WHERE `vat_a_id` = '2';
	UPDATE `stock` SET `vat_b_id` = '7' WHERE `vat_a_id` = '3';

	UPDATE `payment` SET `shop_variant_id` = '1';
	UPDATE `shipping` SET `shop_variant_id` = '1';

	 */

	/** @ORM\ManyToOne(targetEntity="Vat") */
	private $vatA;

	/** @ORM\ManyToOne(targetEntity="Vat") */
	private $vatB;

	public function setVat(Vat $vat, $priceBase = NULL)
	{
		$priceBase = $priceBase ? $priceBase : $this->priceBase;
		$vatAttr = 'vat' . $priceBase;
		$this->$vatAttr = $vat;
		return $this;
	}

	public function getVat($priceBase = NULL)
	{
		$priceBase = $priceBase ? $priceBase : $this->priceBase;
		$vatAttr = 'vat' . $priceBase;
		return $this->$vatAttr;
	}

}
