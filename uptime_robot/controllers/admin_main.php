<?php
/**
 * Uptime Robot main controller
 * 
 * @package blesta
 * @subpackage blesta.plugins.uptime_robot
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends UptimeRobotController {
	/**
	 * @var System status values for each status
	 */
	private $status_values = array(
		'cron' => array('serious'=>75, 'minor'=>50),
		'cron_task_stalled' => array('serious'=>25, 'minor'=>25),
		'trial' => array('serious'=>0, 'minor'=>0),
		'invoices' => array('serious'=>30, 'minor'=>30),
		'backup' => array('serious'=>15, 'minor'=>15),
		'updates' => array('serious'=>15, 'minor'=>0),
	);
	/**
	 * @var Time (in seconds) that must pass without a task ending before we deem it stalled
	 */
	private $stalled_time = 3600;
	
	
	/**
	 * Load language
	 */
	public function preAction() {
		parent::preAction();
		
		$this->requireLogin();
		
		Language::loadLang("admin_main", null, PLUGINDIR . "uptime_robot" . DS . "language" . DS);
	}

	
	/**
	 * Renders the system status widget
	 */
	public function index() {
		function ping($server) {
			set_time_limit(3);
			if(strtolower(PHP_OS) == 'darwin'){ exec("ping -c 1 ".$server, $r); } else { exec("/bin/ping -c 1 -i 0.2 -W 1 ".$server, $r); } 
			$r = print_r($r, true);
			if(preg_match("/bytes from/i", $r)){ return true; } else { return false; }
		}
		include 'config.php';
			//Process Requests
				if(!empty($_POST)){
					//Add Server
					if(!empty($_POST['hostname']) && !empty($_POST['ip_address']) && !empty($_POST['server_os'])){
						$f = "\n".'$server_robot[] = array(\'hostname\' => \''.html_entity_decode($_POST['hostname']).'\', \'ip\' => \''.html_entity_decode($_POST['ip_address']).'\', \'os\' => \''.html_entity_decode($_POST['server_os']).'\', \'auto_manage\' => '.html_entity_decode($_POST['auto_manage']).', \'root_password\' => \''.base64_encode($_POST['root_password']).'\');';
						file_put_contents(dirname(__FILE__).'/config.php', $f, FILE_APPEND | LOCK_EX);
					}
					//Configuration
					if(!empty($_POST['admin_email']) && !empty($_POST['deliver_method'])){
						$f = '<?php'."\n".'$config = array(\'deliver_method\' => \''.html_entity_decode($_POST['deliver_method']).'\', \'admin_email\' => \''.html_entity_decode($_POST['admin_email']).'\', \'admin_phone\' => \''.html_entity_decode($_POST['admin_phone']).'\', \'twilio_sid\' => \''.html_entity_decode($_POST['twilio_sid']).'\', \'twilio_token\' => \''.html_entity_decode($_POST['twilio_token']).'\', \'twilio_number\' => \''.html_entity_decode($_POST['twilio_number']).'\');';
							foreach($server_robot as $server_array){
								$f .= "\n".'$server_robot[] = array(\'hostname\' => \''.html_entity_decode($server_array['hostname']).'\', \'ip\' => \''.html_entity_decode($server_array['ip']).'\', \'os\' => \''.html_entity_decode($server_array['os']).'\', \'auto_manage\' => '.html_entity_decode($server_array['auto_manage']).', \'root_password\' => \''.html_entity_decode($server_array['root_password']).'\');';
							}
						file_put_contents(dirname(__FILE__).'/config.php', $f, LOCK_EX);
					}
					//Delete Server
					if(!empty($_POST['delete_server'])){
						$f = '<?php'."\n".'$config = array(\'deliver_method\' => \''.$config['deliver_method'].'\', \'admin_email\' => \''.$config['admin_email'].'\', \'admin_phone\' => \''.$config['admin_phone'].'\', \'twilio_sid\' => \''.$config['twilio_sid'].'\', \'twilio_token\' => \''.$config['twilio_token'].'\', \'twilio_number\' => \''.$config['twilio_number'].'\');';
							foreach($server_robot as $server_array){
								if(!($server_array['hostname'] == $_POST['delete_server'])){
									$f .= "\n".'$server_robot[] = array(\'hostname\' => \''.html_entity_decode($server_array['hostname']).'\', \'ip\' => \''.html_entity_decode($server_array['ip']).'\', \'os\' => \''.html_entity_decode($server_array['os']).'\', \'auto_manage\' => '.html_entity_decode($server_array['auto_manage']).', \'root_password\' => \''.html_entity_decode($server_array['root_password']).'\');';
								}
							}
						file_put_contents(dirname(__FILE__).'/config.php', $f, LOCK_EX);
					}
					$this->redirect($this->base_uri);
				}
								
		$this->set("config", $config);
		$this->set("server_robot", $server_robot);
		$this->set("health_status", $this->getStatusLanguage($uptime_robot));
		
		return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) ? false : null);
	}
	
	/**
	 * Settings
	 */
	public function settings() {
		// Only available via AJAX
		if (!$this->isAjax()) {
			$this->redirect($this->base_uri);
		}
		
		return $this->renderAjaxWidgetIfAsync(false);
	}
	
	/**
	 * Retrieves the system status language to use based on the overall status
	 */
	private function getStatusLanguage($uptime_robot) {
		if ($uptime_robot <= 50)
			return Language::_("AdminMain.index.health_poor", true);
		elseif ($uptime_robot <= 75)
			return Language::_("AdminMain.index.health_fair", true);
		elseif ($uptime_robot <= 95)
			return Language::_("AdminMain.index.health_good", true);
		return Language::_("AdminMain.index.health_excellent", true);
	}
}
?>