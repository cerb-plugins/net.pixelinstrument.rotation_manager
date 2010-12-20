<?php
abstract class Extension_RotationManagerTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	function showTab() {}
	function saveTab() {}
};

class PiRotationManagerPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
		
	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$tpl->assign('request_path', implode('/',$response->path));

		// Remember the last tab/URL
		$visit = CerberusApplication::getVisit();
		if(null == ($selected_tab = @$response->path[1])) {
			$selected_tab = $visit->get(CerberusVisit::KEY_ACTIVITY_TAB, '');
		}
		$tpl->assign('selected_tab', $selected_tab);

		// Path
		$stack = $response->path;
		array_shift($stack);
		
		// active worker
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl->assign('active_worker', $active_worker);
		
		$tab_manifests = DevblocksPlatform::getExtensions('net.pixelinstrument.rotation_manager.tab', false);
		//uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('tab_manifests', $tab_manifests);
		
		$tpl->display('devblocks:net.pixelinstrument.rotation_manager::index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_RotationManagerTab) {
			$inst->showTab();
		}
	}
	
	function showRotationSchemePeekAction() {
		return PiRotationSchemeTab::showRotationSchemePeek();
	}
	
	function saveRotationSchemePeekAction() {
		return PiRotationSchemeTab::saveRotationSchemePeek();
	}
	
	function saveRotationSchemeSingleWorkerAction() {
		return PiRotationSchemeTab::saveRotationSchemeSingleWorker();
	}
};
?>