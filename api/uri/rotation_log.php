<?php
class PiRotationLogTab extends Extension_RotationManagerTab {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		// Remember the tab
		$visit = CerberusApplication::getVisit();
		$visit->set(CerberusVisit::KEY_ACTIVITY_TAB, 'rotationlog');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// [TODO] Convert to $defaults
		
		if(null == ($view = C4_AbstractViewLoader::getView(View_RotationLog::DEFAULT_ID))) {
			$view = new View_RotationLog();
			$view->id = View_RotationLog::DEFAULT_ID;
			$view->renderSortBy = SearchFields_RotationLog::DATE;
			$view->renderSortAsc = 0;
			
			$view->name = "Rotation Logs";
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:net.pixelinstrument.rotation_manager::logs/logs.tpl');		
	}
}

?>
