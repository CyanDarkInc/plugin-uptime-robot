<?php
/**
 * System Status plugin handler
 * 
 * @package blesta
 * @subpackage blesta.plugins.uptime_robot
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class UptimeRobotPlugin extends Plugin {

	/**
	 * @var string The version of this plugin
	 */
	private static $version = "1.3.1";
	/**
	 * @var string The authors of this plugin
	 */
	private static $authors = array(array('name'=>"Phillips Data, Inc.",'url'=>"http://www.blesta.com"));
	
	public function __construct() {
		Language::loadLang("uptime_robot_plugin", null, dirname(__FILE__) . DS . "language" . DS);
	}
	
	/**
	 * Returns the name of this plugin
	 *
	 * @return string The common name of this plugin
	 */
	public function getName() {
		return Language::_("UptimeRobotPlugin.name", true);	
	}
	
	/**
	 * Returns the version of this plugin
	 *
	 * @return string The current version of this plugin
	 */
	public function getVersion() {
		return self::$version;
	}

	/**
	 * Returns the name and URL for the authors of this plugin
	 *
	 * @return array The name and URL of the authors of this plugin
	 */
	public function getAuthors() {
		return self::$authors;
	}
	
	/**
	 * Performs any necessary bootstraping actions
	 *
	 * @param int $plugin_id The ID of the plugin being installed
	 */
	public function install($plugin_id) {
        Loader::loadModels($this, array("CronTasks"));
 
        $task = array(
            'key' => "uptime_robot", // a string used to identify this cron task
            'plugin_dir' => "uptime_robot", // the plugin directory of this plugin
            'name' => "Uptime Robot", // the name of this cron task
            'description' => "Everyone with a website knows that, things can sometimes go wrong. Sometimes it is with the code, the server or the network. Uptime Robot is all about helping you to keep your websites up.", // the description of this task
            'type' => "interval" // "time" = once per day at a defined time, "interval" = every few minutes or hours
        );
        $this->CronTasks->add($task);
        $this->CronTasks->addTaskRun($this->CronTasks->getByKey('uptime_robot'), $task);
	}

	/**
	 * This method is invoked once for each different cron task configured by the plugin
	 * and identified by $key
	 *
	 * @param string $key A string used to identify this cron task
	 */
	public function cron($key) {
		include 'controllers/config.php';
		include 'lib/functions.php';

			foreach($server_robot as $server){
				if(!ping($server['ip'])){
				
					//Uptime Robot
					if($config['deliver_method'] == 'email'){
						$subject = $server['hostname'].' is down.';
						$message = 'The server '.$server['hostname'].'['.$server['ip'].'] are down. Date:'.date('d-m-Y g:i A');
						$header =  'From: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".
    							   'Reply-To: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".
    							   'X-Mailer: PHP/' . phpversion();
						mail($config['admin_email'], $subject, $message, $header);
					} elseif ($config['deliver_method'] == 'twilio' && rand(0, 10) == 1){
						include('lib/twilio/Services/Twilio.php');
						$msg = 'The server '.$server['hostname'].'('.$server['ip'].') are down. Date:'.date('d-m-Y g:i A');
						$client = new Services_Twilio($config['twilio_sid'], $config['twilio_token']);
						$client = $client->account->sms_messages->create('+'.$config['twilio_number'], '+'.$config['admin_phone'], $msg, array());
					}
					
				}
				if($server['auto_manage'] == 1 && $server['os'] =! 'windows' && rand(0, 1440) == 1){
				
					//Server Monitoring
					if(ItsSshActivated($server['ip'], 22, 'root', base64_decode($server['root_password']))){
						$os = $server['os'];
						if($server['os'] == 'centos'){$os = 'rhel';}
						if($server['os'] == 'other'){$os = 'bsd';}
						cleanSoftwareSSH($server['ip'], 22, 'root', base64_decode($server['root_password']), $os); //Clean Installer Files		
						upgradeSoftwareSSH($server['ip'], 22, 'root', base64_decode($server['root_password']), $os); //Update Software
						
						//Disk Alert
						$hdd_total = getTotalDisk($server['ip'], 22, 'root', base64_decode($server['root_password']));
						$hdd_used = getUsedDisk($server['ip'], 22, 'root', base64_decode($server['root_password']));
						if(($hdd_used/$hdd_total) > 0.9){
							//Alert
							if($config['deliver_method'] == 'email'){
								$subject = $server['hostname'].' is it running out of free space.';
								$message = 'The server '.$server['hostname'].'['.$server['ip'].'] is it running out of free space. Only have '.($hdd_total-$hdd_used).'GB free. Date:'.date('d-m-Y g:i A');
								$header =  'From: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".
    							  		   'Reply-To: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".
    							   		   'X-Mailer: PHP/' . phpversion();
								mail($config['admin_email'], $subject, $message, $header);
							} elseif ($config['deliver_method'] == 'twilio'){
								include('lib/twilio/Services/Twilio.php');
								$msg = 'The server '.$server['hostname'].'['.$server['ip'].'] is it running out of free space. Only have '.($hdd_total-$hdd_used).'GB free. Date:'.date('d-m-Y g:i A');
								$client = new Services_Twilio($config['twilio_sid'], $config['twilio_token']);
								$client = $client->account->sms_messages->create('+'.$config['twilio_number'], '+'.$config['admin_phone'], $msg, array());
							}
						}
						
						//RAM Alert
						$ram_total = getTotalMemory($server['ip'], 22, 'root', base64_decode($server['root_password']));
						$ram_used = getUsedMemory($server['ip'], 22, 'root', base64_decode($server['root_password']));
						if(($ram_used/$ram_total) > 0.8){
							//Alert
							if($config['deliver_method'] == 'email'){
								$subject = $server['hostname'].' is it running out of RAM.';
								$message = 'The server '.$server['hostname'].'['.$server['ip'].'] is it running out of RAM. Only have 10% free. Date:'.date('d-m-Y g:i A');
								$header =  'From: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".
    							  		   'Reply-To: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".
    							   		   'X-Mailer: PHP/' . phpversion();
								mail($config['admin_email'], $subject, $message, $header);
							} elseif ($config['deliver_method'] == 'twilio'){
								include('lib/twilio/Services/Twilio.php');
								$msg = 'The server '.$server['hostname'].'['.$server['ip'].'] is it running out of RAM. Only have 10% free. Date:'.date('d-m-Y g:i A');
								$client = new Services_Twilio($config['twilio_sid'], $config['twilio_token']);
								$client = $client->account->sms_messages->create('+'.$config['twilio_number'], '+'.$config['admin_phone'], $msg, array());
							}
						}
						
						//CPU Alert
						$cpu = getCpuUsage($server['ip'], 22, 'root', base64_decode($server['root_password']));
						if($cpu > 0.8){
							//Alert
							if($config['deliver_method'] == 'email'){
								$subject = $server['hostname'].' are overloaded.';
								$message = 'The server '.$server['hostname'].'['.$server['ip'].'] have more than 80% CPU usage. Date:'.date('d-m-Y g:i A');
								$header =  'From: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".
    							  		   'Reply-To: no-reply@'.$_SERVER['SERVER_NAME']."\r\n".
    							   		   'X-Mailer: PHP/' . phpversion();
								mail($config['admin_email'], $subject, $message, $header);
							} elseif ($config['deliver_method'] == 'twilio'){
								include('lib/twilio/Services/Twilio.php');
								$msg = 'The server '.$server['hostname'].'['.$server['ip'].'] have more than 80% CPU usage. Date:'.date('d-m-Y g:i A');
								$client = new Services_Twilio($config['twilio_sid'], $config['twilio_token']);
								$client = $client->account->sms_messages->create('+'.$config['twilio_number'], '+'.$config['admin_phone'], $msg, array());
							}
							mail($config['admin_email'], $server['hostname'].' are overloaded.', 'The server '.$server['hostname'].'['.$server['ip'].'] have more than 80% CPU usage. Date:'.date('d-m-Y g:i A'));
						}
						
					}
				} 
			}
    }

	
	/**
	 * Performs migration of data from $current_version (the current installed version)
	 * to the given file set version
	 *
	 * @param string $current_version The current installed version of this plugin
	 * @param int $plugin_id The ID of the plugin being upgraded
	 */
	public function upgrade($current_version, $plugin_id) {
		// Upgrade if possible
		if (version_compare($this->getVersion(), $current_version, ">")) {
			//Nothing to Do
		}
	}
	
	/**
	 * Performs any necessary cleanup actions
	 *
	 * @param int $plugin_id The ID of the plugin being uninstalled
	 * @param boolean $last_instance True if $plugin_id is the last instance across all companies for this plugin, false otherwise
	 */
	public function uninstall($plugin_id, $last_instance) {
		Loader::loadModels($this, array("CronTasks"));
		$this->CronTasks->delete($this->CronTasks->getByKey('uptime_robot', 'uptime_robot'), $plugin_id);
		$this->CronTasks->deleteTaskRun($this->CronTasks->getTaskRunByKey('uptime_robot', 'uptime_robot'));
	}
	
	/**
	 * Returns all actions to be configured for this widget (invoked after install() or upgrade(), overwrites all existing actions)
	 *
	 * @return array A numerically indexed array containing:
	 * 	-action The action to register for
	 * 	-uri The URI to be invoked for the given action
	 * 	-name The name to represent the action (can be language definition)
	 */
	public function getActions() {
		return array(
			array(
				'action'=>"widget_staff_home",
				'uri'=>"widget/uptime_robot/admin_main/",
				'name'=>Language::_("UptimeRobotPlugin.name", true)
			)
		);
	}
}
?>