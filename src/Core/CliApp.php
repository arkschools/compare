<?php

namespace Ark\Compare\Core;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class CliApp extends CommandLineApplication {

    protected $configPath = 'configs/base.yml';
    protected $configs = array();

    /**
     * @var Compare
     */
    protected $objCompare;

	public function configure($argv) {
        // Uppercase keys please
		$this->options = array(
            'X'    => 'terminate',
            'C'    => 'run_comparison',
            //'R'    => 'report',
            '?'    => 'help',
            'help' => 'help',
		);

        try {
            $this->configs = Yaml::parse(file_get_contents($this->configPath));

            if(!isset($this->configs['base_urls']) || count($this->configs['base_urls']) < 2) {
                throw new ParseException("Not enough base paths");
            }

            if(!isset($this->configs['resources']) || count($this->configs['resources']) < 1) {
                throw new ParseException("No resources configured");
            }
        } catch (ParseException $e) {
            $this->error(sprintf("ERROR [%s] : %s", $this->configPath, $e->getMessage()));

            return $this->terminate();
        }

        //print_r($this->configs);

        $this->objCompare = new Compare($this->configs);
	}


    /**
     * Run the comparison
     *  - Will involk the compare, set up filters and print a report
     */
    public function run_comparison($skipFilters = false) {
        $cliTerm = $this;

        $notifyCallback = function($url) use ($cliTerm) {
            $cliTerm->out(sprintf("Completed %s", $url), array('green', 'bold'));
        };

        $notifySectionCallback = function($msg) use ($cliTerm) {
            $cliTerm->out($msg, array('green', 'bold'));
        };

        try {
            if($skipFilters != "nofilter"){
                // Old textarea for debug
                $this->objCompare->addFilter(Utility::cutoutFilterFactory("<br />\n	<textarea name='debugInfo'", '</textarea>' . "\n\n", $cliTerm));

                // Symfony debug bar
                $this->objCompare->addFilter(Utility::cutoutFilterFactory("\n<div id=\"sfwdt", "</script>", $cliTerm));
                $this->objCompare->addFilter(Utility::cutoutFilterFactory("<script>/*<![CDATA[*/    (function (".") {                Sfjs.load(", '</script>' . "\n", $cliTerm));

                // CCR! changes
                $this->objCompare->addFilter(Utility::cutoutFilterFactory('The code execution started', '<br />' . "\n", $cliTerm));
                $this->objCompare->addFilter(Utility::cutoutFilterFactory('This page was generated ', 'seconds.', $cliTerm));
                $this->objCompare->addFilter(Utility::cutoutFilterFactory('<link rel="icon" type="image/vnd.microsoft.icon"', 'favicon.ico" />', $cliTerm));

                $regexps = $this->configs['normalise']['regexp'];

                foreach($regexps as $regexp) {
                    $this->objCompare->addFilter(Utility::replaceFilterFactory($regexp[0], $regexp[1], $cliTerm));
                }
            }else{
                $this->out('Filters have been turned off.', array('yellow'));
            }

            $this->objCompare->fetch($notifyCallback, $notifySectionCallback);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

    }

    public function report() {
        $report = $this->objCompare->getReport();
        foreach($report as $res => $resData) {
            $this->out(sprintf("#%s [ %s ]", $res, $resData[0]['resource']), array('purple') );
            foreach($resData as $base => $baseData) {

                $deltas = array();
                foreach($baseData['similarity'] as $b => $p){
                    $deltas[] = sprintf("(@%s: %s", $b, $p) . "%)";
                }
                $formatted = sprintf("+ #%s  [%s] : %d chars <deltas: %s>", $base, $baseData['base'], $baseData['length'], implode(", ", $deltas));
                $this->out( $formatted, array('yellow') );
            }
        }
    }


	public function welcome() {
//		$boldYellow = array('bold', 'purple');
//		$this->out("=======================================", "red");
//		$this->out("=", "red");
//        $this->out(array(
//                "= " => 'red',
//                "Welcome to Compare." => array('bold', 'green')
//            ));
//
//        $configSummary = sprintf("(Imported configs for %d base urls and %d resources)",
//            count($this->configs['base_urls']),
//            count($this->configs['resources'])
//        );
//        $this->out(array(
//                "= " => 'red',
//                $configSummary => array('bold', 'green')
//            ));
//
//		$this->out(array(
//			"= " => 'red',
//			"Type '?'"  => $boldYellow,
//			" - for command options." => 'yellow'
//		));
//		$this->out("=", "red");
//		$this->out("=======================================", "red");

        $this->run_comparison();
        exit(0);
	}

	public function help() {
		$boldYellow = array('bold', 'purple');
        $this->out(array( $this->padL("?", 6) => $boldYellow, " (help) - this help message" => 'yellow'));
        $this->out(array( $this->padL("c", 6) => $boldYellow, " (Run Comparison) - Runs comparisons (make take a while)" . $this->configPath => 'yellow'));
        //$this->out(array( $this->padL("r", 6) => $boldYellow, " (Report) - Show a pretty report" . $this->configPath => 'yellow'));
		$this->out(array( $this->padL("X", 6) => $boldYellow, " (terminate)" => 'yellow'));
	}

}