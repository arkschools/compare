<?php

namespace Ark\Compare\Core;

class CliApp extends CommandLineApplication {

	public function configure($argv) {

		// X ?[help]
		$this->options = array(
			'X' => 'terminate',			// -
			'?' => 'help',				// -
			'help' => 'help'
		);
	}


	public function welcome() {
		$boldYellow = array('bold', 'purple');
		$this->out("=======================================", "red");
		$this->out("=", "red");
		$this->out(array(
			"= " => 'red',
			"Welcome to Compare." => array('bold', 'green')
		));
		$this->out(array(
			"= " => 'red',
			"Type '?'"  => $boldYellow,
			" - for command options." => 'yellow'
		));
		$this->out("=", "red");
		$this->out("=======================================", "red");
	}

	public function help() {
		$boldYellow = array('bold', 'purple');
		$this->out(array( $this->padL("?", 6) => $boldYellow, " (help) - this help message" => 'yellow'));
		$this->out(array( $this->padL("X", 6) => $boldYellow, " (terminate)" => 'yellow'));

	}

}