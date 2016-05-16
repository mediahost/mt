<?php

namespace App\AppModule\Presenters;

use App\Components\Page\Form\IPageEditFactory;
use App\Components\Page\Form\PageEdit;
use App\Components\Page\Grid\IPagesGridFactory;
use App\Components\Page\Grid\PagesGrid;
use App\Extensions\FilesManager;
use App\Helpers;
use App\Model\Entity\Page;
use App\Model\Repository\PageRepository;
use Exception;
use Nette\Http\FileUpload;
use Nette\Http\Request;
use Nette\Utils\FileSystem;
use Tracy\Debugger;

class ImagesPresenter extends BasePresenter
{
	const UPLOAD = 'uploaded';

	/** @var Request @inject */
	public $request;

	/** @var FilesManager @inject */
	public $filesManager;

	/**
	 * @secured
	 * @resource('images')
	 * @privilege('upload')
	 */
	public function actionUpload()
	{
		$files = $this->request->getFiles();
		if (array_key_exists('image1', $files)) {
			/** @var FileUpload $file */
			$file = $files['image1'];
			if ($file && $file->isOk() && $file->isImage()) {
				$filename = time() . $file->getSanitizedName();
				$dir = $this->filesManager->getImagePath(self::UPLOAD);
				$destination = Helpers::getPath($dir, $filename);
				$file->move($destination);

				$baseUrl = $this->request->url->getBaseUrl();
				$filePath = $this->filesManager->getImagePath(self::UPLOAD, FALSE);
				$this->payload->path = $baseUrl . $filePath . '/' . $filename;
				$this->payload->name = $file->getSanitizedName();
				$this->payload->state = 'OK';
			} else {
				$this->payload->state = 'error';
			}
		} else {
			$this->payload->state = 'failed';
		}
		$this->sendPayload();
	}

}
