<?php

require_once "Services/Repository/classes/class.ilObjectPluginGUI.php";
require_once "Customizing/global/plugins/Services/Repository/RepositoryObject/H5P/classes/class.ilH5PPlugin.php";
require_once "Services/Form/classes/class.ilPropertyFormGUI.php";
require_once "Services/Form/classes/class.ilSelectInputGUI.php";
require_once "Services/AccessControl/classes/class.ilPermissionGUI.php";
require_once "Customizing/global/plugins/Services/Repository/RepositoryObject/H5P/classes/H5P/ActiveRecord/class.ilH5PContent.php";
require_once "Customizing/global/plugins/Services/Repository/RepositoryObject/H5P/classes/H5P/Framework/class.ilH5PFramework.php";

/**
 * H5P GUI
 *
 * @ilCtrl_isCalledBy ilObjH5PGUI: ilRepositoryGUI,
 * @ilCtrl_isCalledBy ilObjH5PGUI: ilObjPluginDispatchGUI
 * @ilCtrl_isCalledBy ilObjH5PGUI: ilAdministrationGUI
 * @ilCtrl_Calls      ilObjH5PGUI: ilPermissionGUI
 * @ilCtrl_Calls      ilObjH5PGUI: ilInfoScreenGUI
 * @ilCtrl_Calls      ilObjH5PGUI: ilObjectCopyGUI
 * @ilCtrl_Calls      ilObjH5PGUI: ilCommonActionDispatcherGUI
 */
class ilObjH5PGUI extends ilObjectPluginGUI {

	const CMD_PERMISSIONS = "perm";
	const CMD_SETTINGS = "settings";
	const CMD_SETTINGS_STORE = "settingsStore";
	const CMD_SHOW_H5P = "showH5p";
	const TAB_CONTENT = "content";
	const TAB_PERMISSIONS = "perm_settings";
	const TAB_SETTINGS = "settings";
	/**
	 * @var ilObjH5P
	 */
	var $object;
	/**
	 * @var ilH5PPlugin
	 */
	protected $plugin;
	/**
	 * @var ilH5PFramework
	 */
	protected $h5p_framework;
	/**
	 * @var ilObjUser
	 */
	protected $user;


	protected function afterConstructor() {
		/**
		 * @var ilObjUser $ilUser
		 */

		global $ilUser;

		$this->user = $ilUser;

		$this->h5p_framework = new ilH5PFramework();
	}


	/**
	 * @return string
	 */
	final function getType() {
		return ilH5PPlugin::ID;
	}


	/**
	 * @param string $cmd
	 */
	function performCommand($cmd) {
		switch ($cmd) {
			case self::CMD_SHOW_H5P:
			case self::CMD_SETTINGS:
			case self::CMD_SETTINGS_STORE:
				$this->{$cmd}();
				break;
		}
	}


	/**
	 * @param string $html
	 */
	protected function show($html) {
		$this->tpl->setTitle($this->object->getTitle());

		$this->tpl->setDescription($this->object->getDescription());

		$this->tpl->setContent($html);
	}


	/**
	 * @param string $a_new_type
	 *
	 * @return ilPropertyFormGUI
	 */
	function initCreateForm($a_new_type) {
		$packages = [ "" => "&lt;" . $this->txt("xhfp_please_select") . "&gt;" ] + ilH5PContent::getPackagesArray();

		$form = parent::initCreateForm($a_new_type);

		$package = new ilSelectInputGUI($this->txt("xhfp_package"), "xhfp_package");
		$package->setRequired(true);
		$package->setOptions($packages);
		$form->addItem($package);

		return $form;
	}


	/**
	 * @param ilObjH5P $a_new_object
	 */
	function afterSave(ilObject $a_new_object) {
		$content_id = filter_input(INPUT_POST, "xhfp_package");
		$user_data = $a_new_object->getUserData();

		$user_data->setContentMainId($content_id);

		$user_data->setDataId($a_new_object->getId());

		$user_data->setUserId($this->user->getId());

		$user_data->create();

		$content = $this->h5p_framework->h5p_core->loadContent($content_id);
		$content["id"] = $content_id;

		$this->h5p_framework->h5p_core->filterParameters($content);

		parent::afterSave($a_new_object);
	}


	/**
	 *
	 */
	protected function showH5p() {
		$this->tabs_gui->activateTab(self::TAB_CONTENT);

		$this->h5p_framework->addCore();

		$content = $this->h5p_framework->h5p_core->loadContent($this->object->getUserData()->getContentMainId());

		$content_dependencies = $this->h5p_framework->h5p_core->loadContentDependencies($this->object->getUserData()
			->getContentMainId(), "preloaded");
		$files = $this->h5p_framework->h5p_core->getDependenciesFiles($content_dependencies, ilH5PFramework::getH5PFolder());
		// TODO double slashes

		$core_scripts = array_map(function ($file) {
			return (ilH5PFramework::CORE_PATH . $file);
		}, H5PCore::$scripts);

		$core_styles = array_map(function ($file) {
			return (ilH5PFramework::CORE_PATH . $file);
		}, H5PCore::$styles);

		$scripts = array_map(function ($file) {
			return $file->path;
		}, $files["scripts"]);

		$styles = array_map(function ($file) {
			return $file->path;
		}, $files["styles"]);

		$H5PIntegration = [
			"baseUrl" => "",
			"url" => ilH5PFramework::getH5PFolder(),
			"postUserStatistics" => false,
			"ajaxPath" => "",
			"ajax" => [
				"setFinished" => "",
				"contentUserData" => ""
			],
			"saveFreq" => 30,
			"user" => [
				"name" => "",
				"mail" => ""
			],
			"siteUrl" => "",
			"l10n" => [
				"H5P" => $this->h5p_framework->h5p_core->getLocalization()
			],
			"loadedJs" => $scripts,
			"loadedCss" => $styles,
			"core" => [
				"scripts" => $core_scripts,
				"styles" => $core_styles
			],
			"contents" => [
				("cid-" . $content["contentId"]) => [
					"library" => H5PCore::libraryToString($content["library"]),
					"jsonContent" => $content["params"],
					"fullScreen" => false,
					"exportUrl" => "",
					"embedCode" => "",
					"resizeCode" => "",
					"mainId" => 0,
					"url" => "",
					"title" => $content["title"],
					"contentUserData" => [],
					"displayOptions" => [
						"frame" => false,
						"export" => false,
						"embed" => false,
						"copyright" => false,
						"icon" => false
					],
					"styles" => [],
					"scripts" => []
				]
			]
		];

		foreach ($scripts as $script) {
			$this->tpl->addJavaScript($script);
		}

		foreach ($core_styles as $style) {
			$this->tpl->addCss($style, "");
		}

		$tmpl = $this->plugin->getTemplate("H5PIntegration.html");

		$tmpl->setCurrentBlock("scriptBlock");
		$tmpl->setVariable("H5P_INTERGRATION", ilH5PFramework::jsonToString($H5PIntegration));
		$tmpl->parseCurrentBlock();

		$tmpl->setCurrentBlock("contentBlock");
		$tmpl->setVariable("H5P_CONTENT_ID", $content["contentId"]);

		$this->show($tmpl->get());
	}


	/**
	 *
	 */
	protected function getSettingsForm() {
		$packages = ilH5PContent::getPackagesArray();

		$current_package = $this->object->getUserData()->getContentMainId();
		if ($current_package === NULL) {
			$packages = [ "" => "&lt;" . $this->txt("xhfp_please_select") . "&gt;" ] + $packages;
		}

		$form = new ilPropertyFormGUI();

		$form->setFormAction($this->ctrl->getFormAction($this));

		$form->setTitle($this->lng->txt(self::TAB_SETTINGS));

		$form->addCommandButton(self::CMD_SETTINGS_STORE, $this->txt("xhfp_save"));
		$form->addCommandButton(self::CMD_SHOW_H5P, $this->lng->txt("cancel"));

		$title = new ilTextInputGUI($this->lng->txt("title"), "xhfp_title");
		$title->setRequired(true);
		$title->setValue($this->object->getTitle());
		$form->addItem($title);

		$description = new ilTextAreaInputGUI($this->lng->txt("description"), "xhfp_description");
		$description->setValue($this->object->getLongDescription());
		$form->addItem($description);

		$package = new ilSelectInputGUI($this->txt("xhfp_package"), "xhfp_package");
		$package->setRequired(true);
		$package->setOptions($packages);
		$package->setValue($current_package);
		$package->setDisabled(true);
		$form->addItem($package);

		return $form;
	}


	/**
	 *
	 */
	protected function settings() {
		$this->tabs_gui->activateTab(self::TAB_SETTINGS);

		$form = $this->getSettingsForm();

		$this->show($form->getHTML());
	}


	/**
	 *
	 */
	protected function settingsStore() {
		$form = $this->getSettingsForm();

		$form->setValuesByPost();

		if (!$form->checkInput()) {
			$this->show($form->getHTML());

			return;
		}

		$title = $form->getInput("xhfp_title");
		$this->object->setTitle($title);

		$description = $form->getInput("xhfp_description");
		$this->object->setDescription($description);

		/*$content_id = $form->getInput("xhfp_package");
		$this->object->getUserData()->setContentMainId($content_id);*/

		$this->object->update();

		ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);

		$this->show($form->getHTML());

		$this->ctrl->redirect($this, self::CMD_SHOW_H5P);
	}


	/**
	 *
	 */
	protected function setTabs() {
		$this->tabs_gui->addTab(self::TAB_CONTENT, $this->lng->txt(self::TAB_CONTENT), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_H5P));

		$this->tabs_gui->addTab(self::TAB_SETTINGS, $this->lng->txt(self::TAB_SETTINGS), $this->ctrl->getLinkTarget($this, self::CMD_SETTINGS));

		$this->tabs_gui->addTab(self::TAB_PERMISSIONS, $this->lng->txt(self::TAB_PERMISSIONS), $this->ctrl->getLinkTargetByClass([
			self::class,
			ilPermissionGUI::class,
		], self::CMD_PERMISSIONS));

		$this->tabs_gui->manual_activation = true; // Show all tabs as links when no activation
	}


	/**
	 * @return string
	 */
	function getAfterCreationCmd() {
		return self::getStandardCmd();
	}


	/**
	 * @return string
	 */
	function getStandardCmd() {
		return self::CMD_SHOW_H5P;
	}
}
