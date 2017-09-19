<?php
/**
 * System Status parent controller for all System Statys child controllers to inherit from
 * 
 * @package blesta
 * @subpackage blesta.plugins.uptime_robot
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class UptimeRobotController extends AppController {

	public function preAction() {
		parent::preAction();
		
		// Override default view directory
		$this->view->view = "default";
		$this->structure->view = "default";
	}
}
?>