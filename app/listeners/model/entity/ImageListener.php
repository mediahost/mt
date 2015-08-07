<?php

namespace App\Listeners\Model\Entity;

use App\Extensions\Foto;
use App\Helpers;
use App\Model\Entity\Image;
use Doctrine\ORM\Events;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Nette\Utils\Image as ImageUtils;

class ImageListener extends Object implements Subscriber
{

	/** @var Foto @inject */
	public $fotoService;

	public function getSubscribedEvents()
	{
		return array(
			Events::prePersist,
			Events::preUpdate,
			Events::postRemove,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function prePersist($params)
	{
		$this->saveImage($params);
	}

	public function preUpdate($params)
	{
		$this->saveImage($params);
	}

	public function postRemove($params)
	{
		$this->removeImage($params);
	}

	// </editor-fold>

	private function saveImage(Image $image)
	{
		if ($image->changed) {
			$realFilename = $this->createNewImage($image, $image->folder);
			if ($image->filename) {
				$this->fotoService->delete($image->filename);
			}
			$image->filename = Helpers::getPath($image->folder, $realFilename);
		}
	}

	private function removeImage(Image $image)
	{
		if ($image->filename) {
			$this->fotoService->delete($image->filename);
		}
	}

	private function createNewImage(Image $image, $folder)
	{
		$requestedFormat = ImageUtils::PNG;
		$filename = $image->requestedFilename;
		$this->fotoService->create($image->source, $filename, $folder, $requestedFormat);
		return $filename;
	}

}
