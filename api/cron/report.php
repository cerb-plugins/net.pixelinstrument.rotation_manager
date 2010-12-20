<?php
/*
 * class PiRotationManagerReport
 * Periodically send out a report to all managers and workers with the list
 * of assigned tickets
 */
class PiRotationManagerReport extends CerberusCronPageExtension {
	function run() {
		// Initialize the logger
		$logger = DevblocksPlatform::getConsoleLog();
		
		// initialize the translation service
		$translate = DevblocksPlatform::getTranslationService();
		
		
		// get the groups
		$groups = DAO_Group::getAll();
		
		// get all the workers
		$workers = DAO_Worker::getAll();
		
		$logger->info($translate->_('net.pixelinstrument.rotation_manager.report.begin'));
		
		foreach ($groups as $group_id => $group) {
			// find workers in each group
			$logger->info("[Rotation Manager] --- BEGIN " . $group->name . " ---");
			
			// get the active scheme in the current group
			$scheme = array_pop(DAO_RotationScheme::getWhere("group_id = $group_id AND active = 1", "name", true, 1));
			
			if (!$scheme) {
				$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.begin_group'), $group->name));
			} else {
				$scheme_id = $scheme->id;
				
				// get the workers associated to this scheme
				$context_workers = CerberusContexts::getWorkers(Model_RotationScheme::CUSTOM_ROTATION_SCHEME, $scheme_id);
				
				if (!sizeof($context_workers)) {
					$logger->warn(vsprintf($translate->_('net.pixelinstrument.rotation_manager.warn.no_workers_in_scheme'), $scheme->name));
				} else {
					// Start the close operations
				$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.report.looking_to_open_tickets'), $group->name));
					
					// get the assigned and open tickets
					list($tickets,$null) = DAO_Ticket::search(
						array(),
						array(
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$group_id),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',0),
							new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WORKERS, 'not in', null), // translated to 'is not null'
						),
						-1,
						0,
						SearchFields_Ticket::TICKET_UPDATED_DATE,
						false,
						false
					);
					
					// log the action
					$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.report.open_tickets'), intval(sizeof($tickets))));
					
					if (sizeof($tickets)) {
						// start mail creation
						$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.start_email_for_group'), $group->name));
						
						$mailSubject = vsprintf($translate->_('net.pixelinstrument.rotation_manager.report.report_email.subject'), $group->name);
						$mailText = vsprintf($translate->_('net.pixelinstrument.rotation_manager.report.report_email.body'), $group->name);
						$mailText .= "\n\n";
					
						foreach ($tickets as $ticket) {
							// print out ticket id, workers and subject
							$ticket_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_TICKET, $ticket['t_id']);
							
							$workers_names = array();
							foreach ($ticket_workers as $worker) {
								array_push($workers_names, $worker->getName());
							}
							
							$mailText .= "[" . $ticket['t_mask'] ."] " . $ticket['t_subject'] ."\n";
							$mailText .= $translate->_('common.workers') . ": " . implode(", ", $workers_names) . "\n";
							$mailText .= $translate->_('net.pixelinstrument.rotation_manager.last_update') . ": " . date("r", $ticket['t_updated_date']) . "\n";
							
							$mailText .= "\n";
						}
						
						$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.end_email_for_group'), $group->name));
						
						// get destination emails
						$destination_emails = array();
						
						// managers
						$group_workers = DAO_Group::getTeamMembers($group_id);
						foreach( $group_workers as $worker_id => $worker ) {
							if( $worker->is_manager ) {
								if (isset($workers[$worker_id]))
									array_push( $destination_emails, $workers[$worker_id]->email );
							}
						}

						// workers in the rotation scheme
						foreach ($context_workers as $worker ) {
							$mail = $worker->email;
							if( array_search( $mail, $destination_emails ) === false )
								array_push( $destination_emails, $mail );
						}
						
						if (!sizeof($destination_emails)) {
							$logger->warn(vsprintf($translate->_('net.pixelinstrument.rotation_manager.warn.no_managers_or_workers_in_scheme'), $scheme->name));
						} else {
							foreach ($destination_emails as $mail) {
								$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.sending_mail_to'), $mail));
								if ($mail)
									CerberusMail::quickSend( $mail, $mailSubject, $mailText );
							}
						}
					}
				}
			}
			
			// End of assignment for the group
			$logger->info(vsprintf($translate->_('net.pixelinstrument.rotation_manager.end_group'), $group->name));
		}
		
		$logger->info($translate->_('net.pixelinstrument.rotation_manager.report.end'));
	}
};
?>
