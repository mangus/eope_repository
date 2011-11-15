<?php

class repository_eope_repository extends repository {

    public function __construct($repositoryid, $context = SITEID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
    }

    function get_file($url, $filename) {
        global $CFG;
        if (substr($filename, -4) != '.html')
            $filename .= '.html';
        $path = $this->prepare_file($filename);
        $redirectcontent = file_get_contents($CFG->wwwroot.'/repository/eope_repository/repository-redirect.html');
        $redirectcontent = str_replace('{{URL}}', $url, $redirectcontent);
        file_put_contents($path, $redirectcontent);
        return array('path'=>$path, 'url'=>$url);
    }

    public function get_listing($path='', $page='') {
        global $USER;
        //$USER->email;

        /*
        if ($path == 'my_entries')
            die('korras!'); 
        */

        $itemslist = array(
            /*
            array('title'=>'Test File.zip .html',
                'thumbnail'=>'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=icon&rev=217&component=repository_eope_repository#TODO-FIX-THIS',
                'source'=>'http://e-ope.ee/_download/euni_repository/file/821/kameerika.zip'),
            */
            array('title'=>get_string('my_entries', 'repository_eope_repository'),
                'path'=>'my_entries',
                'thumbnail'=>'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217#TODO-FIX-THIS',
                'children'=>array()),
            array('title'=>get_string('all_entries', 'repository_eope_repository'),
                'path'=>'all_entries',
                'thumbnail'=>'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217#TODO-FIX-THIS',
                'children'=>array())
        );

        $list = array(
            'nologin'=>true,
            'dynload'=>true,
             //this will be used to build navigation bar
            'path'=>array(
                array('name'=>get_string('repository', 'repository_eope_repository'),'path'=>'/')
            ),
            'list'=> $itemslist
        );
        return $list;
    }

    /* TODO

    public function search($text) {
        $search_result = array();
        // search result listing's format is the same as 
        // file listing
        $search_result['list'] = array();
        return $search_result;
    }


    public function search($search_text) {
        $space = optional_param('space', 'workspace://SpacesStore', PARAM_RAW);
        $currentStore = $this->user_session->getStoreFromString($space);
        $nodes = $this->user_session->query($currentStore, $search_text);
        $ret = array();
        $ret['list'] = array();
        foreach($nodes as $v) {
            $ret['list'][] = array('title'=>$v->cm_name, 'source'=>$v->id);
        }
        return $ret;
    }
    */

    // will be called when installing a new plugin in admin panel
    /*
    public static function plugin_init() {
        $result = true;
        // do nothing
        return $result;
    }
    */
}
