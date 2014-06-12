<?php

namespace Ark\Compare\Core;

abstract class CommandLineApplication {

	protected $action;
	protected $args = array();

	protected $options = array();

	/*
		Black       0;30     Dark Gray     1;30
		Blue        0;34     Light Blue    1;34
		Green       0;32     Light Green   1;32
		Cyan        0;36     Light Cyan    1;36
		Red         0;31     Light Red     1;31
		Purple      0;35     Light Purple  1;35
		Brown       0;33     Yellow        1;33
		Light Gray  0;37     White         1;37
	*/
	public $colors = array(
		'reset' 	=> "0",
		'bold' 		=> "1",
		'red' 		=> "31",
		'green' 	=> "32",
		'yellow' 	=> "33",
		'blue' 		=> "34",
		'purple' 	=> "35",
		'cyan' 		=> "36",
		'white' 	=> "37"
	);

	public function __construct($argv = array()) {
		// $argv can be used here for intialisations params
		$this->configure($argv);
		$this->welcome();
	}

	public function welcome() {}

	private function setParams($command) {
		$args = explode(' ', $command);
		$this->action = $args[0];
		if(count($args) > 1){
			$this->args = array_splice($args, 1);
		}else {
			$this->args = array();
		}
		return $this;
	}

	// convenience
	protected function padL($str, $len=100){ return str_pad($str, $len, ' ', STR_PAD_LEFT); }
	protected function padR($str, $len=100){ return str_pad($str, $len, ' ', STR_PAD_RIGHT); }

	public function switchColor($xColSelection = 'reset') {
		$cliApp = $this;
		$encoded = array_map(function($strCode) use ($cliApp) {
			return (isset($cliApp->colors[$strCode])) ? $cliApp->colors[$strCode] :  null;
		}, (!is_array($xColSelection)) ? array($xColSelection) : $xColSelection);
		return sprintf( "\033[%sm", implode( ';', $encoded) );
	}

    public function error($message) {
        $this->out($message, array('bold', 'red'));
    }

	public function out($message, $color = "reset") {
		if(is_array($message)) {
			$colorizedMssg = "";
			foreach($message as $mssg => $xcol) {
				$colorizedMssg .= $this->switchColor($xcol) . $mssg;
			}
			$message = $colorizedMssg;
		}
		print 	$this->switchColor($color)
				. $this->padR($message, 100)
				. $this->switchColor("reset")
				. PHP_EOL;
	}

	protected function goCommand() {
		$k = strtoupper($this->action);
		$bHasCommandInOptions = isset($this->options[$k]);
		$methodName = $this->options[$k];
		if($bHasCommandInOptions || method_exists($this, $methodName)) {
			switch(count($this->args)) {
				case 1:
					return $this->$methodName($this->args[0]);
					break;
				case 2:
					return $this->$methodName($this->args[0], $this->args[1]);
					break;
				case 3:
					return $this->$methodName($this->args[0], $this->args[1], $this->args[2]);
					break;
				case 4:
					return $this->$methodName($this->args[0], $this->args[1], $this->args[2], $this->args[3]);
					break;
				default:
					return $this->$methodName();
					break;
			}
		}else {
			$this->help();
			throw new \Exception(sprintf("Unknown Command '%s'", $this->action));
		}
		return false;
	}

	protected function getNextCommand() {
		echo "> ";
		return trim(fgets(fopen ("php://stdin","r")));
	}

	// For use in testing suite
	//
	public function runCommand($command) {
		$this->setParams($command)->goCommand();
	}

	public function run() {
		$bContinue = true;
		while ($bContinue !== false) {
			try {
				$this->setParams($this->getNextCommand());
				$bContinue = $this->goCommand();
			} catch (\Exception $e) {
				print "ERROR:: " . $e->getMessage() . PHP_EOL;
			}
		}
	}

	public function terminate(){
		$this->out('bye!', array('bold', 'green'));
		return false;
	}

	// Abstractions for children
	//
	abstract protected function configure($argv);
	abstract protected function help();

}
