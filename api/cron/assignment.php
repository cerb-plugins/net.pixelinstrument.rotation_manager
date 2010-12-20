<?php
/*
 * class PiRotationManagerAssignment
 * Assign automatically the unassigned tickets to one of the workers
 */
class PiRotationManagerAssignment extends CerberusCronPageExtension {
	function run() {
		// Initialize the logger
		$logger = DevblocksPlatform::getConsoleLog();
		
		// initialize the translation service
		$translate = DevblocksPlatform::getTranslationService();
		
		// get the groups
		$groups = DAO_Group::getAll();
		
		$logger->info($translate->_('net.pixelinstrument.rotation_manager.assignment.begin'));
		
		
		foreach ($groups as $group_id => $group) {

			// look for tickets in each group
			$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.begin_group'), $group->name));
			
			// get the active scheme in the current group
			$scheme = array_pop(DAO_RotationScheme::getWhere("group_id = $group_id AND active = 1", "name", true, 1));
			
			if (!$scheme) {
				$logger->warn(vsprintf($translate->_('net.pixelinstrument.rotation_manager.warn.no_scheme_in_group'), $group->name));
			} else {
				$scheme_id = $scheme->id;
				
				// get the workers
				$context_workers = CerberusContexts::getWorkers(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $scheme_id);
				
				if (!sizeof($context_workers)) {
					$logger->warn(vsprintf($translate->_('net.pixelinstrument.rotation_manager.warn.no_workers_in_scheme'), $scheme->name));
				} else {
					// get the last worker id from the logs
					$last_worker_log = array_pop(DAO_RotationLog::getWhere("scheme_id = $scheme_id", "id", false, 1));
					
					// Start the operation
					$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.assignment.looking_for_tickets_to_assign'), $group->name));
					
					// get the unassigned tickets
					list($tickets,$null) = DAO_Ticket::search(
						array(),
						array(
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$group_id),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',0),
							new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WORKERS, DevblocksSearchCriteria::OPER_IS_NULL),
						),
						-1,
						0,
						SearchFields_Ticket::TICKET_UPDATED_DATE,
						false,
						false
					);
					
					// log the action
					$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.closer.tickets_to_be_assigned'), intval(sizeof($tickets))));
					
					if (sizeof($tickets)) {
						// get next worker
						$next_worker = false;
						
						if (!$last_worker_log || !array_key_exists( $last_worker_log->worker_id, $context_workers )) {
							reset($context_workers);
							$next_worker = current($context_workers);
						} else {
							while (list($worker_id,$worker) = each($context_workers)) {
								if ($worker->id == $last_worker_log->worker_id) {
									$next_worker = current($context_workers); // this is the NEXT worker!
									break;
								}
							}
							
							if (!$next_worker) {
								reset($context_workers);
								$next_worker = current($context_workers);
							}
						}
						
						foreach ($tickets as $ticket) {
							// assign the ticket
							CerberusContexts::setWorkers(CerberusContexts::CONTEXT_TICKET, $ticket['t_id'], array($next_worker->id));
							
							// write logs
							$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.assignment.ticket_assigned_to_reason'), array($ticket['t_mask'], $next_worker->getName(), "round robin")));
							
							// add log in the database
							$log_fields = array(
								DAO_RotationLog::DATE => time(),
								DAO_RotationLog::GROUP_ID => $group_id,
								DAO_RotationLog::SCHEME_ID => $scheme_id,
								DAO_RotationLog::WORKER_ID => $next_worker->id,
								DAO_RotationLog::TICKET_ID => $ticket['t_id'],
								DAO_RotationLog::REASON => 'Round Robin'
							);
							DAO_RotationLog::create($log_fields);
							
							// choose next worker for this iteration
							$next_worker = next($context_workers);
							if (!$next_worker) {
								reset($context_workers);
								$next_worker = current($context_workers);
							}
						}
					}
				}
			}
			
			// End of assignment for the group
			$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.end_group'), $group->name));
		}
		
		$logger->info($translate->_('net.pixelinstrument.rotation_manager.assignment.end'));
	}
};
?>
