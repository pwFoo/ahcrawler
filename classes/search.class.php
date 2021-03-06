<?php

require_once 'crawler-base.class.php';

/**
 * ____________________________________________________________________________
 *          __    ______                    __             
 *   ____ _/ /_  / ____/________ __      __/ /__  _____    
 *  / __ `/ __ \/ /   / ___/ __ `/ | /| / / / _ \/ ___/    
 * / /_/ / / / / /___/ /  / /_/ /| |/ |/ / /  __/ /        
 * \__,_/_/ /_/\____/_/   \__,_/ |__/|__/_/\___/_/         
 * ____________________________________________________________________________ 
 * Free software and OpenSource * GNU GPL 3
 * DOCS https://www.axel-hahn.de/docs/ahcrawler/index.htm
 * 
 * THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE <br>
 * LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR <br>
 * OTHER PARTIES PROVIDE THE PROGRAM ?AS IS? WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * 
 * ----------------------------------------------------------------------------
 * SEARCH
 * 
 * usage:
 * require_once("../search.class.php");
 * 
 * */
class ahsearch extends crawler_base {

    // ----------------------------------------------------------------------
    // searchresults
    // ----------------------------------------------------------------------
    /*
    private $_aRankCounter = array(
        'matchWord' => array(
            'title' => 50,
            'keywords' => 50,
            'description' => 50,
            'url' => 500,
            'content' => 5,
        ),
        'WordStart' => array(
            'title' => 20,
            'keywords' => 20,
            'description' => 20,
            'url' => 30,
            'content' => 3,
        ),
        'any' => array(
            'title' => 2,
            'keywords' => 2,
            'description' => 2,
            'url' => 5,
            'content' => 1,
        ),
    );
     */
    private $_aRankCounter = array();
    
    private $_aFormNames = array(
        'language'=>'lang',
        'category'=>'subdir',
    );

    // ----------------------------------------------------------------------
    /**
     * new crawler
     * @param integer  $iSiteId  site-id of search index
     */
    public function __construct($iSiteId = false) {
        
        $aOptions=$this->getEffectiveOptions();
        $this->_aRankCounter = $aOptions['searchindex']['rankingWeights'];

        $this->setSiteId($iSiteId);
        $this->setLangFrontend();
        return true;
    }

    public function getQueryValue($sKey){
        $aSource=(isset($_POST) && is_array($_POST) && count($_POST))
                ? $_POST
                : ((isset($_GET) && is_array($_GET) && count($_GET))
                    ? $_GET
                    : false
                )
                ;
        if(!$aSource){
            return false;
        }
        if(!isset($aSource[$sKey]) || !$aSource[$sKey]){
            return false;
        }
        
        // TODO: clean value
        return $aSource[$sKey];
    }
    
    /**
     * get categories to search in ... it returns the structure from config 
     * below profiles -> [id] -> searchcategories
     * @return array
     */
    public function getSearchCategories($bAddNone=false) {
        $aReturn=array();
        if (!isset($this->aProfileSaved['frontend']['searchcategories']) || !count($this->aProfileSaved['frontend']['searchcategories'])) {
            return false;
        }
        if($bAddNone){
            $aReturn[$this->lF('label.searchsubdir-none')]='';
        }
        return array_merge($aReturn, $this->aProfileSaved['frontend']['searchcategories']);
    }
    /**
     * get categories to search in ... it returns the structure from config 
     * below profiles -> [id] -> searchlang
     * @return array
     */
    public function getSearchLang($bAddNone=false) {
        $aReturn=array();
        if (!isset($this->aProfileSaved['frontend']['searchlang']) || !count($this->aProfileSaved['frontend']['searchlang'])) {
            return false;
        }
        if($bAddNone){
            $aReturn[$this->lF('label.searchlang-none')]='';
        }
        foreach ($this->aProfileSaved['frontend']['searchlang'] as $sMyLang){
            $aReturn[$sMyLang]=$sMyLang;
        }
        return $aReturn;
    }
    
    public function getSearchtermsOfUsers(){
            $sQuery=''
                    . 'SELECT query, count(query) as count, results '
                    . 'FROM searches '
                    . 'WHERE siteid = '.$this->_sTab.' '
                    . 'AND ts > \''.date("Y-m-d H:i:s", (date("U") - (60 * 60 * 24 * $iDays))).'\' '
                    . 'GROUP BY query '
                    . 'ORDER BY count desc, query asc '
                    . 'LIMIT 0,10';
            $oResult=$this->oDB->query($sQuery);
            
            /*
             * TODO: FIX ME
            $oResult = $this->oDB->select(
                    'searches', 
                    array('ts', 'query', 'count(query) as count', 'results'),
                    array(
                        'AND' => array(
                            'siteid' => $this->_sTab,
                            '[>]ts' => date("Y-m-d H:i:s", (date("U") - (60 * 60 * 24 * $iDays))),
                        ),
                        "GROUP" => "query",
                        "ORDER" => array("count"=>"DESC", "query"=>"asc"),
                        "LIMIT" => 10
                    )
            );
             */
            
            // echo "$sQuery ".($oResult ? "OK" : "fail")."<br>";
            $aSearches[$iDays]=($oResult ? $oResult->fetchAll(PDO::FETCH_ASSOC) : array());
    }
            
            
    // ----------------------------------------------------------------------
    // ACTIONS SEARCH
    // ----------------------------------------------------------------------

    /**
     * create a search phrase for WHERE clause
     * This method replaces % with [%]
     * 
     * @param string   $sTerm  search term
     * @param boolean  $bLike  surround % for search with like (for Medoo)
     * @return type
     */
    private function _replaceSearchterm4Sql($sTerm, $bLike=true){
        $sReturn=$sTerm;
        if(strpos($sReturn, '%')!==false){
            $sReturn=str_replace('%', '/%', $sTerm);
            // for Medoo: if the search term contains a % then it
            // does not surround the searchterm with % on using like
            $sReturn=$bLike ? '%'.$sReturn.'%' : $sReturn;
        }
        
        return $sReturn;
    }
    
    /**
     * do search through pages
     * @param string  $q          search string
     * @param type $aOptions
     *   url    {string}  limit url i.e. //[domain]/[path] - without "%"
     *   subdir {string}  => subset of search without domain with starting slash (/[path])
     *   mode   {string}  one of AND | OR | PHRASE (default: OR)
     *   lang   {string}  force language of the document; default: all
     * @param string  $aOptions     options
     * @return array
     */
    public function search($q, $aOptions = array()) {
        if (!$this->iSiteId) {
            // echo "ABORT - keine this->iSiteId = ".$this->iSiteId."<br>";
            return false;
        }
        // $this->_scanKeywords();
        // echo "DEBUG: q = $q<br>\n<pre>".print_r($aOptions, 1)."</pre><br>\n";
        if (!$q) {
            return false;
        }

        if (!is_array($aOptions)) {
            $aOptions = array();
        }
        
        // get GET / POST values from a sent form
        foreach(array('subdir', 'lang', 'mode') as $sOptionKey){
            $sVal=$this->getQueryValue($sOptionKey);
            if ($sVal){
                $aOptions[$sOptionKey]=$sVal;
            }
        }

        // echo '<pre>'.print_r($aOptions,1).'</pre>';
        if (!array_key_exists('url', $aOptions)){
            $aOptions['url']='//'.parse_url($this->aProfileSaved['searchindex']['urls2crawl'][0], PHP_URL_HOST);
        }
        
        if (array_key_exists('subdir', $aOptions)){
            $aOptions['url'].=str_replace('//','/','/'.$aOptions['subdir']);
        }
        
        if (!array_key_exists('mode', $aOptions)) {
            $aOptions['mode'] = 'AND';
        }
        
        // --- prepare search options
        if (!array_key_exists('url', $aOptions)) {
            $aOptions['url'] = '';
        } else {
            // remove '%' ... it is added below in 'url[~]' => $aOptions['url'],
            $aOptions['url'] = str_replace('%', '', $aOptions['url']);
        }
        $sPhrase=$this->_replaceSearchterm4Sql($q);
        if($aOptions['mode']==='PHRASE'){
            $aQuery['OR'] = array(
                'title[~]' => $sPhrase,
                'description[~]' =>$sPhrase,
                'keywords[~]' => $sPhrase,
                'url[~]' => $sPhrase,
                'content[~]' => $sPhrase,
            );
            $aOptions['mode']='AND';
        } else {
            foreach (explode(" ", $q) as $sWord) {
                $sPhrase=$this->_replaceSearchterm4Sql($sWord);
                $aQuery['OR # query for ['.$sWord.']'] = array(
                    'title[~]' => $sPhrase,
                    'description[~]' => $sPhrase,
                    'keywords[~]' => $sPhrase,
                    'url[~]' => $sPhrase,
                    'content[~]' => $sPhrase,
                );
            }
        }
        // print_r($aOptions);echo "<hr>";
        $aSelect=array(
            'siteid' => $this->iSiteId,
            'errorcount' => 0,
            'url[~]' => $aOptions['url'],
            $aOptions['mode'] => $aQuery,
        );
        
        if (isset($aOptions['lang']) && $aOptions['lang']){
            $aSelect['lang']=$aOptions['lang'];
        }
        
        $aDbitems = $this->oDB->select(
                'pages', 
                array('id', 'url', 'lang', 'title', 'description', 'keywords', 'content', 'ts'), 
                array(
                    'AND' => $aSelect,
                    // LIMIT on db can miss best ranked items 
                    // 'LIMIT' => 55
                )
        );
        // echo "DEBUG <pre>" . print_r($aQuery, 1) ."</pre><br>";
        // echo 'DEBUG: ' . $this->oDB->last() . '<br>';
        if (is_array($aDbitems) && count($aDbitems)) {
            $aResult = $this->_reorderByRanking($aDbitems, $q);
            /*
            while(count($aResult)>55){
                array_pop($aResult);
            } 
            */
        }
        // echo "DEBUG ".__METHOD__."() ".count($aResult)."<br>";
        return $aResult;
    }

    /**
     * get valid keywords by last word of a searchstring ordered by count of results
     * @param string  $q  search string
     * @return array
     */
    public function searchKeyword($q) {
        if (!$this->iSiteId) {
            return false;
        }
        $aTmp = explode(" ", $q);
        $sWord = array_pop($aTmp);
        if (!$sWord) {
            return false;
        }
        $aQuery = array(
            'OR' => array(
                'word' => $sWord,
                'word[~]' => $sWord,
            )
        );
        $aResult = $this->oDB->select(
                'words', array('word', 'count'), array(
            'AND' => array(
                'siteid' => $this->iSiteId,
                'AND' => $aQuery,
            ),
            "order" => 'count',
            "LIMIT" => 11
                )
        );
        // echo $this->oDB->last_query() . "\n";
        // print_r($aResult);
        return $aResult;
    }

    /**
     * get valid titles by word of a searchstring
     * @param string  $q  search string
     * @return array
     */
    public function searchTitle($q) {
        if (!$this->iSiteId) {
            return false;
        }
        $aQuery = array(
            'OR' => array(
                'title[~]' => $q,
            )
        );
        $aResult = $this->oDB->select(
                'pages', array('title', 'url'), array(
            'AND' => array(
                'siteid' => $this->iSiteId,
                'AND' => $aQuery,
            ),
            "order" => 'title',
            "LIMIT" => 11
                )
        );
        // echo $this->oDB->last_query() . "\n";
        // print_r($aResult);
        return $aResult;
    }

    /**
     * get array with hit counts of different type
     * @param string $sNeedle
     * @param string $sHaystack
     * @return array
     */
    private function _countHits($sNeedle, $sHaystack) {

        $iMatchWord=0;
        $iWordStart=0;
        
        // ----- matching word
        $a1=array();
        
        // detect a searchterm within the text
        preg_match_all('/\W' . $sNeedle . '\W/i', $sHaystack, $a1);
        $iMatchWord += is_array($a1) ? count($a1[0]) : 0;
        
        // detect a searchterm at the end of the text
        preg_match_all('/\W' . $sNeedle . '$/i', $sHaystack, $a1);
        $iMatchWord += is_array($a1) ? count($a1[0]) : 0;

        // detect a searchterm on start of the text
        preg_match_all('/^' . $sNeedle . '\W/i', $sHaystack, $a1);
        $iMatchWord += is_array($a1) ? count($a1[0]) : 0;

        // detect a searchterm as complete text
        preg_match_all('/^' . $sNeedle . '$/i', $sHaystack, $a1);
        $iMatchWord += is_array($a1) ? count($a1[0]) : 0;

        // ----- word start
        $a2=array();

        // detect searchterm as word start 
        preg_match_all('/\W' . $sNeedle . '/i', $sHaystack, $a2);
        $iWordStart += is_array($a2) ? count($a2[0]) : 0;

        // detect searchterm on start of text
        preg_match_all('/^' . $sNeedle . '\W/i', $sHaystack, $a1);
        $iWordStart += is_array($a1) ? count($a1[0]) : 0;

        // ----- any hit
        preg_match_all('/' . $sNeedle . '/i', $sHaystack, $a3);

        return array(
            'matchWord' => $iMatchWord,
            'WordStart' => $iWordStart,
            'any' => is_array($a3) ? count($a3[0]) : 0,
        );
    }

    /**
     * reorder search result by getting weight and ranking; ordered by most
     * relevant item
     * @param array   $aData  searchresult from $this->search()
     * @param string  $q      search query
     * @return array
     */
    private function _reorderByRanking($aData, $q) {
        $aReturn = array();
        if (!is_array($aData) || !count($aData)) {
            return $aReturn;
        }
        $aSearchwords = explode(" ", $q);
        foreach ($aData as $aItem) {
            $iCount = 0;
            $sUrl = $aItem['url'];

            // TODO: customize replacement
            $aItem['url'] = basename($aItem['url']);
            $aItem['url'] = str_replace('id_', '', $aItem['url']);
            $aItem['url'] = str_replace('.html', '', $aItem['url']);
            $aItem['url'] = str_replace('.php', '', $aItem['url']);
            // echo '['.$aItem['url']."]<br>";
            $aResults = array();
            foreach ($aSearchwords as $sWord) {

                $sWordRegex= preg_replace('/([^a-zA-Z0-9])/', '\\\$1', $sWord);
                // in den einzelnen Spalten nach Anzahl Vorkommen des
                // Wortes (Übereinstimmung, am Anfang, irgendwo) suchen und 
                // deren Anzahl Treffer mit dem Ranking-Faktor multiplizieren 
                foreach (array('title', 'description', 'keywords', 'url', 'content') as $sCol) {
                    // echo "DEBUG: $sWord ... $sWordRegex ... in ".$aItem[$sCol]."<br>";
                    foreach ($this->_countHits($sWordRegex, $aItem[$sCol]) as $sKey => $iHits) {
                        $iCount+=$iHits * $this->_aRankCounter[$sKey][$sCol];
                        $aResults[$sWord][$sKey][$sCol] = array($iHits, $this->_aRankCounter[$sKey][$sCol]);
                    }
                }
            }
            $aItem['url'] = $sUrl;
            $aItem['results'] = $aResults;
            $aReturn[$iCount][] = $aItem;
        }
        if (count($aReturn)) {
            krsort($aReturn);
        }
        return $aReturn;
    }

    public function getCountOfSearchresults($aResult) {
        $iCounter = 0;
        if (!is_array($aResult)) {
            return false;
        }
        foreach ($aResult as $iRanking => $aDataItems) {
            $aRow = array();
            foreach ($aDataItems as $aItem) {
                // unset($aItem['content']);
                // echo '<pre>'.print_r($aItem, 1); die();
                $iCounter ++;
            }
        }
        return $iCounter;
    }

    // ----------------------------------------------------------------------
    // render functions to display search form
    // ----------------------------------------------------------------------
    
        /**
         * generate attributes for html tags with a given kay value hash
         * @param array $aAttributes  attributes as key=>value items
         * @return string
         */
        protected function _addAttributes($aAttributes){
            $sReturn='';
            foreach($aAttributes as $sAttr=>$sValue){
                $sReturn.=' '.$sAttr.'="'.$sValue.'"';
            }
            return $sReturn;
        }
        protected function _getSelectId($sKeyword){
            return 'select'.$sKeyword;
        }

        protected function _renderLabel($sKeyword, $aAttributes=array()){
            if(!isset($aAttributes['for'])){
                $aAttributes['for']=$this->_getSelectId($sKeyword);
            }
            return '<label'
                . $this->_addAttributes($aAttributes)
                . '>'. $this->lF('label.search'.$sKeyword) .'</label>'
                ;
        }
        /**
         * return html code for a select form field 
         * 
         * @param array  $aOptions     array with key = visible label; value= value in option
         * @param string $sName        name attribute for select field
        *  @param array  $aAttributes  optional: html attributes for select tag
         * @param string $sSelected    value of item to select
         * @return string
         */
        protected function _renderSelect($aOptions, $sName, $aAttributes=array(), $sSelected=false){
            $sReturn='';
            if ($aOptions){
                if(!isset($aAttributes['id'])){
                    $aAttributes['id']=$this->_getSelectId($sName);
                }
                $aAttributes['name']=$sName;
                if(!$sSelected){
                    $sSelected=$this->getQueryValue($sName);
                }
                foreach ($aOptions as $sLabel=>$sValue){
                    $sReturn.='<option value="'.$sValue.'"'.($sSelected===$sValue?' selected="selected"':'').'>'.$sLabel.'</option>';
                }
                $sReturn='<select' . $this->_addAttributes($aAttributes) . '>'.$sReturn.'</select>';
            }
            return $sReturn;
        }
        
    /**
     * get html code to add site id (project) and frontend language
     * @since v0.98
     * @return string
     */
    public function renderHiddenfields(){
        return '<input'
            . $this->_addAttributes(array(
                'type'=>'hidden',
                'name'=>'siteid',
                'value'=>$this->iSiteId,
            ))
            .'>'
            . '<input'
            . $this->_addAttributes(array(
                'type'=>'hidden',
                'name'=>'lang',
                'value'=>$this->sLang,
            ))
            .'>'
            ;
    }

    /**
     * get html code for category selection label
     * @param array  $aAttributes  optional: html attributes for input tag
     * @return string
     */
    public function renderInput($aAttributes=array()){
        return '<input'
            . $this->_addAttributes(array_merge(array(
                'type'=>'text',
                'name'=>'q',
                'id'=>'searchterm',
                'value'=>htmlentities($this->getQueryValue('q')),
                'placeholder'=>$this->lF('input.search.placeholder'),
                'title'=>$this->lF('input.search.title'),
                'pattern'=>'^..*',
                'required'=>'required',
            ),$aAttributes))
            .'>';
    }
    /**
     * get html code for category selection label
     * @return string
     */
    public function renderLabelCategories($aAttributes=array()){
        return $this->_renderLabel('subdir', $aAttributes);
    }
    /**
     * get html code for lang selection label
     * @return string
     */
    public function renderLabelLang($aAttributes=array()){
        return $this->_renderLabel('lang', $aAttributes);
    }
    /**
     * get html code for mode selection label
     * @return string
     */
    public function renderLabelMode($aAttributes=array()){
        return $this->_renderLabel('mode', $aAttributes);
    }
    /**
     * get html code for searchterm label
     * @return string
     */
    public function renderLabelSearch($aAttributes=array()){
        if(!isset($aAttributes['for'])){
            $aAttributes['for']='searchterm';
        }
        return $this->_renderLabel('term', $aAttributes);
    }

    
    /**
     * get html code for category selection 
     * @return string
     */
    public function renderSelectCategories($aAttributes=array()){
        return $this->_renderSelect($this->getSearchCategories(true), 'subdir', $aAttributes);
    }
    /**
     * get html code for language selection 
     * @return string
     */
    public function renderSelectLang($aAttributes=array()){
        return $this->_renderSelect($this->getSearchLang(true), 'lang', $aAttributes);
    }
    /**
     * get html code for mode selection 
     * @return string
     */
    public function renderSelectMode($aAttributes=array()){
        return $this->_renderSelect(array(
            $this->lF('label.searchmode-and')=>'AND',
            $this->lF('label.searchmode-or')=>'OR',
            $this->lF('label.searchmode-phrase')=>'PHRASE',
        ), 'mode', $aAttributes);
    }

    /**
     * get htmlcode for a simple or extended search form
     * 
     * echo $o->renderSearchForm();
     * 
     * // with additional options
     * echo $o->renderSearchForm(array(
     *     'categories'=>1,
     *     'lang'=>1,
     *     'mode'=>1,
     * ));

     * @param type $aOptions
     * @return string
     */
    public function renderSearchForm($aOptions=array()){
        $sOptions=(isset($aOptions['categories']) && $aOptions['categories'] 
                ? '<tr><td>'.$this->renderLabelCategories() .'</td><td>'. $this->renderSelectCategories().'</td></tr>'
                : '')
            .(isset($aOptions['lang']) && $aOptions['lang'] 
                ? '<tr><td>'.$this->renderLabelLang() .'</td><td>'. $this->renderSelectLang().'</td></tr>'
                : '')
            .(isset($aOptions['mode']) && $aOptions['mode'] 
                ? '<tr><td>'.$this->renderLabelMode() .'</td><td>'. $this->renderSelectMode().'</td></tr>'
                : '')
            ;
        $sReturn='<form method="GET" action="?">'
                . $this->renderHiddenfields()
                . $this->lF('label.searchhelp').'<br><br>'
                . $this->renderLabelSearch().': '
                . $this->renderInput(array('size'=>'50'))
                . ($sOptions ? '<br><br><strong>'.$this->lF('label.searchoptions').'</strong>:<br><table>'.$sOptions.'</table><hr>' : '')
                .'<button>'.$this->lF('btn.search.label').'</button>'
                .'</form>'
                ;
        return $sReturn;
    }
    // ----------------------------------------------------------------------
    // render function to display search result
    // ----------------------------------------------------------------------
    
    /**
     * do search and render search results
     * @param string  $q            search string
     * @param string  $aOptions     options
     *                  url => subset of search, i.e. '//[domain]/[path]'
     *                  subdir => subset of search without domain with starting slash (/[path])
     * @param string  $sOutputType  one of html| ...
     * @return string
     */
    public function renderSearchresults($q=false, $aOptions = array(), $sOutputType = 'html') {
        $sOut = '';
        $aData = array();
        $iHits = 0;
        if(!$q){
            $q=$this->getQueryValue('q');
        }
        $q = trim($q);
        if ($q) {
            $aData = $this->search($q, $aOptions);

            $iHits = $this->getCountOfSearchresults($aData);
            
            // LIMIT output ... maybe add a paging?
            while(count($aData)>50){
                array_pop($aData);
            } 
            

            // echo '<pre>'.print_r($_SERVER, 1).'</pre>'; die();
            if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $client_ip = $_SERVER['REMOTE_ADDR'];
            } else {
                $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            $aResult = $this->oDB->insert(
                    'searches', array(
                        'ts' => date("Y-m-d H:i:s"),
                        'siteid' => $this->iSiteId,
                        'searchset' => $aOptions,
                        'query' => $q,
                        'results' => $iHits,
                        'host' => $client_ip,
                        'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '-',
                        'referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-',
                    )
            );
            // echo "\n" . $this->oDB->last() . '<br>'; 

            switch ($sOutputType) {
                case 'html':
                    $sOut = '<strong>'.$this->lF('searchout.results').'</strong><br><br>';
                    if (!$iHits) {
                        $sOut .= $q ? '<p>' . $this->lF('searchout.nohit') . '</p>' : '';
                    } else {
                        $sOut .= '
                            <style>
                            .searchresult{margin: 0 0 1em 0; border: 0px solid #eee; border-left: 0px solid #eee; padding: 0.5em;}
                            .searchresult:hover{background:#fafafa;}
                            .searchresult a{color:#44a; font-size: 120%;}
                            .searchresult .date{color:#fa3; font-style: italic; font-size: 80%;}
                            .searchresult .url{color:#393;}
                            .searchresult .detail{color:#888;}
                            .searchresult .bar{width: 20%; height: 3em; border-top: 1px solid #eee; float: right; margin-right: 1em; color:#888; }
                            .searchresult .bar2{background:#e0f0ea; height: 1.5em; }

                            .searchresult .mark1{background:#fd3;}
                            .searchresult .mark2{background:#3f3;}
                            .searchresult .mark3{background:#f88;}
                            .searchresult .mark4{background:#ccf;}

                            </style>';
                        if ($iHits > 50) {
                            $sOut .= '<p>' . $this->lF('searchout.too-many-hits') . '<br><br></p>';
                        } else {
                            $sOut .= '<p>' . sprintf($this->lF('searchout.hits'), $iHits) . '</p>';
                        }
                        $iMaxRanking = false;
                        foreach ($aData as $iRanking => $aDataItems) {
                            if (!$iMaxRanking) {
                                $iMaxRanking = $iRanking ? $iRanking : 1;
                            }
                            foreach ($aDataItems as $aItem) {
                                $sAge = round((date("U") - date("U", strtotime($aItem['ts'])) ) / 60 / 60 / 24);
                                $sAge = $sAge > 1 ? '(' . $sAge . ' Tage)' : '';

                                $sDetail = '';
                                if ($aItem['description']) {
                                    $sDetail.=$aItem['description'] . '<br>';
                                }
                                // $sDetail.= '<pre>'.print_r($aItem['results'],true) . '</pre>';
                                //echo "<pre>" . print_r($aItem,1 ) . "</pre>";
                                $aPreviews = array();
                                $aSearchwords = explode(" ", $q);
                                foreach ($aSearchwords as $sWord) {

                                    $iLastPos = 0;
                                    $iSurround = 30;
                                    while (!stripos($aItem['content'], $sWord, $iLastPos) === false) {
                                        $iLastPos = stripos($aItem['content'], $sWord, $iLastPos);
                                        $aPreviews[$iLastPos] = substr($aItem['content'], $iLastPos - $iSurround, ($iSurround * 4 + strlen($sWord)));
                                        $iLastPos++;
                                    }
                                }
                                ksort($aPreviews);
                                // echo "<pre>" . print_r($aPreviews,1 ) . "</pre>";

                                if (count($aPreviews)) {
                                    $iPreview = 0;
                                    foreach ($aPreviews as $sPreview) {
                                        $iPreview++;
                                        if ($iPreview > 1) {
                                            $iMore = count($aPreviews) - $iPreview;
                                            $sDetail.=sprintf($this->lF('searchout.n-more-hits'), $iMore);
                                            break;
                                        }
                                        $sDetail.='...' . $sPreview . '...<br>';
                                    }
                                }
                                $iWord = 0;
                                foreach ($aSearchwords as $sWord) {
                                    $iWord++;
                                    $sClass = "mark${iWord}";
                                    $sDetail = preg_replace('@' . $sWord . '@i', '<span class="' . $sClass . '">\\0</span>', $sDetail);
                                }

                                $sOut.='
                                <div class="searchresult" CConclickCC="location.href=\'' . $aItem['url'] . '\';">
                                    <div class="bar">
                                        <span style="float: right">' . round($iRanking / $iMaxRanking * 100) . '%</span>
                                        <div class="bar2" style="width: ' . round($iRanking / $iMaxRanking * 100) . '%">&nbsp;</div>
                                    </div>
                                   <a href="' . $aItem['url'] . '">' . $aItem['title'] . '</a> <span class="date">' . $sAge . '</span><br>

                                    <div class="url">' . $aItem['url'] . '</div>
                                    <div class="detail">'
                                        . $sDetail . '
                                    </div>
                                </div>
                                     ';
                            }
                        }
                    }

                    break;

                default:
                    break;
            }
        }
        return $sOut 
                . '<br>'
                . 'powered by <a href="'.$this->aAbout['urlDocs'].'">' . $this->aAbout['product'].' '.$this->aAbout['version'].'</a>: '
                . $this->LF('about.infostring');
    }

}
