<?php
class PiRotationSchemeTab extends Extension_RotationManagerTab {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		// Remember the tab
		$visit = CerberusApplication::getVisit();
		$visit->set(CerberusVisit::KEY_ACTIVITY_TAB, 'rotationscheme');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// [TODO] Convert to $defaults
		
		if(null == ($view = C4_AbstractViewLoader::getView(View_RotationScheme::DEFAULT_ID))) {
			$view = new View_RotationScheme();
			$view->id = View_RotationScheme::DEFAULT_ID;
			$view->renderSortBy = SearchFields_RotationScheme::NAME;
			$view->renderSortAsc = 1;
			
			$view->name = "Rotation Schemes";
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:net.pixelinstrument.rotation_manager::scheme/schemes.tpl');		
	}

	function showRotationSchemePeek() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(Model_RotationScheme::CUSTOM_ROTATION_SCHEME); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);

		// Workers
		$context_workers = CerberusContexts::getWorkers(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $id);
		$tpl->assign('context_workers', $context_workers);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Groups
		list($groups,$null) = DAO_Group::search(
			array(),
			array(),
			0,
			0,
			SearchFields_Group::NAME,
			true,
			false
		);
		$tpl->assign('groups', $groups);
		
		// Rotation Scheme
		if ($id) {
			$rotation_scheme = DAO_RotationScheme::get($id);
			$tpl->assign('rotation_scheme', $rotation_scheme);
		}
		
		// Group workers
		$worker_groups = array();
		foreach($groups as $group) {
			$group_workers = DAO_Group::getTeamMembers($group['g_id']);
			foreach($group_workers as $group_worker) {
				if(!isset($worker_groups[$group_worker->id]))
					$worker_groups[$group_worker->id] = array();
				
				array_push($worker_groups[$group_worker->id], $group['g_id']);
			}
		}
		$tpl->assign('worker_groups', $worker_groups);
		
		// View
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('devblocks:net.pixelinstrument.rotation_manager::scheme/peek.tpl');
	}
	
	function saveRotationSchemePeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// read form parameters
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$active = DevblocksPlatform::importGPC($_REQUEST['active'],'boolean',0);
		@$close_days = DevblocksPlatform::importGPC($_REQUEST['close_days'],'integer',0);
		@$alert_days = DevblocksPlatform::importGPC($_REQUEST['alert_days'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
			
		// check if the scheme should be deleted
		if(!empty($id) && !empty($do_delete)) { // delete
			if( $active_worker->hasPriv('net.pixelinstrument.rotation_manager.acl.delete_scheme') ) {
				DAO_RotationScheme::delete($id);
			}
			
			return;
		}
		
		$userHasPermission = $active_worker->hasPriv('net.pixelinstrument.rotation_manager.acl.update_scheme');
		
		if( $userHasPermission ) {
			// check the group
			if( !$group_id ) {
				return;
			}
			
			// if the scheme is active, disable all others
			if ($active) {
				$disable_schemes = array(
					DAO_RotationScheme::ACTIVE => 0
				);
				
				$ids = DAO_RotationScheme::updateWhere($disable_schemes, "group_id = $group_id");
			}
			
			
			// create/update the scheme
			$rotation_scheme = array(
				DAO_RotationScheme::NAME => $name,
				DAO_RotationScheme::GROUP_ID => $group_id,
				DAO_RotationScheme::ACTIVE => $active,
				DAO_RotationScheme::CLOSE_DAYS => $close_days,
				DAO_RotationScheme::ALERT_DAYS => $alert_days,
			);
			
			if (!$id)
				$id = DAO_RotationScheme::create($rotation_scheme);
			else
				DAO_RotationScheme::update( $id, $rotation_scheme );
			
			if( $id ) {
				// Workers
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				CerberusContexts::setWorkers(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $id, $worker_ids);
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $id, $field_ids);
			}
		}
	}
		
	function saveRotationSchemeSingleWorker() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$userHasPermission = $active_worker->hasPriv('net.pixelinstrument.rotation_manager.acl.update_themselves');
		
		if( $userHasPermission ) {
			// read form parameters
			@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
			
			if( $id ) {
				// Workers
				@$worker_in_scheme = DevblocksPlatform::importGPC($_REQUEST['worker_in_scheme'],'integer',0);
				if ($worker_in_scheme) {
					CerberusContexts::addWorkers(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $id, array($active_worker->id));
				} else {
					CerberusContexts::removeWorkers(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $id, array($active_worker->id));
				}
			}
		}
	}
	
}

?>
