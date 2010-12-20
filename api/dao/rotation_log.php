<?php
class DAO_RotationLog extends C4_ORMHelper {
	const ID = 'id';
	const DATE = 'date';
	const GROUP_ID = 'group_id';
	const SCHEME_ID = 'scheme_id';
	const WORKER_ID = 'worker_id';
	const TICKET_ID = 'ticket_id';
	const TICKET_MASK = 'ticket_mask';
	const REASON = 'reason';
    
    static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute("INSERT INTO rotation_log () VALUES ()");
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
    
    static function update($ids, $fields) {
		parent::_update($ids, 'rotation_log', $fields);
	}
    
    static function updateWhere($fields, $where) {
		parent::_updateWhere('rotation_log', $fields, $where);
	}
    
    static function getWhere($where=null, $sortBy='date', $sortAsc=false, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, date, group_id, scheme_id, worker_id, ticket_id, reason ".
			"FROM rotation_log ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
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
			$object = new Model_RotationLog();
			$object->id = intval($row['id']);
			$object->date = intval($row['date']);
			$object->group_id = intval($row['group_id']);
			$object->scheme_id = intval($row['scheme_id']);
			$object->worker_id = intval($row['worker_id']);
			$object->ticket_id = intval($row['ticket_id']);
			$object->reason = $row['reason'];
			
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
		$db->Execute(sprintf("DELETE FROM rotation_log WHERE id IN (%s)", $ids_list));
		
		return true;
	}
    
    public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_RotationLog::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"rotation_log.id as %s, ".
			"rotation_log.date as %s, ".
			"rotation_log.group_id as %s, ".
			"rotation_log.scheme_id as %s, ".
			"rotation_log.worker_id as %s, ".
			"rotation_log.ticket_id as %s, ".
			"ticket.mask as %s, ".
			"rotation_log.reason as %s ",
			    SearchFields_RotationLog::ID,
			    SearchFields_RotationLog::DATE,
				SearchFields_RotationLog::GROUP_ID,
				SearchFields_RotationLog::SCHEME_ID,
				SearchFields_RotationLog::WORKER_ID,
				SearchFields_RotationLog::TICKET_ID,
				SearchFields_RotationLog::TICKET_MASK,
				SearchFields_RotationLog::REASON
			 );
			
		$join_sql = "FROM rotation_log ";
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'rotation_log.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		// Ticket mask
		
		$join_sql .= "LEFT JOIN ticket ON (ticket.id = rotation_log.ticket_id) ";
		
		/*foreach($params as $param) {
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_RotationLog::TICKET_MASK:
					$has_multiple_values = false;
					if (!empty($param->value)) {
						$where_sql .= sprintf("AND ticket.id LIKE %s", $param->value);
					}
					break;
			}
		}*/
		
		$result = array(
			'primary_table' => 'rotation_log',
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
			$object_id = intval($row[SearchFields_RotationLog::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT rotation_log.id) " : "SELECT COUNT(rotation_log.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}  
};

class SearchFields_RotationLog implements IDevblocksSearchFields {
	const ID = 'r_id';
	const DATE = 'r_date';
	const GROUP_ID = 'r_group_id';
	const SCHEME_ID = 'r_scheme_id';
	const WORKER_ID = 'r_worker_id';
	const TICKET_ID = 'r_ticket_id';
	const REASON = 'r_reason';
	
	const TICKET_MASK = 'r_ticket_mask';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'rotation_log', 'id', $translate->_('common.id')),
			self::DATE => new DevblocksSearchField(self::DATE, 'rotation_log', 'date', $translate->_('net.pixelinstrument.rotation_manager.date')),
			self::GROUP_ID => new DevblocksSearchField(self::GROUP_ID, 'rotation_log', 'group_id', $translate->_('net.pixelinstrument.rotation_manager.group')),
			self::SCHEME_ID => new DevblocksSearchField(self::SCHEME_ID, 'rotation_log', 'scheme_id', $translate->_('net.pixelinstrument.rotation_manager.scheme')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'rotation_log', 'worker_id', $translate->_('net.pixelinstrument.rotation_manager.assigned_to')),
			self::TICKET_MASK => new DevblocksSearchField(self::TICKET_MASK, 'ticket', 'mask', $translate->_('net.pixelinstrument.rotation_manager.ticket')),
			self::REASON => new DevblocksSearchField(self::REASON, 'rotation_log', 'reason', $translate->_('net.pixelinstrument.rotation_manager.reason'))
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;	
	}
};

class Model_RotationLog {
	public $id;
	public $date;
	public $group_id;
	public $scheme_id;
	public $worker_id;
	public $ticket_id;
	public $ticket_mask;
	public $reason;
};

class View_RotationLog extends C4_AbstractView {
	const DEFAULT_ID = 'rotation_log';
	const DEFAULT_TITLE = 'Rotation Log';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_RotationLog::DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_RotationLog::DATE,
			SearchFields_RotationLog::GROUP_ID,
			SearchFields_RotationLog::SCHEME_ID,
			SearchFields_RotationLog::WORKER_ID,
			SearchFields_RotationLog::TICKET_MASK,
			SearchFields_RotationLog::REASON,
		);
		$this->addColumnsHidden(array(
			SearchFields_RotationLog::ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_RotationLog::ID,
			SearchFields_RotationLog::TICKET_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		return $this->_objects = DAO_RotationLog::search(
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
		return $this->_doGetDataSample('DAO_RotationLog', $size);
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
		
		$schemes = DAO_RotationScheme::getAll();
		$tpl->assign('schemes', $schemes);
		
		$tpl->assign('timestamp_now', time());
		
		// Pull the results so we can do some row introspection
		$results = $this->getData();
		$tpl->assign('results', $results);
		
		// get the tickets (only the ones that have a log)
		$ticket_ids = array();
		foreach ($results[0] as $result) {
			$ticket_id = $result['r_ticket_id'];
			$ticket_ids[$ticket_id] = $ticket_id;
		}
		
		$tickets = DAO_Ticket::getTickets($ticket_ids);
		$tpl->assign('tickets', $tickets);

		$tpl->display('devblocks:net.pixelinstrument.rotation_manager::logs/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_RotationLog::DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			
			case SearchFields_RotationLog::GROUP_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_group.tpl');
				break;
			
			case SearchFields_RotationLog::WORKER_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
			
			case SearchFields_RotationLog::TICKET_MASK:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			
			case SearchFields_RotationLog::SCHEME_ID:
				$tpl->display('devblocks:net.pixelinstrument.rotation_manager::internal/views/criteria/__context_scheme.tpl');
				break;
			
			case SearchFields_RotationLog::REASON:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
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
			case SearchFields_RotationScheme::VIRTUAL_TICKETS:
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
			case SearchFields_RotationLog::GROUP_ID:
				if(is_array($param->value)) {
					$groups = DAO_Group::getAll();
					$strings = array();
					
					foreach($param->value as $group_id) {
						if(isset($groups[$group_id]))
							$strings[] = '<b>'.$groups[$group_id]->name.'</b>';
					}
					
					echo sprintf("%s", implode(', ', $strings));
				}
				break;
			
			case SearchFields_RotationLog::WORKER_ID:
				if(is_array($param->value)) {
					$workers = DAO_Worker::getAll();
					$strings = array();
					
					foreach($param->value as $worker_id) {
						if(isset($workers[$worker_id]))
							$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
					}
					
					echo sprintf("%s", implode(', ', $strings));
				}
				break;
			
			case SearchFields_RotationLog::SCHEME_ID:
				if(is_array($param->value)) {
					$schemes = DAO_RotationScheme::getAll();
					$strings = array();
					
					foreach($param->value as $scheme_id) {
						if(isset($schemes[$scheme_id]))
							$strings[] = '<b>'.$schemes[$scheme_id]->name.'</b>';
					}
					
					echo sprintf("%s", implode(', ', $strings));
				}
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_RotationLog::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_RotationLog::DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
			
			case SearchFields_RotationLog::WORKER_ID:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $worker_ids);
				break;
			
			case SearchFields_RotationLog::GROUP_ID:
				@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $group_id);
				break;
			
			case SearchFields_RotationLog::SCHEME_ID:
				@$scheme_ids = DevblocksPlatform::importGPC($_REQUEST['rotation_scheme_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $scheme_ids);
				break;
			
			case SearchFields_RotationLog::REASON:
			case SearchFields_RotationLog::TICKET_MASK:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
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
};

?>
