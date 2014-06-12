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
     */
    public function fetch($notify = false) {

            if(!$notify) { $notify = function($url){}; }

            foreach($this->getResources() as $resourceIndex => $resource) {

                $this->report[$resourceIndex] = array();
                foreach($this->getBaseUrls() as $baseIndex => $baseUrl) {

                    $url = $baseUrl . $resource;
                    if(is_string($resource)) {
                        $this->retrievedContent[$url] = (!isset($this->retrievedContent[$url]))
                                        ? file_get_contents($url) // no need for curl here
                                        : $this->retrievedContent[$url];
                    }else{
                        $this->retrievedContent[$url] = "complex HTTP request - skipping for now";
                    }

                    $this->retrievedContent[$url] = $this->filterContent($this->retrievedContent[$url]);

                    // update the report
                    $this->report[$resourceIndex][$baseIndex] = array(
                        'resource' => $resource,
                        'base' => $baseUrl,
                        'url' => $url,
                        'length' => strlen($this->retrievedContent[$url])
                    );

                    $notify($url);
                }

                // filter known diffs

                // finished all base(s)
                // lets do string comparisons
                foreach($this->report[$resourceIndex] as $baseIndexLocal => $contentData) {
                    foreach(array_keys($this->report[$resourceIndex]) as $baseIndexCompare){
                        if($baseIndexLocal != $baseIndexCompare){

                            if(!isset($this->report[$resourceIndex][$baseIndexLocal]['similarity'])) {
                                $this->report[$resourceIndex][$baseIndexLocal]['similarity'] = array();
                            }
                            $strLocal = $this->retrievedContent[$contentData['url']];
                            $strCompare = $this->retrievedContent[$this->report[$resourceIndex][$baseIndexCompare]['url']];
                            similar_text($strLocal, $strCompare, $percent);
                            $this->report[$resourceIndex][$baseIndexLocal]['similarity'][$baseIndexCompare] = $percent;

                        }
                    }
                }

            }

        //
        // complete
    }

    /**
     * @param func $filter
     */
    public function addFilter($filter) {
        $this->filters[] = $filter;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function filterContent($content) {

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
}