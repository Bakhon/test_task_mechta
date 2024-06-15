<?php

namespace vBulletin\Search;

class Search {
    private $db;
    private $searchTable = 'vb_searchresult';
    private $vbpostTable = 'vb_post';
    private $forumIdToExclude = 5;
    private $render;
    
    public function __construct() {
        $this->db = new \PDO('mysql:dbname=vbforum;host=127.0.0.1', 'forum', '123456'); // можно создать отдельный класс и вынести подключение к бд отдельно
        $this->render = new Render();
    }

    public function doSearch(): void {
        if (isset($_REQUEST['searchid'])) {
            $_REQUEST['do'] = 'showresults';
        } elseif (!empty($_REQUEST['q'])) {
            $_REQUEST['do'] = 'process';
            $_REQUEST['query'] = &$_REQUEST['q'];
        }

        if ($_REQUEST['do'] == 'process') {
            try {
                $result = $this->executeSearch($_REQUEST['query']);
                $this->renderSearchResults($result);
                $this->logSearchQuery($_REQUEST['query']);
            } catch (\PDOException $e) {
                echo "Error executing search query: " . $e->getMessage();
            }
           
        } elseif ($_REQUEST['do'] == 'showresults') {
            try {
                $result = $this->getSearchResults($_REQUEST['searchid']);
                $this->renderSearchResults($result);
            } catch (\PDOException $e) {
                echo "Error executing search query: " . $e->getMessage();
            }  
        } else {
            echo "<h2>Search in forum</h2><form><input name='q'></form>";
        }
    }

    private function executeSearch($query) {
        $sth = $this->db->prepare("SELECT * FROM {$this->searchTable} WHERE text like ?");
        $sth->execute([$query]);
        return $sth->fetchAll();
    }

    private function getSearchResults($searchId) {
        $sth = $this->db->prepare("SELECT * FROM {$this->vbpostTable} WHERE searchid = ?");
        $sth->execute([$searchId]);
        return $sth->fetchAll();
    }

    private function renderSearchResults($result) {
        foreach ($result as $row) {
            if ($row['forumid'] != $this->forumIdToExclude) {
                $this->render->renderSearchResult($row);
            }
        }
    }

    private function logSearchQuery($query) {
        $file = fopen('/var/www/search_log.txt', 'a+');
        fwrite($file, $query . "\n");
    }
}