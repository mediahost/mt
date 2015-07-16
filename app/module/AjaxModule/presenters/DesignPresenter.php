<?php

namespace App\AjaxModule\Presenters;

/**
 * Ajax design
 */
class DesignPresenter extends BasePresenter
{

	public function actionSetColor($color)
	{
		if ($this->user->loggedIn) {
			if ($this->designService->isAllowedColor($color)) {
				$this->designService->color = $color;
				$this->addData('color', $color);
			} else {
				$message = $this->translator->translate('This color isn\'t supported.');
				$this->setError($message);
			}
		} else {
			$message = $this->translator->translate('You aren\'t logged in.');
			$this->setError($message);
		}
	}

	public function actionSetLayout($layoutOption, $sidebarOption, $headerOption
	, $footerOption, $sidebarPosOption, $sidebarStyleOption, $sidebarMenuOption)
	{
		if ($this->user->loggedIn) {
			$userSettings = $this->designService->userSettings;
			$userSettings->layoutBoxed = $layoutOption === 'boxed';
			$userSettings->sidebarFixed = $sidebarOption === 'fixed';
			$userSettings->headerFixed = $headerOption === 'fixed';
			$userSettings->footerFixed = $footerOption === 'fixed';
			$userSettings->sidebarReversed = $sidebarPosOption === 'right';
			$userSettings->sidebarMenuLight = $sidebarStyleOption === 'light';
			$userSettings->sidebarMenuHover = $sidebarMenuOption === 'hover';
			$this->designService->saveUser();

			$this->addData('layoutBoxed', $this->designService->settings->layoutBoxed);
			$this->addData('sidebarFixed', $this->designService->settings->sidebarFixed);
			$this->addData('headerFixed', $this->designService->settings->headerFixed);
			$this->addData('footerFixed', $this->designService->settings->footerFixed);
			$this->addData('sidebarReversed', $this->designService->settings->sidebarReversed);
			$this->addData('sidebarMenuLight', $this->designService->settings->sidebarMenuLight);
			$this->addData('sidebarMenuHover', $this->designService->settings->sidebarMenuHover);
		} else {
			$message = $this->translator->translate('You aren\'t logged in.');
			$this->setError($message);
		}
	}

	public function actionSetSidebarClosed($value)
	{
		if ($this->user->loggedIn) {
			$this->designService->sidebarClosed = $value;
			$this->addData('sidebarClosed', $this->designService->settings->sidebarClosed);
		} else {
			$message = $this->translator->translate('You aren\'t logged in.');
			$this->setError($message);
		}
	}

}
