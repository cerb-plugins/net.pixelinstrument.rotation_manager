<?php
class DAO_RotationScheme extends C4_ORMHelper {
	const ID = 'id';
	const GROUP_ID = 'group_id';
	const NAME = 'name';
	const ACTIVE = 'active';
	const CLOSE_DAYS = 'close_days';
	const ALERT_DAYS = 'alert_days';
    
    static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute("INSERT INTO rotation_scheme () VALUES ()");
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
    
    static function update($ids, $fields) {
		parent::_update($ids, 'rotation_scheme', $fields);
	}
    
    static function updateWhere($fields, $where) {
		parent::_updateWhere('rotation_scheme', $fields, $where);
	}
    
    static function getWhere($where=null, $sortBy='name', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, group_id, name, active, close_days, alert_days ".
			"FROM rotation_scheme ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	static function getAll() {
		return self::getWhere();
	}

    static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}

    static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_RotationScheme();
			$object->id = intval($row['id']);
			$object->group_id = intval($row['group_id']);
			$object->name = $row['name'];
			$object->active = intval($row['active']);
			$object->close_days = intval($row['close_days']);
			$object->alert_days = intval($row['alert_days']);
			
			$objects[$object->id] = $object;
		}
		
		return $objects;
	}
    
    static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
	
		// Delete database entries
		$db->Execute(sprintf("DELETE FROM rotation_scheme WHERE id IN (%s)", $ids_list));
		
		return true;
	}
    
    public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_RotationScheme::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"rotation_scheme.id as %s, ".
			"rotation_scheme.group_id as %s, ".
			"rotation_scheme.name as %s, ".
			"rotation_scheme.active as %s, ".
			"rotation_scheme.close_days as %s, ".
			"rotation_scheme.alert_days as %s ",
			    SearchFields_RotationScheme::ID,
			    SearchFields_RotationScheme::GROUP_ID,
				SearchFields_RotationScheme::NAME,
				SearchFields_RotationScheme::ACTIVE,
				SearchFields_RotationScheme::CLOSE_DAYS,
				SearchFields_RotationScheme::ALERT_DAYS
			 );
			
		$join_sql = "FROM rotation_scheme ";
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'rotation_scheme.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		// Virtuals
		foreach($params as $param) {
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_RotationScheme::VIRTUAL_WORKERS:
					$has_multiple_values = true;
					if(empty($param->value)) { // empty
						$join_sql .= "LEFT JOIN context_link AS context_owner ON (context_owner.from_context = 'net.pixelinstrument.contexts.rotation_scheme' AND context_owner.from_context_id = rotation_scheme.id AND context_owner.to_context = 'cerberusweb.contexts.worker') ";
						$where_sql .= "AND context_owner.to_context_id IS NULL ";
					} else {
						$join_sql .= sprintf("INNER JOIN context_link AS context_owner ON (context_owner.from_context = 'net.pixelinstrument.contexts.rotation_scheme' AND context_owner.from_context_id = rotation_scheme.id AND context_owner.to_context = 'cerberusweb.contexts.worker' AND context_owner.to_context_id IN (%s)) ",
							implode(',', $param->value)
						);
					}
					break;
			}
		}
		
		$result = array(
			'primary_table' => 'rotation_scheme',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
    
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			//($has_multiple_values ? 'GROUP BY document.id ' : '').
			$sort_sql;

		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_RotationScheme::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT rotation_scheme.id) " : "SELECT COUNT(rotation_scheme.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

    
    
}



class SearchFields_RotationScheme implements IDevblocksSearchFields {
	const ID = 'r_id';
	const GROUP_ID = 'r_group_id';
	const NAME = 'r_name';
	const ACTIVE = 'r_active';
	const CLOSE_DAYS = 'r_close_days';
	const ALERT_DAYS = 'r_alert_days';
	
	const VIRTUAL_WORKERS = '*_workers';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'rotation_scheme', 'id', $translate->_('common.id')),
			self::GROUP_ID => new DevblocksSearchField(self::GROUP_ID, 'rotation_scheme', 'group_id', $translate->_('net.pixelinstrument.rotation_manager.group')),
			self::NAME => new DevblocksSearchField(self::NAME, 'rotation_scheme', 'name', $translate->_('net.pixelinstrument.rotation_manager.name')),
			self::VIRTUAL_WORKERS => new DevblocksSearchField(self::VIRTUAL_WORKERS, '*', 'workers', $translate->_('common.owners')),
			self::ACTIVE => new DevblocksSearchField(self::ACTIVE, 'rotation_scheme', 'active', $translate->_('net.pixelinstrument.rotation_manager.active')),
			self::CLOSE_DAYS => new DevblocksSearchField(self::CLOSE_DAYS, 'rotation_scheme', 'close_days', $translate->_('net.pixelinstrument.rotation_manager.close_days')),
			self::ALERT_DAYS => new DevblocksSearchField(self::ALERT_DAYS, 'rotation_scheme', 'alert_days', $translate->_('net.pixelinstrument.rotation_manager.alert_days'))
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(Model_RotationScheme::CUSTOM_ROTATION_SCHEME);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;	
	}
};

class Model_RotationScheme {
	public $id;
	public $group_id;
	public $name;
	public $active;
	public $close_days;
	public $alert_days;
	
	const CUSTOM_ROTATION_SCHEME = 'net.pixelinstrument.contexts.rotation_scheme';
};

class View_RotationScheme extends C4_AbstractView {
	const DEFAULT_ID = 'rotation_scheme';
	const DEFAULT_TITLE = 'Rotation Schemes';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_RotationScheme::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_RotationScheme::NAME,
			SearchFields_RotationScheme::GROUP_ID,
			SearchFields_RotationScheme::ACTIVE,
			SearchFields_RotationScheme::CLOSE_DAYS,
			SearchFields_RotationScheme::ALERT_DAYS,
		);
		$this->addColumnsHidden(array(
			SearchFields_RotationScheme::ID,
			SearchFields_RotationScheme::VIRTUAL_WORKERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_RotationScheme::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		return $this->_objects = DAO_RotationScheme::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_RotationScheme', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$tpl->assign('timestamp_now', time());
		
		// Pull the results so we can do some row introspection
		$results = $this->getData();
		$tpl->assign('results', $results);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(Model_RotationScheme::CUSTOM_ROTATION_SCHEME);
		
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:net.pixelinstrument.rotation_manager::scheme/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('devblocks:net.pixelinstrument.rotation_manager::scheme/view.tpl');
				break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_RotationScheme::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			
			case SearchFields_RotationScheme::CLOSE_DAYS:
			case SearchFields_RotationScheme::ALERT_DAYS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_RotationScheme::GROUP_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_group.tpl');
				break;
			
			case SearchFields_RotationScheme::ACTIVE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			
			case SearchFields_RotationScheme::VIRTUAL_WORKERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
					
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_RotationScheme::VIRTUAL_WORKERS:
				if(empty($param->value)) {
					echo "Owners <b>are not assigned</b>";
					
				} elseif(is_array($param->value)) {
					$workers = DAO_Worker::getAll();
					$strings = array();
					
					foreach($param->value as $worker_id) {
						if(isset($workers[$worker_id]))
							$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
					}
					
					echo sprintf("Owner is %s", implode(' or ', $strings));
				}
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = !is_null($param_key) ? $param_key : $param->field;
		$translate = DevblocksPlatform::getTranslationService();
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_RotationScheme::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_RotationScheme::NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_RotationScheme::ACTIVE:
			case SearchFields_RotationScheme::GROUP_ID:
			case SearchFields_RotationScheme::CLOSE_DAYS:
			case SearchFields_RotationScheme::ALERT_DAYS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_RotationScheme::VIRTUAL_WORKERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $worker_ids);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'org_id':
					$change_fields[DAO_RotationScheme::ORG_ID] = $v;
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Document::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Document::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Document::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(Model_Document::CUSTOM_DOCUMENTS, $custom_fields, $batch_ids);
			
			// Owners
			if(isset($do['owner']) && is_array($do['owner'])) {
				$owner_params = $do['owner'];
				foreach($batch_ids as $batch_id) {
					if(isset($owner_params['add']) && is_array($owner_params['add']))
						CerberusContexts::addWorkers(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $batch_id, $owner_params['add']);
					if(isset($owner_params['remove']) && is_array($owner_params['remove']))
						CerberusContexts::removeWorkers(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $batch_id, $owner_params['remove']);
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}	
};

class Context_RotationScheme extends Extension_DevblocksContext {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    function getPermalink($context_id) {
    	// [TODO] Profiles
    	$url_writer = DevblocksPlatform::getUrlService();
    	return null;
    	//return $url_writer->write('c=home&tab=orgs&action=display&id='.$context_id, true);
    }
    
	function getContext($scheme, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Scheme:';
			
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Model_RotationScheme::CUSTOM_ROTATION_SCHEME);
		
		// Polymorph
		if(is_numeric($scheme)) {
			$scheme = DAO_RotationScheme::get($scheme);
		} elseif($scheme instanceof Model_RotationScheme) {
			// It's what we want already.
		} else {
			$scheme = null;
		}
			
		// Token labels
		$token_labels = array(
			'name' => $prefix.$translate->_('net.pixelinstrument.rotation_manager.name')
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Scheme token values
		if(null != $scheme) {
			$token_values['id'] = $scheme->id;
			if(!empty($scheme->name))
				$token_values['name'] = $scheme->name;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $scheme->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $scheme)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $scheme)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		return true;		
	}
	
	function getChooserView() {
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = 'View_RotationScheme';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Rotation Schemes';
		$view->view_columns = array(
			SearchFields_RotationScheme::NAME,
		);
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = 'View_RotationScheme';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Rotation Schemes';
		
		$params = array(
		);

		if(isset($options['filter_open']))
			true; // Do nothing
		
		$view->addParams($params, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};
?>
