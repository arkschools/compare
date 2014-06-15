<?php

namespace Ark\Compare\Core;

class Compare {

    protected $baseUrls = false;
    protected $resources = false;

    protected $filters = array();

    protected $retrievedContent = array();
    protected $report = array();

    public function __construct($configuration = array()) {

        foreach(array('base_urls', 'resources') as $collection) {
            if(isset($configuration[$collection])) {
                $methName = 'set' . Utility::toCamelCase($collection, true);
                $this->$methName($configuration[$collection]);
            }
        }

    }

    /**
     * Fetches the response from HTTP requests
     * for each resource under each base url
     *
     * @param callable|bool $notify
     * @param callable|bool $notifySection
     */
    public function fetch($notify = false, $notifySection = false) {

            if(!$notify) {
                $notify = function($url){};
            }

            if(!$notifySection) {
                $notifySection = function($msg){};
            }

            foreach ($this->getResources() as $resourceIndex => $resource) {
//                $this->report[$resourceIndex] = array();

                $resourceInfo = array();

                if (is_array($resource)) {
                    $resourceInfo = $resource[1];
                    $resource     = $resource[0];
                }

                foreach($this->getBaseUrls() as $baseIndex => $baseUrl) {
                    $url = $baseUrl . $resource;

                    set_error_handler(function () { throw new \RuntimeException(); });

                    try {
                        if (0 === count($resourceInfo)) {
                            // Easy, using GET

                            $context = null;
                        } else{
                            // Harder, using POST

                            $opts = array(
                                        'http' => array(
                                                    'method'  => 'POST',
                                                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                                                    'content' => http_build_query($resourceInfo)
                                                    )
                                    );

                            $context  = stream_context_create($opts);
                        }

                        $pageContents = file_get_contents($url, null, $context);

                        $pageContents = $this->filterContent($pageContents);
                    } catch (\RuntimeException $e) {
                        $pageContents = 'Error retrieving page - ' . rand(1000000, 10000000);
                    }

                    restore_error_handler();

                    $this->retrievedContent[$baseIndex] = $pageContents;

                    $notify($url);
                }

                if (count($this->retrievedContent) == 2) {
                    // Save the temporary files in case it runs out of memory

                    $temporaryFiles = array();

                    foreach($this->getBaseUrls() as $baseIndex => $baseUrl) {
                        $filename = sprintf(
                            '%s/comparisons/%s - %d - %s.htm',
                            getcwd(),
                            $resourceIndex,
                            $baseIndex,
                            Utility::OSSafeString($resource)
                        );

                        $pageContents = $this->retrievedContent[$baseIndex];

                        if (150 <= strlen($filename)) {
                            $filename = substr($filename, 0, 150) . '.htm';
                        }

                        $temporaryFiles[] = $filename;

                        file_put_contents($filename, $pageContents);
                    }

                    $percent = $this->getDiff(
                        $this->retrievedContent[0],
                        $this->retrievedContent[1]
                    );

                    foreach ($temporaryFiles as $temporaryFile) {
                        unlink($temporaryFile);
                    }

                    $msg = sprintf(
                        'Similarity of %s = %s',
                        $resourceIndex,
                        $percent
                    );

                    $notifySection($msg);

                    foreach($this->getBaseUrls() as $baseIndex => $baseUrl) {
                        $folder = sprintf(
                            '%s/comparisons/%s',
                            getcwd(),
                            Utility::OSSafeString($baseUrl)
                        );

                        if (!file_exists($folder)) {
                            mkdir($folder);
                        }

                        $filename = sprintf(
                            '/%s - %.2f - %s.htm',
                            $resourceIndex,
                            $percent,
                            Utility::OSSafeString($resource)
                        );

                        $pageContents = $this->retrievedContent[$baseIndex];

                        if (150 <= strlen($filename)) {
                            $filename = substr($filename, 0, 150) . '.htm';
                        }

                        file_put_contents($folder . $filename, $pageContents);
                    }
                }

                // filter known diffs

                // finished all base(s)
                // lets do string comparisons
//                foreach($this->report[$resourceIndex] as $baseIndexLocal => $contentData) {
//                    foreach(array_keys($this->report[$resourceIndex]) as $baseIndexCompare){
//                        if($baseIndexLocal != $baseIndexCompare){
//
//                            if(!isset($this->report[$resourceIndex][$baseIndexLocal]['similarity'])) {
//                                $this->report[$resourceIndex][$baseIndexLocal]['similarity'] = array();
//                            }
//                            $strLocal = $this->retrievedContent[$contentData['url']];
//                            $strCompare = $this->retrievedContent[$this->report[$resourceIndex][$baseIndexCompare]['url']];
//                            similar_text($strLocal, $strCompare, $percent);
//                            $this->report[$resourceIndex][$baseIndexLocal]['similarity'][$baseIndexCompare] = $percent;
//
//                        }
//                    }
//                }
            }

        //
        // complete
    }

    /**
     * @param callable $filter
     */
    public function addFilter($filter) {
        $this->filters[] = $filter;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function filterContent($content)
    {
        foreach($this->filters as $filter) {
            $content = $filter($content);
        }

        return $content;
    }

    /**
     * @param mixed $url (url to get cached / retrieved content for
     *
     * @return array specific url if provided or all if $url was false or not provided
     */
    public function getRetrievedContent($url = false) {
        return ($url) ? $this->retrievedContent[$url] : $this->retrievedContent;
    }

    /**
     * @return array
     */
    public function getReport() {
        return $this->report;
    }

    /**
     * @param $baseUrls
     */
    public function setBaseUrls ($baseUrls) {
        $this->baseUrls = $baseUrls;
    }

    /**
     * @return Array
     */
    public function getBaseUrls () {
        return $this->baseUrls;
    }

    /**
     * @param $resources Array
     */
    public function setResources ($resources) {
        $this->resources = $resources;
    }

    /**
     * @return Array
     */
    public function getResources () {
        return $this->resources;
    }

    public function getDiff($oldFile, $newFile)
    {
        $oldFileArray = explode("\n", str_replace("\r", '', $oldFile));

        $diff = $this->diff($oldFileArray, explode("\n", str_replace("\r", '', $newFile)));

        $totalChanged = 0;

        for ($i = 0; $i < count($diff); $i++) {
            if (is_array($diff[$i])) {
                $totalChanged += max(count($diff[$i]['d']), count($diff[$i]['i']));
            }
        }

        $totalChanged = min($totalChanged, count($oldFileArray));

        return 100 * (count($oldFileArray) - $totalChanged) / count($oldFileArray);
    }

    /*
        Paul's Simple Diff Algorithm v 0.1
        (C) Paul Butler 2007 <http://www.paulbutler.org/>
        May be used and distributed under the zlib/libpng license.

        This code is intended for learning purposes; it was written with short
        code taking priority over performance. It could be used in a practical
        application, but there are a few ways it could be optimized.

        Given two arrays, the function diff will return an array of the changes.
        I won't describe the format of the array, but it will be obvious
        if you use print_r() on the result of a diff on some test data.
    */

    private function diff($old, $new){
        $matrix = array();
        $maxlen = 0;

        foreach($old as $oindex => $ovalue){
            $nkeys = array_keys($new, $ovalue);
            foreach($nkeys as $nindex){
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if($matrix[$oindex][$nindex] > $maxlen){
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }

        if($maxlen == 0) {
            return array(array('d'=>$old, 'i'=>$new));
        }

        return array_merge(
            $this->diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            $this->diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
        );
    }
}