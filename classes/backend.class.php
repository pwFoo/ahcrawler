<?php

require_once 'ahwi-updatecheck.class.php';
require_once 'analyzer.html.class.php';
require_once 'crawler-base.class.php';
require_once 'crawler.class.php';
require_once 'httpheader.class.php';
require_once 'ressources.class.php';
require_once 'renderer.class.php';
require_once 'search.class.php';
require_once 'sslinfo.class.php';
require_once 'status.class.php';

/**
 * 
 * AXLES CRAWLER :: BACKEND
 * 
 * 
 * */
class backend extends crawler_base {

    private $_aMenu = array(
        'home'=>array(), 
        'settings'=>array(
            'setup'=>array(),
            'profiles'=>array(),
        ),
        'search'=>array(
            'status'=>array(), 
            'searches'=>array(),
        ),
        'analysis'=>array(
            'sslcheck'=>array(), 
            'httpheaderchecks'=>array(), 
            'htmlchecks'=>array(), 
            'linkchecker'=>array(), 
            'ressources'=>array(),
            'checkurl'=>array(), 
            'ressourcedetail'=>array(), 
        ), 
        'about'=>array(
            'update'=>array(), 
        )
    );
    private $_sPage = false;
    private $_sTab = false;
    
    private $_aIcons= array(
        'menu'=>array(
            'home'=>'fa fa-home', 
            'setup'=>'fa fa-window-maximize', 
            'settings'=>'fa fa-cogs', 
            'search'=>'fa fa-search', 
            'profiles'=>'fa fa-globe', 
            'crawler'=>'fa fa-flag', 
            'status'=>'fa fa-flag', 
            'searches'=>'fa fa-search', 
            'analysis'=>'fa fa-newspaper-o', 
            'sslcheck'=>'fa fa-shield', 
            'ressources'=>'fa fa-file-code-o', 
            'linkchecker'=>'fa fa-warning', 
            'htmlchecks'=>'fa fa-check', 
            'httpheaderchecks'=>'fa fa-flag-o', 
            'checkurl'=>'fa fa-globe', 
            'ressourcedetail'=>'fa fa-map-o', 
            'about'=>'fa fa-info-circle', 
            'update'=>'fa fa-bolt', 
            'project'=>'fa fa-book', 
            
            'logoff'=>'fa fa-info-circle', 
        ),
        'cols'=>array(
            'url'=>'fa fa-link', 
            'title'=>'fa fa-chevron-right', 
            'description'=>'fa fa-chevron-right', 
            'errorcount'=>'fa fa-bolt', 
            'keywords'=>'fa fa-key', 
            'lasterror'=>'fa fa-bolt', 
            'actions'=>'fa fa-check', 
            'searchset'=>'fa fa-cube', 
            'query'=>'fa fa-search', 
            'results'=>'fa fa-bullseye', 
            'count'=>'fa fa-thumbs-o-up', 
            'host'=>'fa fa-laptop', 
            'ua'=>'fa fa-paw', 
            'referrer'=>'fa fa-link', 
            'ts'=>'fa fa-calendar', 
            'ressourcetype'=>'fa fa-cubes', 
            'type'=>'fa fa-cloud', 
            'content_type'=>'fa fa-file-code-o', 
            'http_code'=>'fa fa-retweet', 
            'length'=>'fa fa-arrows-h', 
            'size'=>'fa ', 
            'time'=>'fa fa-clock-o', 
            
            'updateisrunning'=>'fa fa-spinner fa-pulse', 
        ),
        'res'=>array(
            
            // ressourcetype
            'audio'=>'fa fa-file-sound-o',
            'css'=>'fa fa-eyedropper',
            'image'=>'fa fa-file-image-o',
            'link'=>'fa fa-link',
            'page'=>'fa fa-sticky-note-o',
            'redirect'=>'fa fa-mail-forward',
            'script'=>'fa fa-file-code-o',
            
            // type
            'internal'=>'fa fa-thumb-tack',
            'external'=>'fa fa-globe',
            
            // content_type/ MIME
            
            // http_code
            'http-code-0'=>'fa fa-spinner',
            'http-code-2xx'=>'fa fa-check',
            'http-code-3xx'=>'fa fa-mail-forward',
            'http-code-4xx'=>'fa fa-bolt',
            'http-code-5xx'=>'fa fa-spinner',
            
            'ressources.showtable'=>'fa fa-table',
            'ressources.showreport'=>'fa fa-file-o',
            'ressources.ignorelimit'=>'fa fa-unlock',
            
        ),
        'button'=>array(
            'button.add' => 'fa fa-plus',
            'button.back' => 'fa fa-chevron-left',
            'button.close' => 'fa fa-close',
            'button.continue' => 'fa fa-chevron-right',
            'button.crawl' => 'fa fa-play',
            'button.create' => 'fa fa-star-o',
            'button.delete' => 'fa fa-trash',
            'button.edit' => 'fa fa-pencil',
            'button.help' => 'fa fa-question-circle',
            'button.login' => 'fa fa-check',
            'button.logoff' => 'fa fa-power-off',
            'button.reindex' => 'fa fa-refresh',
            'button.save' => 'fa fa-send',
            'button.search' => 'fa fa-search',
            'button.truncateindex' => 'fa fa-trash',
            'button.up' => 'fa fa-arrow-up',
            'button.view' => 'fa fa-eye',
        ),
    );
    
    public $iLimitRessourcelist=1000;
    
    public $oUpdate = false;

    // ----------------------------------------------------------------------
    /**
     * new crawler
     * @param integer  $iSiteId  site-id of search index
     */
    public function __construct($iSiteId = false) {
        $this->_oLog=new logger();
        if (!isset($_SESSION)) {
            session_start();
        }
        $this->setSiteId($iSiteId);
        $this->logAdd(__METHOD__.' site id was set');
        $this->setLangBackend();
        $this->logAdd(__METHOD__.' backend lang was set');
        $this->_getPage();
        $this->logAdd(__METHOD__.' getPage was finished');
        /*
         * 
         */
        $this->oUpdate=new ahwiupdatecheck(array(
                'product'=>$this->aAbout['product'],
                'version'=>$this->aAbout['version'],
                'baseurl'=>$this->aOptions['updater']['baseurl'],
                'tmpdir'=>($this->aOptions['updater']['tmpdir'] ? $this->aOptions['updater']['tmpdir'] : __DIR__.'/../tmp/'),
                'ttl'=>$this->aOptions['updater']['ttl'],
        ));
        // echo "getUpdateInfos : </pre>" . print_r($this->oUpdate->getUpdateInfos(), 1).'</pre>';
        $this->logAdd(__METHOD__.' Done');
        
        return true;
    }

    // ----------------------------------------------------------------------
    // LOGIN
    // ----------------------------------------------------------------------

    /**
     * check authentication if a user and password were configured
     * @global array  $aUserCfg  config from ./config/config_user.php
     * @return boolean
     */
    private function _checkAuth() {
        $aOptions = $this->_loadOptions();
        if (!isset($aOptions['options']['auth']['user']) || $this->_getUser()
        ) {
            return true;
        }
        if (
                array_key_exists('AUTH_USER', $_POST) && array_key_exists('AUTH_PW', $_POST) && $aOptions['options']['auth']['user'] == $_POST['AUTH_USER'] && $aOptions['options']['auth']['password'] == md5($_POST['AUTH_PW'])
        ) {
            $this->_setUser($_POST['AUTH_USER']);
            return true;
        }
        return false;
    }

    /**
     * get the username of the current user
     * @return boolean
     */
    private function _getUser() {
        if (!array_key_exists('AUTH_USER', $_SESSION)) {
            return false;
        }
        return $_SESSION['AUTH_USER'];
    }

    /**
     * set an authenticated user user
     * @param string  $sNewUser
     * @return boolean
     */
    private function _setUser($sNewUser) {
        if (!$sNewUser) {
            // ... means: logoff
            unset($_SESSION['AUTH_USER']);
            return false;
        }
        $_SESSION['AUTH_USER'] = $sNewUser;
        return $_SESSION['AUTH_USER'];
    }

    /**
     * get html code of a login form
     * @return string
     */
    private function _getLoginForm() {
        $sReturn = '';

        $aTable = array();
        $aTable[] = array(
            '<label for="euser">' . $this->lB('login.username') . '</label>',
            '<input type="text" id="euser" name="AUTH_USER" value="" required="required" placeholder="' . $this->lB('login.username') . '">'
        );
        $aTable[] = array(
            '<label for="epw">' . $this->lB('login.password') . '</label>',
            '<input type="password" id="epw" name="AUTH_PW" value="" required="required" placeholder="' . $this->lB('login.password') . '">'
        );

        $sHref = '?' . str_replace('page=logoff', '', $_SERVER['QUERY_STRING']);

        $sReturn = '<h3>' . $this->lB('login.title') . '</h3>'
                . '<p>' . $this->lB('login.infotext') . '</p>'
                . '<form method="POST" action="' . $sHref . '" class="pure-form pure-form-aligned">'
                . '<div class="pure-control-group">'
                    . '<label for="euser">' . $this->lB('login.username') . '</label>'
                    . '<input type="text" id="euser" name="AUTH_USER" value="" required="required" placeholder="' . $this->lB('login.username') . '">'
                . '</div>'
                . '<div class="pure-control-group">'
                    . '<label for="epw">' . $this->lB('login.password') . '</label>'
                    . '<input type="password" id="epw" name="AUTH_PW" value="" required="required" placeholder="' . $this->lB('login.password') . '">'
                . '</div>'
                . '<br>'
                . '<div class="pure-control-group">'
                    . '<label>&nbsp;</label>'
                    . '<button type="submit" class="pure-button button-secondary">' .$this->_getIcon('button.login'). $this->lB('button.login') . '</button>'
                . '</div>'
                . '</form>'
        ;
        return $sReturn;
    }

    // ----------------------------------------------------------------------
    // NAVIGATION
    // ----------------------------------------------------------------------
    
    /**
     * get new querystring - create the new querystring by existing query string
     * of current request and given new parameters
     * @param array $aQueryParams
     * @return string
     */
    private function _getQs($aQueryParams) {
        if ($_GET) {
            $aQueryParams = array_merge($_GET, $aQueryParams);
        }
        // return '?'.  str_replace(array('%5B','%5D'), array('[',']'), http_build_query($aQueryParams));
        return '?'.  preg_replace('/%5B[0-9]+%5D/simU', '[]', http_build_query($aQueryParams));

        $s='';
        foreach ($aQueryParams as $var => $value) {
            if ($value){
                $s.="&amp;" . $var . "=" . urlencode($value);
            }
        }
        $s = "?" . $s;
        return $s;
        
    }

    /**
     * find the current page (returns one of the menu items of _aMenu)
     * @return string
     */
    private function _getPage() {
        $sPage = (array_key_exists('page', $_GET) && $_GET['page']) ? $_GET['page'] : '';
        if (!$sPage) {
            $aKeys=array_keys($this->_aMenu);
            $sPage = $aKeys[0];
        }
        $sPage2=preg_replace('/[^a-z]/', '', $sPage);
        if(!$sPage || $sPage!==$sPage2){
            $sPage='error404';
            header("HTTP/1.0 404 Not Found");
        }
        $this->_sPage=$sPage;
        return $this->_sPage;
    }

    /**
     * find the current tab or take the first id
     * @return type
     */
    private function _getTab($aTabs=false) {
        $this->_sTab = (array_key_exists('tab', $_GET) && $_GET['tab']) ? $_GET['tab'] : '';
        if ($this->_sTab && $this->_sTab!=='add') {
            setcookie("tab", $this->_sTab, time() + 3600);
        }
        if (!$this->_sTab && array_key_exists('tab', $_COOKIE)) {
            $this->_sTab = $_COOKIE['tab'];
        }

        if (!$this->_sTab && is_array($aTabs)) {
            $aTmp = array_keys($aTabs);
            $this->_sTab = count($aTmp) ? $aTmp[0] : false;
        }

        return $this->_sTab;
    }

    private function _getNavItems($aNav){
        $sNavi = '';
        foreach ($aNav as $sItem=>$aSubItems) {
            $sNaviNextLevel='';
            if (count($aSubItems)){
                $sNaviNextLevel.=$this->_getNavItems($aSubItems);
            }
            $bHasActiveSubitem=strpos($sNaviNextLevel, 'pure-menu-link-active');
            $bIsActive=$this->_sPage == $sItem || $bHasActiveSubitem;
            $sClass = $bIsActive ? ' pure-menu-link-active' : '';
            $sUrl = '?page=' . $sItem;
            if ($this->_sTab) {
                $sUrl.='&amp;tab=' . $this->_sTab;
            }
            if(array_key_exists('menu', $this->aOptions)
                    && array_key_exists($sItem, $this->aOptions['menu'])
                    && !$this->aOptions['menu'][$sItem]
            ){
                // hide menu 
            } else {
                // $sNavi.='<li class="pure-menu-item"><a href="?'.$sItem.'" class="pure-menu-link'.$sClass.'">'.$sItem.'</a></li>';
                $sNavi.='<li class="pure-menu-item">'
                    . '<a href="?page=' . $sItem . '" class="pure-menu-link' . $sClass . '"'
                        . ' title="' . $this->lB('nav.' . $sItem . '.hint') . '"'
                        . '><i class="'.$this->_aIcons['menu'][$sItem].'"></i> ' 
                        . $this->lB('nav.' . $sItem . '.label') 
                    . '</a>'
                    . ($bIsActive ? $sNaviNextLevel : '')
                    ;
            
                $sNavi.='</li>';
            }
        }
        if($sNavi || true){
            $sNavi='<ul class="pure-menu-list">'.$sNavi.'</ul>';
        }
        
        return $sNavi;
    }
    
    /**
     * get html code for navigation; the current page is highlighted
     * @return string
     */
    public function getNavi() {
        if (!$this->_checkAuth()) {
            return '';
        }
        $sNavi = $this->_getNavItems($this->_aMenu);
        /*
        foreach ($this->_aMenu as $sItem) {
            $sClass = ($this->_sPage == $sItem) ? ' pure-menu-link-active' : '';
            $sUrl = '?page=' . $sItem;
            if (!$this->_sTab) {
                $sUrl.='&amp;tab=' . $this->_sTab;
            }
            // $sNavi.='<li class="pure-menu-item"><a href="?'.$sItem.'" class="pure-menu-link'.$sClass.'">'.$sItem.'</a></li>';
            $sNavi.='<li class="pure-menu-item">'
                    . '<a href="?page=' . $sItem . '" class="pure-menu-link' . $sClass . '"'
                    . ' title="' . $this->lB('nav.' . $sItem . '.hint') . '"'
                    . '><i class="'.$this->_aIcons['menu'][$sItem].'"></i> ' . $this->lB('nav.' . $sItem . '.label') . '</a></li>';
        }
         * 
         */
        return $sNavi;
    }

    /**
     * get html code for horizontal navigation
     * @param array $aTabs  nav items
     * @return string
     */
    private function _getNavi2($aTabs=array(), $bAddButton=false, $sUpUrl=false) {
        $sReturn = '';
        if (!$this->_sTab) {
            $this->_getTab($aTabs);
        }
        if($bAddButton){
            $aTabs['add']=$this->_getIcon('button.add');
        }
        if($sUpUrl){
            $sReturn.='<li class="pure-menu-item">'
                    . '<a href="' . $sUpUrl . '" class="pure-menu-link"'
                    . '>' . $this->_getIcon('button.up') . '</a></li>';            
        }
        foreach ($aTabs as $sId => $sLabel) {
            $sUrl = '?page=' . $this->_sPage . '&amp;tab=' . $sId;
            $sClass = ($this->_sTab == $sId) ? ' pure-menu-link-active' : '';
            $sReturn.='<li class="pure-menu-item">'
                    . '<a href="' . $sUrl . '" class="pure-menu-link' . $sClass . '"'
                    . '>' . $this->_getIcon('project') . $sLabel . '</a></li>';
        }
        if ($sReturn) {
            $sReturn = '<div class="pure-menu pure-menu-horizontal">'
                    . '<ul class="pure-menu-list">'
                    . '' . $sReturn . ''
                    . '</ul>'
                    . '</div>';
        }
        return $sReturn;
    }

    /**
     * get html code for a message box 
     * @param type $sMessage  message text
     * @param type $sLevel    level ok|warning|error
     * @return string
     */
    protected function _getMessageBox($sMessage, $sLevel='warning'){
        
        return '<div class="message message-'.$sLevel.'">'
                // . $oRenderer->renderShortInfo($sLevel)
                . $sMessage
                . '</div>'
                ;
    }
    /**
     * 
     * @return string
     */
    private function _renderChildItems($aNav){
        $sReturn='';
        foreach ($aNav as $sItem=>$aSubItems) {
            if ($this->_sPage!==$sItem){
                $sUrl = '?page=' . $sItem;
                if ($this->_sTab) {
                    $sUrl.='&amp;tab=' . $this->_sTab;
                }
                // $sNavi.='<li class="pure-menu-item"><a href="?'.$sItem.'" class="pure-menu-link'.$sClass.'">'.$sItem.'</a></li>';
                if(array_key_exists('menu', $this->aOptions)
                        && array_key_exists($sItem, $this->aOptions['menu'])
                        && !$this->aOptions['menu'][$sItem]
                ){
                    // hide item
                } else {
                    $sReturn.=
                        '<a href="?page=' . $sItem . '" class="childitem"'
                            . ' title="' . $this->lB('nav.' . $sItem . '.hint') . '"'
                            . '><i class="'.$this->_aIcons['menu'][$sItem].'"></i> ' 
                            . '<strong>'.$this->lB('nav.' . $sItem . '.label').'</strong><br>'
                            .$this->lB('nav.' . $sItem . '.hint')
                        . '</a>'
                        ;
                }
            }
        }
        $sReturn.='<div style="clear: both"></div>';
        return $sReturn;
    }
    
    /**
     * get html code for document header: headline and hint
     * @return string
     */
    public function getHead() {
        $sReturn='';
        $this->logAdd(__METHOD__ . '() start; page = "' . $this->_sPage . '"');
        $sH2 = $this->lB('nav.' . $this->_sPage . '.label');
        $sHint = $this->lB('nav.' . $this->_sPage . '.hint');
        if (!$this->_checkAuth()) {
            $sH2 = $this->lB('nav.login.label');
            $sHint = $this->lB('nav.login.access-denied');
        }
        
        $oStatus=new status();
        $aStatus=$oStatus->getStatus();
        $sStatus='';
        if ($aStatus && is_array($aStatus)){
            $sStatus.=''
                    . $this->_getIcon('updateisrunning')
                    . 'Start: '.date("H:i:s", $aStatus['start'])
                    . ' ('. ($aStatus['last']-$aStatus['start']).' s): '
                    . $aStatus['action'] . ' - '
                    . $aStatus['lastmessage'].' <br>'
                    // .'<pre>'.print_r($aStatus, 1).'</pre>'
                    ;
        } else {
            // $sStatus=$this->lB('status.no-action');
        }
        
                
        $this->logAdd(__METHOD__ . ' H2 = "'.$sH2.'"');
        return ''
                . ($this->_checkAuth() && $this->_getUser()
                    ? '<span style="z-index: 100000; position: fixed; right: 1em; top: 1em;">'
                        . $this->_getButton(array(
                            'href' => './?page=logoff',
                            'class' => 'button-secondary',
                            'label' => 'button.logoff',
                            'popup' => false
                        ))
                        . '</span>'
                    : ''
                )
                . (isset($sH2) && $sH2 ? '<h2>' : '')
                . (isset($this->_aIcons['menu'][$this->_sPage]) 
                    ? '<i class="'.$this->_aIcons['menu'][$this->_sPage].'"></i> '
                    : ''
                    )
                . (isset($sH2) && $sH2 ? $sH2 . '</h2><p class="pageHint">' . $sHint . '</p>' : '')
                
                . ($sStatus ? '<div id="divStatus">'. $sStatus .'</div>' : '')
        ;
    }

    // ----------------------------------------------------------------------
    // PROFILE/ CONFIG
    // ----------------------------------------------------------------------

    /**
     * get array with search profiles
     * @return array
     */
    private function _getProfiles() {
        $aOptions = $this->_loadOptions();
        $aReturn = array();
        if (isset($aOptions['profiles']) && count($aOptions['profiles'])) {
            foreach ($aOptions['profiles'] as $sId => $aData) {
                $aReturn[$sId] = $aData['label'];
            }
        }
        return $aReturn;
    }

    /**
     * get array with profile data of an existing config
     * @see _getProfiles()
     * @param string   $sId  id of search profile
     * @return array
     */
    private function _getProfileConfig__UNUSED($sId) {
        $aOptions = $this->_loadOptions();
        if (array_key_exists('profiles', $aOptions) && array_key_exists($sId, $aOptions['profiles'])) {
            return $aOptions['profiles'][$sId];
        }
        return false;
    }

    // ----------------------------------------------------------------------
    // OUTPUT RENDERING
    // ----------------------------------------------------------------------

    /**
     * get html code for a result table
     * @param array  $aResult          result of a select query
     * @param string $sLangTxtPrefix   langtext prefix
     * @return string
     */
    private function _getHtmlTable($aResult, $sLangTxtPrefix = '', $sTableId=false) {
        $sReturn = '';
        $aFields = false;
        if (!is_array($aResult) || !count($aResult)) {
            return false;
        }
        foreach ($aResult as $aRow) {
            if (!$aFields) {
                $aFields = array_keys($aRow);
            }
            $sReturn.='<tr>';
            foreach ($aFields as $sField) {
                $sReturn.='<td class="td-' . $sField . '">' . $aRow[$sField] . '</td>';
            }
            $sReturn.='</tr>';
        }
        if ($sReturn) {
            $sTh = '';
            foreach ($aFields as $sField) {
                $sIcon=(array_key_exists($sField, $this->_aIcons['cols']) ? '<i class="'.$this->_aIcons['cols'][$sField].'"></i> ' : '['.$sField.']');

                $sTh.='<th class="th-' . $sField . '">' . $sIcon . $this->lB($sLangTxtPrefix . $sField) . '</th>';
            }
            // $sReturn = '<table class="pure-table pure-table-horizontal pure-table-striped datatable">'
            $sReturn = '<table'.($sTableId ? ' id="'.$sTableId.'"' : '').' class="pure-table pure-table-horizontal datatable">'
                    . '<thead><tr>' . $sTh . '</tr></thead>'
                    . '<tbody>' . $sReturn . ''
                    . '</tbody>'
                    . '</table>';
        }
        return $sReturn;
    }

    /**
     * get html code for a simple table without table head
     * @param array  $aResult          result of a select query
     * @param array  $bFirstIsHeader   flag: first record is header line; default is false
     * @return string
     */
    private function _getSimpleHtmlTable($aResult, $bFirstIsHeader=false) {
        $sReturn = '';
        $bIsFirst=true;
        foreach ($aResult as $aRow) {
            $sReturn.='<tr>';
            foreach ($aRow as $sField) {
                $sReturn.= $bFirstIsHeader && $bIsFirst
                        ? '<th>' . $sField . '</th>'
                        : '<td>' . $sField . '</td>'
                        ;
            }
            $sReturn.='</tr>';
            $bIsFirst=false;
        }
        if ($sReturn) {
            $sReturn = '<table class="pure-table pure-table-horizontal"><thead></thead>'
                    . '<tbody>' . $sReturn . ''
                    . '</tbody>'
                    . '</table>';
        }
        return $sReturn;
    }

    private function _getButton($aOptions = array()) {
        $sReturn = '';
        if (!array_key_exists('href', $aOptions)) {
            $aOptions['href'] = '#';
        }
        if (!array_key_exists('class', $aOptions)) {
            $aOptions['class'] = '';
        }
        if (!array_key_exists('target', $aOptions)) {
            $aOptions['target'] = '';
        }
        if (!array_key_exists('label', $aOptions)) {
            $aOptions['label'] = 'button.view';
        }
        if (!array_key_exists('popup', $aOptions)) {
            $aOptions['popup'] = true;
        }
        $sReturn = '<a '
                . 'class="pure-button ' . $aOptions['class'] . '" '
                . 'href="' . $aOptions['href'] . '" '
                . 'target="' . $aOptions['target'] . '" '
                . 'title="' . $this->lB($aOptions['label'] . '.hint') . '" '
                . ($aOptions['popup'] ? 'onclick="showModal(this.href); return false;"' : '')
                . '>' . $this->_getIcon($aOptions['label']).$this->lB($aOptions['label']) . '</a>';
        return $sReturn;
    }

    private function _getIcon($sKey, $bEmptyIfMissing=false){
        foreach(array_keys($this->_aIcons)as $sIconsection){
            if (array_key_exists($sKey, $this->_aIcons[$sIconsection])){
                return '<i class="'.$this->_aIcons[$sIconsection][$sKey].'"></i> ';
            }
        }
        return $bEmptyIfMissing ? '' : '<span title="missing icon ['.$sKey.']">['.$sKey.']</span>';
    }
    
    /**
     * prettify table output: limit a string to a mximum and insert space
     * @param string  $sVal   string
     * @param int     $iMax   max length
     * @return string
     */
    private function _prettifyString($sVal, $iMax = 500) {
        $sVal = str_replace(',', ', ', $sVal);
        $sVal = str_replace(',  ', ', ', $sVal);
        $sVal = htmlentities($sVal);
        return (strlen($sVal) > $iMax) ? substr($sVal, 0, $iMax) . '<span class="more"></span>' : $sVal;
    }

    /**
     * get html code for a simple table without table head
     * @param array  $aResult          result of a select query
     * @return string
     */
    private function _getSearchindexTable($aResult, $sLangTxtPrefix = '') {
        $aTable = array();
        foreach ($aResult as $aRow) {
            $sId = $aRow['id'];
            unset($aRow['id']);
            foreach ($aRow as $sKey => $sVal) {
                $aRow[$sKey] = $this->_prettifyString($sVal);
            }
            $aRow['url']=str_replace('/', '/&shy;', $aRow['url']);
            $aRow['actions'] = $this->_getButton(array(
                'href' => 'overlay.php?action=viewindexitem&id=' . $sId,
                'class' => 'button-secondary',
                'label' => 'button.view'
            ));
            $aTable[] = $aRow;
        }
        return $this->_getHtmlTable($aTable, $sLangTxtPrefix);
    }

    // ----------------------------------------------------------------------
    
    
    // ----------------------------------------------------------------------
    // PAGE CONTENT
    // ----------------------------------------------------------------------

    /**
     * wrapper function: get page content as html
     * @return string
     */
    public function getContent() {
        if (!$this->_checkAuth()) {
            return $this->_getLoginForm();
        }
        $sPagefile='pages/'.$this->_sPage.'.php';
        if(!file_exists($sPagefile)){
            $sPagefile='pages/error404.php';
            return include $sPagefile;
            // include $sPagefile;
        } else {
            return include $sPagefile;
        }
    }


    private function _getChart($aOptions){
        $sReturn='';
        
        static $iChartCount;
        if(!isset($iChartCount)){
            $iChartCount=0;
        }
        $iChartCount++;
        
        $sDomIdDiv='chart-div-'.$iChartCount;
        $sDomIdCanvas='chart-canvas-'.$iChartCount;
        $sVarChart='chartConfig'.$iChartCount;
        $sVarCtx='chartCtx'.$iChartCount;
        
        if(isset($aOptions['data'])){
            $aOptions['labels']=array();
            $aOptions['values']=array();
            $aOptions['colors']=array();
            foreach($aOptions['data'] as $aItem){
                $aOptions['labels'][]=$aItem['label'];
                $aOptions['values'][]=$aItem['value'];
                $aOptions['colors'][]=$aItem['color'];
            }
        }
        return '
            
            <div id="'.$sDomIdDiv.'" class="piechart">
		<canvas id="'.$sDomIdCanvas.'"></canvas>
            </div>
            <script>
                var '.$sVarChart.' = {
                    type: \''.$aOptions['type'].'\',
                    data: {
                        datasets: [{
                                data: '.json_encode($aOptions['values']).',
                                backgroundColor: '. str_replace('"', '', json_encode($aOptions['colors'])).',
                        }],
                        labels: '.json_encode($aOptions['labels']).'
                    },
                    options: {
                        animation: {
                            duration: 0
                        },
                        legend: {
                            display: true
                        },
                        responsive: true
                    }
                    
                };

                // window.onload = function() {
                    var '.$sVarCtx.' = document.getElementById("'.$sDomIdCanvas.'").getContext("2d");
                    window.myPie = new Chart('.$sVarCtx.', '.$sVarChart.');
                // };
            </script>
        ';
    }

        /**
         * html check - get count pages with too short element
         * @param string   $sKey        name of item; one of title|description|keywords
         * @param integer  $iMinLength  minimal length
         * @return integer
         */
        private function _getHtmlchecksCount($sKey, $iMinLength){
            $aTmp = $this->oDB->query('
                    select count(*) count from pages 
                    where siteid='.$this->_sTab.' and errorcount=0 and length('.$sKey.')<'.$iMinLength
                )->fetchAll(PDO::FETCH_ASSOC);
            return $aTmp[0]['count'];
        }
        /**
         * html check - get pages with too large values
         * @param string   $sKey    name of item; one of size|time
         * @param integer  $iMax    max value
         * @return integer
         */
        private function _getHtmlchecksLarger($sKey, $iMax){
            $aTmp = $this->oDB->query('
                    select count(*) count from pages 
                    where siteid='.$this->_sTab.' and errorcount=0 and '.$sKey.'>'.$iMax
                )->fetchAll(PDO::FETCH_ASSOC);
            return $aTmp[0]['count'];
        }
        /**
         * html check - get get html code for a chart of too short elements
         * @param string   $sQuery      query to fetch data
         * @param integer  $iMinLength  minimal length
         * @return string
         */
        private function _getHtmlchecksChart($iTotal, $iValue){
            return $this->_getChart(array(
                'type'=>'pie',
                'data'=>array(
                    array(
                        'label'=>$this->lB('htmlchecks.label-warnings'),
                        'value'=>$iValue,
                        'color'=>'getStyleRuleValue(\'color\', \'.chartcolor-warnings\')',
                        // 'legend'=>$this->lB('linkchecker.found-http-'.$sSection).': '.,
                    ),
                    array(
                        'label'=>$this->lB('htmlchecks.label-ok'),
                        'value'=>($iTotal-$iValue),
                        'color'=>'getStyleRuleValue(\'color\', \'.chartcolor-ok\')',
                        // 'legend'=>$this->lB('linkchecker.found-http-'.$sSection).': '.,
                    ),
                )
            ));
        }
        /**
         * html check - get get html code for a table of too short elements
         * @param string|array   $sQuery      query to fetch data
         * @param string         $iMinLength  table id
         * @return string
         */
        private function _getHtmlchecksTable($sQuery, $sTableId=false){
            if(is_array($sQuery)){
                $aTmp = $this->oDB->debug()->select(
                        $sQuery[0], // table
                        $sQuery[1], // what to select
                        $sQuery[2]  // params
                        );
                echo '<pre>'.print_r($sQuery, 1).'</pre>';
                echo '<pre>'.print_r($aTmp, 1).'</pre>';
                
            } else {
                $aTmp = $this->oDB->query($sQuery)->fetchAll(PDO::FETCH_ASSOC);
            }
            $aTable = array();
            foreach ($aTmp as $aRow) {
                $aTable[] = $aRow;
            }
            return $this->_getHtmlTable($aTable, "db-pages.", $sTableId);
        }
    

    // ----------------------------------------------------------------------
    // OVERLAY CONTENT
    // ----------------------------------------------------------------------

    private function _getRequestParam($sParam) {
        return (array_key_exists($sParam, $_GET) && $_GET[$sParam]) ? $_GET[$sParam] : false;
    }

    /**
     * wrapper function: get page content as html
     * @return string
     */
    public function getOverlayContent() {
        if (!$this->_checkAuth()) {
            // TODO: go to login form
            // return $this->lB('nav.login.access-denied');
            return $this->_getLoginForm();
        }
        $sAction = $this->_getRequestParam('action');
        $sMethod = "_getOverlayContent" . $sAction;
        if (method_exists($this, $sMethod)) {
            return call_user_func(__CLASS__ . '::' . $sMethod, $this);
        }
        return 'unknown method: ' . __CLASS__ . '::' . $sMethod;
    }

    /**
     * overlay: view a search index item
     * @return string
     */
    private function _getOverlayContentviewindexitem() {
        $sReturn = '<h1>' . $this->lB('overlay.viewIndexItem') . '</h1>';
        $sId = $this->_getRequestParam('id');
        if (!$sId) {
            return $sReturn;
        }
        $aItem = $this->oDB->select(
                'pages', '*', array(
            'AND' => array(
                'id' => $sId,
            )
                )
        );
        if (count($aItem)) {
            $aTable = array();
            foreach ($aItem[0] as $sKey => $sVal) {
                $aTable[] = array(
                    $sKey,
                    $this->_prettifyString($sVal)
                );
            }
            return $sReturn . $this->_getSimpleHtmlTable($aTable)
                    . '<br>'
                    . $this->_getButton(array(
                        'href' => './?page=status',
                        'target' => '_top',
                        'class' => 'button-secondary',
                        'label' => 'button.close'
                    ))
                    . ' '
                    . $this->_getButton(array(
                        'href' => 'overlay.php?action=updateindexitem&url=' . $aItem[0]['url'] . '&siteid=' . $aItem[0]['siteid'],
                        'class' => 'button-success',
                        'label' => 'button.reindex'
                    ))
                    . ' '
                    . $this->_getButton(array(
                        'href' => 'overlay.php?action=deleteindexitem&id=' . $sId . '&siteid=' . $aItem[0]['siteid'],
                        'class' => 'button-error',
                        'label' => 'button.delete'
                    ))
            ;
        }
        return $sReturn;
    }

    /**
     * overlay: delete a search index item
     * @return string
     */
    private function _getOverlayContentdeleteindexitem() {
        $sReturn = '<h1>' . $this->lB('overlay.deleteIndexItem') . '</h1>';
        $sSiteId = $this->_getRequestParam('siteid');
        $sId = $this->_getRequestParam('id');

        $sReturn.='siteid=' . $sSiteId . ' id=' . $sId . '<br>';
        $o = new crawler($sSiteId);
        $sReturn.=$o->deleteFromIndex($sId);
        $sReturn.=$this->_getButton(array(
            'href' => './?page=status',
            'class' => 'button-secondary',
            'target' => '_top',
            'label' => 'button.close'
        ));
        return $sReturn;
    }

    /**
     * overlay: update a single url in search index
     * @return string
     */
    private function _getOverlayContentupdateindexitem() {
        $sReturn = '<h1>' . $this->lB('overlay.updateIndexItem') . '</h1>';
        $sSiteId = $this->_getRequestParam('siteid');
        $sUrl = $this->_getRequestParam('url');
        $sReturn.='siteid=' . $sSiteId . ' url=' . $sUrl . '<br>';
        ob_start();
        $o = new crawler($sSiteId);
        $o->updateSingleUrl($sUrl);
        $sReturn.='<pre>' . ob_get_contents() . '</pre>';
        ob_end_clean();

        $sReturn.=$this->_getButton(array(
            'href' => './?page=status',
            'class' => 'button-secondary',
            'target' => '_top',
            'label' => 'button.close'
        ));
        return $sReturn;
    }

    private function _getOverlayContentcrawl() {
        $sReturn = '<h1>' . $this->lB('overlay.crawl') . '</h1>';
        $sSiteId = $this->_getRequestParam('siteid');
        $sReturn.='siteid=' . $sSiteId . '<br>';
        ob_start();
        // echo "..."; ob_flush();flush();
        $o = new crawler($sSiteId);
        $o->run();
        $sReturn.='<pre>' . ob_get_contents() . '</pre>';
        ob_end_clean();

        $sReturn.=$this->_getButton(array(
            'href' => './?page=status',
            'class' => 'button-secondary',
            'target' => '_top',
            'label' => 'button.close'
        ));
        return $sReturn;
    }

    private function _getOverlayContentsearch() {
        $sSiteId = (int)$this->_getRequestParam('siteid');
        $sQuery = $this->_getRequestParam('query');
        $sSubdir = $this->_getRequestParam('subdir');
        $o = new ahsearch($sSiteId);
        $aResult = $o->search($sQuery, array('subdir'=>$sSubdir));
        // print_r($aResult);
        
        $sSelect='';
        $aCat=$o->getSearchCategories();
        if ($aCat){
            foreach ($aCat as $sLabel=>$sUrl){
                $sSelect.='<option value="'.$sUrl.'" '.($sSubdir==$sUrl?'selected="selected"':'').' >'.$sLabel.'</option>';
            }
            $sSelect=' <select name="subdir" class="form-control">'.$sSelect.'</select> ';
        }

        $sForm = '<form action="" method="get" class="pure-form">'
                . '<input type="hidden" name="action" value="search">'
                . '<input type="hidden" name="siteid" value="' . $sSiteId . '">'
                // . '<input type="hidden" name="subdir" value="' . $sSubdir . '">'
                . '<label>' . $this->lB('searches.query') . '</label> '
                . '<input type="text" name="query" value="' . $sQuery . '" required="required">'
                . ' '
                . $sSelect
                . '<button class="pure-button button-success">' . $this->_getIcon('button.search') . $o->lF('btn.search.label') . '</button>'
                . '</form>';

        $iResults = $o->getCountOfSearchresults($aResult);
        $sReturn = '<h1>' . $this->lB('overlay.search') . '</h1>'
                . $sForm
                . ($sQuery ? '<p>' . $this->lB('searches.results') . ': ' . $iResults . '<p>' : '');

        $aTable = array();

        $iCounter = 0;
        $iMaxRanking = false;

        if ($sQuery && $iResults) {
            foreach ($aResult as $iRanking => $aDataItems) {
                if (!$iMaxRanking) {
                    $iMaxRanking = $iRanking;
                }
                $aRow = array();
                foreach ($aDataItems as $aItem) {
                    // unset($aItem['content']);
                    // echo '<pre>'.print_r($aItem, 1); die();
                    $iCounter ++;
                    $sResult = '';
                    foreach ($aItem['results'] as $sWord => $aMatchTypes) {
                        $sResult.='<strong>' . $sWord . '</strong><br>';
                        foreach ($aMatchTypes as $sType => $aHits) {
                            $sMatches = '';
                            foreach ($aHits as $sWhere => $iHits) {
                                if ($iHits) {
                                    $sMatches.='...... ' . $sWhere . ': ' . $iHits . '<br>';
                                }
                            }
                            if ($sMatches) {
                                $sResult.='.. ' . $sType . '<br>' . $sMatches;
                            }
                        }
                    }
                    $aTable[] = array(
                        'search.#' => $iCounter,
                        'search.summary' => '<strong><a href="' . $aItem['url'] . '" target="_blank">' . $aItem['title'] . '</a></strong><br>'
                        . 'description: <em>' . $aItem['description'] . '</em><br>'
                        . 'keywords: <em>' . $aItem['keywords'] . '</em><br>'
                        . 'content: <em>' . $this->_prettifyString($aItem['content'], 200) . '</em><br>'
                        ,
                        'search.ranking' => '<a href="#" class="hoverinfos">' . $iRanking . '<span>' . $sResult . '<!-- <pre>' . print_r($aItem['results'], 1) . '</pre>--></span></a>',
                    );
                }
            }
        }
        $sReturn.=$this->_getHtmlTable($aTable)
                . (($iResults > 3) ? '<br>' . $sForm : '')
                . '<br>' . $this->_getButton(array(
                    'href' => './?page=searches',
                    'class' => 'button-secondary',
                    'target' => '_top',
                    'label' => 'button.close'
        ));

        return $sReturn;
    }
    
    
    private function _getRessourceSummary($aRessourcelist, $bLinkRessource=false){
        $sReturn='';
        // $aFilter=array('ressourcetype','type', 'content_type', 'http_code');
        $aFilter=array('type', 'content_type', 'http_code');
        $aCounter=array();
        $aTable = array();
        if (count($aRessourcelist)) {
            
            foreach ($aRessourcelist as $aRow) {
                foreach ($aFilter as $sKey){
                    if (!array_key_exists($sKey, $aCounter)){
                        $aCounter[$sKey]=array();
                    }
                    if (!array_key_exists($aRow[$sKey], $aCounter[$sKey])){
                        $aCounter[$sKey][$aRow[$sKey]]=0;
                    }
                    $aCounter[$sKey][$aRow[$sKey]]++;
                    ksort($aCounter[$sKey]);
                }
                /*
                    $aRow['actions'] = $this->_getButton(array(
                        'href' => 'overlay.php?action=ressourcedetail&id=' . $aRow['id'] . '&siteid=' . $this->_sTab . '',
                        'class' => 'button-secondary',
                        'label' => 'button.view'
                    ));
                 * 
                 */
                    $sUrl=str_replace('/', '/&shy;', ($bLinkRessource
                            ?'<a href="?action=ressourcedetail&id='.$aRow[$bLinkRessource].'&siteid='.$_GET['siteid'].'">'.$aRow['url'].'</a>'
                            :$aRow['url']
                    ));
                    
                    $aRow['type'] = $oRenderer->renderArrayValue('type', $aRow);
                    $aRow['http_code'] = $oRenderer->renderArrayValue('http_code', $aRow);
                    // unset($aRow['id']);
                    $aTable[] = array(
                        $sUrl,

                        $aRow['type'],
                        $aRow['content_type'],
                        $aRow['http_code'],
                    );

            }
        } else {
            $sReturn.=' :-/ ';
        }
        $sReturn.=$this->_getHtmlTable($aTable, "db-ressources.");
        return $sReturn;
    }

        
    private function _getOverlayContentressourcedetail() {
        $sSiteId = $this->_getRequestParam('siteid');
        $sId = $this->_getRequestParam('id');
        $aRessource = $this->oDB->select(
                'ressources', 
                '*', 
                array(
                    'AND' => array(
                        'siteid' => $sSiteId,
                        'id' => $sId,
                    ),
                )
        );
        $aFrom = $this->oDB->select(
                'ressources', 
                array(
                    '[>]ressources_rel' => array('id'=>'id_ressource')
                ),
                '*', 
                array(
                    'AND' => array(
                        'ressources_rel.siteid' => $sSiteId,
                        'ressources_rel.id_ressource_to' => $sId,
                    ),
                )
        );
        $aTo = $this->oDB->select(
                'ressources', 
                array(
                    '[>]ressources_rel' => array('id'=>'id_ressource_to')
                ),
                '*', 
                array(
                    'AND' => array(
                        'ressources_rel.siteid' => $sSiteId,
                        'ressources_rel.id_ressource' => $sId,
                    ),
                )
        );
        // echo $this->oDB->last().'<br>';
        $sReturn='';
        
        $sReturn.='<h1>'.$aRessource[0]['url'].'</h1>'
                .'<table>'
                . '<tbody>'
                . '<tr>'
                    . '<td valign="top">'
                        .'FROM: '
                        . $this->_getRessourceSummary($aFrom, 'id_ressource')
                        // . '<pre>'.print_r($aFrom, 1).'</pre>'
                    . '</td>'
                
                    . '<td valign="top">'
                        . '&gt;'
                    . '</td>'
                
                    . '<td valign="top">'
                        . $this->_getRessourceSummary($aRessource)
                        // .'<pre>'.print_r($aRessource, 1).'</pre>'
                    . '</td>'
                
                    . '<td valign="top">'
                        . '&gt;'
                    . '</td>'
                
                    . '<td valign="top">'
                        .'TO: '
                        . $this->_getRessourceSummary($aTo, 'id_ressource_to')
                        // .'To: <pre>'.print_r($aTo, 1).'</pre>'
                    . '</td>'
                . '</tr>'
                . '</tbody>'
                . '</table>'
                // .'<pre>'.print_r($aRessource, 1).'</pre>'
                // .'FROM: <pre>'.print_r($aFrom, 1).'</pre>'
                // .'To: <pre>'.print_r($aTo, 1).'</pre>'
                ;
        return $sReturn;
    }
    // ----------------------------------------------------------------------
}
