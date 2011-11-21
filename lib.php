<?php

/**
 * Moodle repository plugin for http://e-ope.ee/repositoorium
 * @author Mart Mangus
 * @license GPL
 */

class repository_eope_repository extends repository {

    const apiurl = 'http://www.e-ope.ee/_download/euni_repository/api/';
    private $listing = array(
        'nologin' => true,
        'dynload' => true
    );

    public function __construct($repositoryid, $context = SITEID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
    }

    function get_file($url, $filename) {
        global $CFG;
        if (substr($filename, -4) != '.html')
            $filename .= '.html';
        $path = $this->prepare_file($filename);

        $processedurl = str_replace('+', '%20', $url); //urlencode()?
        $redirectcontent = file_get_contents($CFG->wwwroot . '/repository/eope_repository/repository-redirect.html');
        $redirectcontent = str_replace('{{URL}}', $processedurl, $redirectcontent);
        file_put_contents($path, $redirectcontent);
        return array('path'=>$path, 'url'=>$url);
    }

    /**
     * @arg $path
     * Possible paths:
     *   all_entries/<school_id>/<entry_id>
     *   my_entries/<entry_id>
     *   search/<search_string>/<entry_id>
     */
    public function get_listing($path='', $page='') {

        $this->listing['path'] = array(
            array('name' => get_string('repository', 'repository_eope_repository'),'path' => '')
        );

        if ($path == '') {
            $this->listing_start();
        } else {
            $paths = explode('/', $path);
            switch ($paths[0]) {
                case 'all_entries':
                    $this->listing_all($paths);
                    break;
                case 'my_entries':
                    $this->listing_my($paths);
                    break;              
                case 'search':
                    $this->listing_search($paths);
                    break;
                default:
                    throw new Exception("Error: Nothing available with this path: '$path'");
            }
        }
        return $this->get_current_listing();
    }

    private function listing_start() {
        $itemslist = array(
            array('title' => get_string('my_entries', 'repository_eope_repository'),
                'path' => 'my_entries',
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217',
                'children' => array()),
            array('title' => get_string('all_entries', 'repository_eope_repository'),
                'path' => 'all_entries',
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217',
                'children' => array())
        );
        $this->listing['list'] = $itemslist;
    }

    /**
     * @param $paths
     *   [1] -- school ID
     *   [2] -- entry ID
     */
    private function listing_all($paths) {
        $composedlist = array();
        $depth = count($paths);
        switch ($depth) {
            case 1:
                $encoded = file_get_contents(self::apiurl . 'list-schools');
                $schools = json_decode($encoded, true);
                $composedlist = $this->list_schools($schools);
                break;
            case 2:
                $encoded = file_get_contents(self::apiurl . 'school-entries?school_id=' . intval($paths[1]));
                $entries = json_decode($encoded, true);
                $composedlist = $this->list_entries($entries, 'all_entries/' . $paths[1] . '/');
                break;
            case 3:
                $composedlist = $this->list_files($paths[2]);
                break;
            default:
                throw new Exception('Error: This depth level is not defined (all): ' . $depth);
        }
        $this->listing['list'] = $composedlist;

        // Building path
        $this->listing['path'] []= 
            array('name' => get_string('all_entries', 'repository_eope_repository'), 'path' => 'all_entries');

        if ($depth > 1) {
            $encoded = file_get_contents(self::apiurl . 'get-school-name?school_id=' . intval($paths[1]));
            $schoolname = json_decode($encoded, true);
            $this->listing['path'] []= 
                array('name' => $schoolname, 'path' => 'all_entries/' . $paths[1]);
            if ($depth > 2)
            {
                $encoded = file_get_contents(self::apiurl . 'get-entry?entry_id=' . intval($paths[2]));
                $entry = json_decode($encoded, true);
                $this->listing['path'] []= 
                    array('name' => $this->get_title($entry), 'path' => 'all_entries/' . $paths[1] . '/' . $paths[2]);
            }
        }
    }


    /**
     * @param $paths
     *   [1] -- entry ID
     */
    private function listing_my($paths) {
        global $USER;
        $composedlist = array();
        $depth = count($paths);
        switch ($depth)
        {
            case 1:
                if (empty($USER->idnumber))
                    $composedlist = array();
                else {
                    $encoded = file_get_contents(self::apiurl . 'user-entries?IK=' . $USER->idnumber);
                    $entries = json_decode($encoded, true);
                    $composedlist = $this->list_entries($entries, 'my_entries/', true);
                }
                break;

            case 2:
                $composedlist = $this->list_files($paths[2]);
                break;

            default:
                throw new Exception('Error: This depth level is not defined (my): ' . $depth);
        }
        $this->listing['list'] = $composedlist;

        // Building path
        $this->listing['path'] []= 
            array('name' => get_string('my_entries', 'repository_eope_repository'), 'path' => 'my_entries');
        if ($depth > 1) {
            $encoded = file_get_contents(self::apiurl . 'get-entry?entry_id=' . intval($paths[1]));
            $entry = json_decode($encoded, true);
            $this->listing['path'] []= 
                array('name' => $this->get_title($entry, true), 'path' => 'my_entries/' . $paths[1]);
        }
    }

    /**
     * @param $paths
     *   [1] -- search string
     *   [2] -- entry ID
     */
    private function listing_search($paths) {

        $composedlist = array();
        $depth = count($paths);
        switch ($depth)
        {
            case 2:
                $encoded = file_get_contents(self::apiurl . 'search?text=' . $paths[1]);
                $entries = json_decode($encoded, true);
                $composedlist = $this->list_entries($entries, 'search/' . $paths[1] . '/');
                break;

            case 3:
                $composedlist = $this->list_files($paths[2]);
                break;

            default:
                throw new Exception('Error: This depth level is not defined (search): ' . $depth);
        }
        $this->listing['list'] = $composedlist;

        // Building path
        $this->listing['path'] []= 
            array('name' => get_string('search', 'repository_eope_repository') . ' "' . $paths[1] . '"',
                'path' => 'search/' . $paths[1]);
        if ($depth > 2) {
            $encoded = file_get_contents(self::apiurl . 'get-entry?entry_id=' . intval($paths[2]));
            $entry = json_decode($encoded, true);
            $this->listing['path'] []= 
                array('name' => $this->get_title($entry), 'path' => 'search/' . $paths[1] . '/' . $paths[2]);
        }
    }

    private function get_current_listing() {
        return $this->listing;
    }

    private function list_entries($entries, $path, $skipauthor = false)
    {
        $composedlist = array();
        foreach ($entries as $id => $entry) {
            $composedlist[] = array(
                'title' => $this->get_title($entry, $skipauthor),
                'path' => $path . $id,
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217',
                'children' => array()
            );
        }
        return $composedlist;
    }

    private function list_schools($schools)
    {
        $composedlist = array();
        foreach ($schools as $id => $schoolname) {
            $composedlist[] = array(
                'title' => $schoolname,
                'path' => 'all_entries/' . intval($id),
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217',
                'children' => array()
            );
        }
        return $composedlist;
    }

    private function list_files($entryid)
    {
        $composedlist = array();
        $encoded = file_get_contents(self::apiurl . 'entry-files?entry_id=' . intval($entryid));
        $files = json_decode($encoded, true);
        foreach ($files as $file) {
            $composedlist[] = array(
                'title' => $file['file_name'] . ' (' . $this->format_filesize($file['file_size']) . ') .html',
                'source' => $file['url'],
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Funknown-32&rev=217'
            );
        }
        return $composedlist;
    }

    private function get_title($entry, $skipauthor=false) {
        if (empty($entry['title']))
            return 'Error: Invalid Entry';
        $title = $entry['title'];
        if (!$skipauthor)
            $title .= ' (' .
                (count($entry['authors']) == 1
                    ? get_string('author', 'repository_eope_repository')
                    : get_string('authors', 'repository_eope_repository')
                ) . ': ' . implode(', ', $entry['authors']) . ')';
        return $title;
    }

    public function search($text) {
        $this->get_listing('search/' . $text);
        return $this->get_current_listing();
    }

    private function format_filesize($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 

    // will be called when installing a new plugin in admin panel
    /*
    public static function plugin_init() {
        $result = true;
        // do nothing
        return $result;
    }
    */
}
