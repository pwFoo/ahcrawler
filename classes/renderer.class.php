<?php

require_once 'ressources.class.php';
require_once 'httpheader.class.php';
require_once 'htmlelements.class.php';

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
 * ressources-renderer
 *
 * @author hahn
 */
class ressourcesrenderer extends crawler_base {

    /**
     * searchindex
     * @var type 
     */
    protected $oCrawler = false;

    /**
     * ressource
     * @var type 
     */
    protected $oRes = false;
    

    /**
     * icons
     * @var type 
     */
    private $_aIcons = array(
        /*
        'url' => 'fa fa-link',
        'title' => 'fa fa-chevron-right',
        'description' => 'fa fa-chevron-right',
        'errorcount' => 'fa fa-bolt',
        'keywords' => 'fa fa-key',
        'lasterror' => 'fa fa-bolt',
        'actions' => 'fa fa-check',
        'searchset' => 'fa fa-cube',
        'query' => 'fa fa-search',
        'results' => 'fa fa-bullseye',
        'count' => 'fa fa-thumbs-o-up',
        'host' => 'fa fa-laptop',
        'ua' => 'fa fa-paw',
        'referrer' => 'fa fa-link',
        'id' => 'fa fa-hashtag',
        'ts' => 'fa fa-calendar',
        'ressourcetype' => 'fa fa-cubes',
        'type' => 'fa fa-cloud',
        'content_type' => 'fa fa-file-code-o',
        'http_code' => 'fa fa-retweet',
        'size_download' => 'fa fa-download',
        '_size_download' => 'fa fa-download',
        '_meta_total_time' => 'fa fa-clock-o',
         * 
         */
        
        // ressourcetype
        'audio'=>'far fa-file-sound',
        'css'=>'fas fa-eye-dropper',
        'image'=>'far fa-file-image',
        'link'=>'fas fa-link',
        'page'=>'far fa-sticky-note',
        // 'redirect'=>'fas fa-angle-double-right',
        'script'=>'far fa-file-code',

        // type
        'external'=>'fas fa-globe-americas',
        'internal'=>'fas fa-thumbtack',
        // content_type/ MIME
        //
        'link-to-url' => 'fas fa-external-link-alt',

        // http_code
        'http-code-' => 'far fa-hourglass',
        'http-code-0' => 'fas fa-plug',
        'http-code-2xx' => 'far fa-thumbs-up',
        'http-code-3xx' => 'fas fa-share',
        'http-code-4xx' => 'fas fa-bolt',
        'http-code-5xx' => 'fas fa-spinner',
        'http-code-9xx' => 'fas fa-bolt',

        /*
        'ressources.showtable' => 'fa fa-table',
        'ressources.showreport' => 'far fa-file',
        'ressources.ignorelimit' => 'fa fa-unlock',

        'button.close' => 'fa fa-close',
        'button.crawl' => 'fa fa-play',
        'button.delete' => 'fa fa-trash',
        'button.help' => 'fa fa-question-circle',
        'button.login' => 'fa fa-check',
        'button.logoff' => 'fa fa-power-off',
        'button.reindex' => 'fa fa-refresh',
        'button.search' => 'fa fa-search',
        'button.truncateindex' => 'fa fa-trash',
        'button.view' => 'fa fa-eye',
         * 
         */
        
        'ico.found' => 'fas fa-check',
        'ico.miss' => 'fas fa-ban',
        
        'ico.unknown' => 'fas fa-question-circle',
        'ico.httpv1' => 'fas fa-check',
        'ico.non-standard' => 'far fa-check-circle',
        'ico.security' => 'fas fa-lock',
        'ico.warn' => 'fas fa-exclamation-triangle',
        
        'ico.bookmarklet' => 'fas fa-expand-arrows-alt',
        'ico.redirect' => 'fas fa-share',
        'ico.filter'=>'fas fa-filter',
    );
    public $oHtml=false;

    // ----------------------------------------------------------------------
    // construct
    // ----------------------------------------------------------------------

    public function __construct($iSiteId = false) {
        $this->oHtml=new htmlelements();
        $this->setLangBackend();
        if ($iSiteId) {
            $this->_initRessource($iSiteId);
        }
        return true;
    }

    // ----------------------------------------------------------------------
    // private functions
    // ----------------------------------------------------------------------

    /**
     * init resource class and set the site id
     * @param type $iSiteId
     * @return boolean
     */
    private function _initRessource($iSiteId = false) {
        if (!$this->oRes) {
            $this->oRes = new ressources();
        }
        if (!$this->oCrawler) {
            $this->oCrawler = new crawler();
        }
        if ($iSiteId) {
            $this->oRes->setSiteId($iSiteId);
            $this->oCrawler->setSiteId($iSiteId);
        }
        return true;
    }

    private function _getIcon($sKey, $bEmptyIfMissing = false, $sClass=false) {
        if (array_key_exists($sKey."", $this->_aIcons)) {
            return '<i class="' . $this->_aIcons[$sKey] . ($sClass ? ' '.$sClass : '' ) .'"></i> ';
        }
        return $bEmptyIfMissing ? '' : '<span title="missing icon [' . $sKey . ']">[' . $sKey . ']</span>';
    }

    /**
     * get html code as script with $(document).ready() to init a datatable 
     * 
     * @example
     * <code>echo $oRenderer->renderInitDatatable('#' . $sTableId, array('lengthMenu'=>array(array(50, -1))))</code>
     * 
     * @param string  $sSelector   css selector as class or id
     * @param array   $aOptions    options
     * @return string
     */
    public function renderInitDatatable($sSelector='.datatable', $aOptions=array()){
        $aLength=array(10,25,50,100,-1);
        // echo __METHOD__ . '() DEBUG <pre>'.print_r($aOptions, 1).'</pre>';
        if(!isset($aOptions['lengthMenu'][0]) || !is_array($aOptions['lengthMenu'][0])){
            $aOptions['lengthMenu']=array($aLength);
        }
        if(!isset($aOptions['lengthMenu'][1])){
            $aOptions['lengthMenu'][1]=$aOptions['lengthMenu'][0];
            $iAll=array_search(-1, $aOptions['lengthMenu'][1]);
            if($iAll!==false){
                $aOptions['lengthMenu'][1][$iAll]='...';
            }
        }
        return "<script>"
        . "$(document).ready( "
            . "function () {\$('$sSelector').DataTable( ". json_encode($aOptions)." );} );"
        . "</script>";
    }
    /**
     * render a ressource value and add css class with given array key and
     * the array
     * 
     * @param string  $sKey    array key to render
     * @param array   $aArray  array
     * @return string
     */
    public function renderArrayValue($sKey, $aArray){
        if (array_key_exists($sKey, $aArray)){
            return $this->renderValue($sKey, $aArray[$sKey]);
        }
        return false;
    }

    /**
     * render a table to show http header
     * @param array $aHeaderWithChecks  array of header vars; user the return of [httpheader]->checkHeaders();
     *     [expires] => Array
     *        (
     *            [var] => expires
     *            [value] => Wed, 05 Sep 2018 19:24:03 GMT
     *            [found] => httpv1
     *            [bad] => 
     *        )
     *
     * @return string
     */
    public function renderHttpheaderAsTable($aHeaderWithChecks){
        if(!$aHeaderWithChecks ||!is_array($aHeaderWithChecks) || !count($aHeaderWithChecks)){
            return '';
        }
        $sReturn='';
        foreach($aHeaderWithChecks as $aEntry){
            $sReturn.='<tr title="'.htmlentities($aEntry['var'].': '.$aEntry['value']).'" '
                    . 'class="'.implode(' ', array_values($aEntry['tags'])).'"'
                    . '>'
                    . '<td>'.(strstr($aEntry['var'], '_') ? '' : $aEntry['var']) . '</td>'
                    . '<td style="max-width: 30em; overflow: hidden;">'.htmlentities($aEntry['value']).'</td>'
                    . '<td>'
                        . $this->_getIcon('ico.' . $aEntry['found'], false, 'ico-'.$aEntry['found']) 
                        . ($aEntry['bad'] ? $this->_getIcon('ico.warn', false, 'ico-warn') : '')
                    .'</td>'
                    . '<td>'. $this->lB('httpheader.varfound.'.$aEntry['found']) .'</td>'
                    // . '<td>'. print_r(array_values($aEntry['tags']),1) .'</td>'
                    . '</tr>'
                    ;
        }
        return '<table class="pure-table pure-table-horizontal">'
                . '<tr>'
                    . '<th>'.$this->lB('httpheader.thvariable').'</th>'
                    . '<th>'.$this->lB('httpheader.thvalue').'</th>'
                    . '<th></th>'
                    . '<th>'.$this->lB('httpheader.thcomment').'</th>'
                . '</tr>'
                . $sReturn
            . '</table>';
    }
    
    /**
     * get css classes for http status; it returns 2 classnames with
     * 100 block grouping and the exact code
     * 
     * @param integer $iHttpStatus
     * @param boolean $bOnlyFirst   optional flag: use grouped code "http-code-Nxx" without full statuscode; default: false (=show http code as number)
     * @return string
     */
    protected function _getCssClassesForHttpstatus($iHttpStatus, $bOnlyFirst=false){
        return 'http-code-'.floor((int)$iHttpStatus/100).'xx'.
                (!$bOnlyFirst ? ' http-code-'.(int)$iHttpStatus : '');
    }
    /**
     * render a ressource value and add css class
     * 
     * @param string  $sType  string
     * @param mixed   $value  value
     * @return string
     */
    public function renderValue($sType, $value) {

        $sIcon = $this->_getIcon($value, true);
        switch ($sType) {

            case 'http_code':
                if (!$sIcon) {
                    $sIcon = $this->_getIcon('http-code-' . floor((int)$value/100) . 'xx', true);
                }
                if (!$sIcon) {
                    $sIcon = $this->_getIcon('http-code-' . $value, true);
                }
                // $shttpStatusLabel=$this->lB('httpcode.'.$iHttp_code.'.label', 'httpcode.???.label');
                $shttpStatusDescr=$value.': '.$this->lB('httpcode.'.$value.'.descr', 'httpcode.???.descr')
                        .($this->lB('httpcode.'.$value.'.todo') ? "&#13;&#13;".$this->lB('httpcode.todo').":&#13;".$this->lB('httpcode.'.$value.'.todo') : '');
                $sReturn='<span class="http-code '.$this->_getCssClassesForHttpstatus($value).'" '
                        . 'title="'.$shttpStatusDescr.'"'
                        . '>'.$sIcon.$value.'</span>';
                break;

            case 'ressourcetype':
            case 'type':
                $sReturn = '<span class="' . $sType . ' ' . $sType . '-' . $value . '">' . $sIcon . $value . '</span>';
                break;
            case 'url':
                $sReturn = $sIcon . htmlentities($value);
                break;

            default:
                $sReturn = $sIcon . $value;
                break;
        }
        return $sReturn;
    }

    /**
     * render an value from the array by the given key
     * @param type $sKey
     * @param type $aArray
     * @return boolean
     */
    private function _renderArrayValue($sKey, $aArray) {
        if (array_key_exists($sKey, $aArray)) {
            return $this->renderValue($sKey, $aArray[$sKey]);
        }
        return false;
    }

    /**
     * render a few items from ressource item array as html table
     * @param array  $aItem       array of a single ressource item
     * @param array  $aArraykeys  optional: array keys to render (default: all)
     * @return strineg
     */
    private function _renderItemAsTable($aItem, $aArraykeys = false) {
        if (!$aArraykeys) {
            $aArraykeys = array_keys($aItem);
        }
        $sReturn = '';
        foreach ($aArraykeys as $sKey) {
            if (array_key_exists($sKey, $aItem)) {
                $sReturn.='<tr>'
                        . '<td>' . $this->_getIcon($sKey, true) . ' ' . $this->lB("db-ressources." . $sKey) . '</td>'
                        . '<td>' . $this->renderValue($sKey, $aItem[$sKey]) . '</td>'
                        . '</tr>';
            }
        }
        if ($sReturn) {
            return '<table class="pure-table pure-table-horizontal">'
                    . $sReturn
                    . '</table>';
        }
        return false;
    }

    /**
     * human readaably size by value in byte
     * 
     * @param integer  $iValue
     * @return string
     */
    public function hrSize($iValue) {
        $iOut = $iValue;
        foreach (array(
            $this->lB('hr-size-byte'),
            $this->lB('hr-size-kb'),
            $this->lB('hr-size-MB'),
            $this->lB('hr-size-GB'),
            $this->lB('hr-size-TB'),
            $this->lB('hr-size-PB'),
        ) as $sSuffix) {
            if ($iOut < 3000) {
                return round($iOut, 2) . ' ' . $sSuffix;
            }
            $iOut = $iOut / 1024;
        }
        return $iValue . ' (??)';
    }

    /**
     * human readaably age by value in unix ts
     * 
     * @param integer  $iUnixTs  unix timestamp
     * @return string
     */
    public function hrAge($iUnixTs) {
        if($iUnixTs<1){
            return $this->lB('hr-time-never');
        }
        return $this->hrTimeInSec(date("U") - $iUnixTs);
    }

    /**
     * human readaably time by value in seconds
     * 
     * @param integer  $iValue  value in seconds
     * @return string
     */
    public function hrTimeInSec($iValue) {
        $iOut = $iValue;
        if ($iOut < 180) {
            return $iOut . ' ' . $this->lB('hr-time-sec');
        }
        $iOut = $iOut / 60;
        if ($iOut < 180) {
            return (int) $iOut . ' ' . $this->lB('hr-time-min');
        }

        $iOut = $iOut / 60;
        if ($iOut < 72) {
            return (int) $iOut . ' ' . $this->lB('hr-time-h');
        }
        $iOut = $iOut / 24;
        if ($iOut < 366) {
            return (int) $iOut . ' ' . $this->lB('hr-time-d');
        }
        $iOut = $iOut / 365;
        return (int) $iOut . ' ' . $this->lB('hr-time-y');
    }

    // ----------------------------------------------------------------------
    // public rendering functions
    // ----------------------------------------------------------------------

    /**
     * render ressource with redirects in ressource report
     * @param array    $aRessourceItem  ressource item
     * @param integer  $iLevel          level
     * @return string
     */
    private function _renderWithRedirects($aRessourceItem, $iLevel = 1) {
        $iIdRessource=$aRessourceItem['id'];
        static $aUrllist;
        if ($iLevel===1){
            $aUrllist=array();
        }
        /*
        $iIdRessource=array_key_exists('id_ressource', $aRessourceItem)
                ? $aRessourceItem['id_ressource_to']
                : $aRessourceItem['id']
                ;
         * 
         */
        if (array_key_exists($iIdRessource, $aUrllist)){
            return $sReturn . ' <span class="error">'
                . sprintf($this->lB("linkchecker.loop-detected"), $aRessourceItem['url'])
                . '</span>'
                ;
        }
        $oStatus=new httpstatus($aRessourceItem['http_code']);
        $bIsRedirect=($aRessourceItem['http_code'] >= 300 && $aRessourceItem['http_code'] < 400);
        $sReturn = ''
                // . ' #'.$iIdRessource.' '.$iLevel.' '
                . ($iLevel===2 ? '<div class="redirects"><div class="redirectslabel">'.$this->lB('ressources.redirects-to').'</div>' : '')
                    . ($iLevel>2 ? '<div class="redirects">' : '')
                    . $this->renderRessourceItemAsLine($aRessourceItem, true, !$bIsRedirect)
                    . ($iLevel===2 ? '</div>' : '')
                . ($iLevel>2 ? '</div>' : '')
                // . ' ('.$aRessourceItem['http_code']
                ;
        $aUrllist[$iIdRessource]=true;
        if ($bIsRedirect) {
            // echo " scan sub elements of # $iIdRessource ...<br>";
            $aOutItem = $this->oRes->getRessourceDetailsOutgoing($iIdRessource);
            // $sReturn .= count($aOutItem) .  " sub elements ... recursion with <pre>" . print_r($aOutItem, 1) . "</pre><br>";
            if ($aOutItem && count($aOutItem)) {
                $iLevel++;
                // $sReturn .= str_repeat('&nbsp;&nbsp;&nbsp;', $iLevel++) . '&gt; ' . $this->_renderWithRedirects($aOutItem[0], $iLevel++);
                $sReturn .= '<div class="redirects">' . $this->_renderWithRedirects($aOutItem[0], $iLevel++) . '</div>';
            }
        }
        return $sReturn;
    }
    /**
     * render referencing (incoming) ressources report
     * @param array   $aRessourceItem  ressource item
     * @param boolean $bReinit         flag for deleting the url list (for multiple usage of this method on a page)
     * @return string
     */
    public function _renderIncomingWithRedirects($aRessourceItem, $bReInit=false) {
        $iIdRessource=$aRessourceItem['id'];
        static $aUrllist;
        if (!$aUrllist || $bReInit){
            $aUrllist=array();
        }
        $sReturn = '';

        if (array_key_exists($iIdRessource, $aUrllist)){
            return $sReturn . ' <span class="error">'
                . sprintf($this->lB("linkchecker.loop-detected"), $aRessourceItem['url'])
                . '</span>'
                ;
        }
        $aResIn=$this->oRes->getRessourceDetailsIncoming($aRessourceItem['id']);
        $aUrllist[$iIdRessource]=true;
        if(count($aResIn)){
            // $sReport.='|   |<br>';
            $sReturn.='<div class="references">'
                . '<div class="referenceslabel">'.sprintf($this->lB('ressources.referenced-in'), count($aResIn)).'</div>';
                foreach ($aResIn as $aInItem){
                    $sReturn.=$this->renderRessourceItemAsLine($aInItem, $aInItem['type']=='external');
                    if ($aInItem['type']=='external'){
                        $sReturn.=$this->_renderIncomingWithRedirects($aInItem);
                    }
                }
            $sReturn.='</div>';

        } else {
            // $sReturn.='<br>';
        }
        return $sReturn;
    }

    public function renderBookmarklet(){
        $sMyUrl = 'http'
                . ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]) ? 's' : '')
                . '://'
                . $_SERVER["HTTP_HOST"]
                . ':' . $_SERVER["SERVER_PORT"]
                . $_SERVER["SCRIPT_NAME"]
                    // tab=all + siteid=all
                    // TODO to switch to siteid later
                . '?page=checkurl&siteid=all&redirect=1&query='
                ;
        return 
            '<h3>'.$this->lB('bookmarklet.head').'</h3>'
            . $this->lB('bookmarklet.hint').':<br><br>'
            . $this->oHtml->getTag('a', array(
                    'class'=>'pure-button',
                    'href'=>'javascript:document.location.href=\''.$sMyUrl.'\'+encodeURI(document.location.href);',
                    'onclick'=>'alert(\''.$this->lB('bookmarklet.hint').'\'); return false;',
                    'title'=>$this->lB('bookmarklet.hint'),
                    'label'=>$this->_getIcon('ico.bookmarklet') . $this->lB('bookmarklet.label'),
              ))
            
            . '<br><br>'
            . $this->lB('bookmarklet.posthint')
            ;
    }
    public function renderMessagebox($sMessage, $sType=''){
        return '<div class="message message-'.$sType.'">'.$sMessage.'</div>';
    }
    /**
     * get html code for report item with redirects and and its references
     * 
     * @param integer  $iRessourceId    id of the ressource
     * @param boolean  $bShowIncoming   optional flag: show ressources that use the current ressource? default: true (=yes)
     * @param boolean  $bShowRedirects  optional flag: show redrirects? default: true (=yes)
     * @return string
     */
    public function renderReportForRessource($aRessourceItem, $bShowIncoming=true, $bShowRedirects=true) {
        $sReturn = '';
        $this->_initRessource();
        
        $sCssStatus=isset($aRessourceItem['http_code']) ? ' '.$this->_getCssClassesForHttpstatus($aRessourceItem['http_code']) : '';
        
        $sReturn.=$bShowRedirects
            ? $this->_renderWithRedirects($aRessourceItem)
            : $this->renderRessourceItemAsLine($aRessourceItem, true)
        ;
        if ($bShowIncoming) {
            $sReturn.=$this->_renderIncomingWithRedirects($aRessourceItem, $bShowIncoming, $bShowRedirects);
        }
        // return $sReturn;
        return '<div class="divRessourceReport '.$sCssStatus.'">'. $sReturn . '</div>';
    }

    /**
     * get html code for infobox with a single ressource given by id
     * 
     * @param integer  $iRessourceId  id of the ressource
     * @return string
     */
    public function renderRessourceId($iRessourceId) {
        $iId = (int) $iRessourceId;
        if (!$iId) {
            return false;
        }
        $this->_initRessource();
        $aResourceItem = $this->oRes->getRessourceDetails($iId);
        return $this->renderRessourceItemAsBox($aResourceItem);
    }

    private function _extendRessourceItem($aRessourceItem) {

        if (array_key_exists('size_download', $aRessourceItem)) {
            $aRessourceItem['_size_download'] = $aRessourceItem['size_download']
                    ? $this->hrSize($aRessourceItem['size_download'])
                    : $this->lB('ressources.size-is-zero');
        }
        if (array_key_exists('total_time', $aRessourceItem) && $aRessourceItem['total_time']) {
            $aRessourceItem['_dlspeed'] = $this->hrSize($aRessourceItem['size_download'] / $aRessourceItem['total_time']) . '/ sec';
        }
        if (array_key_exists('total_time', $aRessourceItem) && $aRessourceItem['total_time']) {
            $aRessourceItem['_dlspeed'] = $this->hrSize($aRessourceItem['size_download'] / $aRessourceItem['total_time']) . '/ sec';
        }

        // add head metadata
        $aResponsemetadata= json_decode($aRessourceItem['header'], 1);
        foreach(array('total_time', 'namelookup_time', 'connect_time', 'pretransfer_time', 'starttransfer_time', 'redirect_time') as $sKey){
            if ($aResponsemetadata && is_array($aResponsemetadata) && array_key_exists($sKey, $aResponsemetadata)) {
                $aRessourceItem['_meta_'.$sKey]=$aResponsemetadata[$sKey];
            }
        }
        return $aRessourceItem;
    }

    /**
     * get html code for infobox with a single ressource given by arraydata
     * 
     * @param array  $aRessourceItem  array of the ressource item
     * @return string
     */
    public function renderRessourceItemAsBox($aRessourceItem) {
        $sReturn = '';
        if (!is_array($aRessourceItem) || !count($aRessourceItem) || !array_key_exists('ressourcetype', $aRessourceItem)) {
            return false;
        }
        $aRessourceItem = $this->_extendRessourceItem($aRessourceItem);

        $unixTS = date("U", strtotime($aRessourceItem['ts']));


        $sReturn.='<div class="divRessource">'
                . '<div class="divRessourceHead">'
                    . '<span style="float: right;">'
                        . '<a href="' . $aRessourceItem['url'] . '" target="_blank" class="pure-button button-secondary" title="'.$this->lB('ressources.link-to-url').'">'
                        . $this->_getIcon('link-to-url')
                        . '</a>'
                    . '</span>'
                    . $this->_renderArrayValue('type', $aRessourceItem)
                    . ' '
                    . $this->_renderArrayValue('ressourcetype', $aRessourceItem)
                    . '<br>'
                    . '<strong>'. str_replace('&', '&shy;&',htmlentities($this->_renderArrayValue('url', $aRessourceItem))).'</strong>'
                . '</div>'
                . '<div class="divRessourceContent">'
                . $this->lB('ressources.age-scan') . ': ' . $this->hrAge($unixTS) . '<br><br>'
                
        ;

        $sReturn.=$this->_renderItemAsTable($aRessourceItem, array(
            'id',
            'http_code',
            'type',
            'ressourcetype',
            'content_type',
            '_size_download',
            'ts',
            '_meta_total_time', 
        ))
        ;
        
        $aHeaderJson=json_decode($aRessourceItem['header'] ? $aRessourceItem['header'] : $aRessourceItem['lasterror'], 1);
        if($aHeaderJson && $aHeaderJson['_responseheader']){
            
            $oHttpheader=new httpheader();
            $aHeader=$oHttpheader->setHeaderAsString(is_array($aHeaderJson['_responseheader']) ? $aHeaderJson['_responseheader'][0] : $aHeaderJson['_responseheader']);
            // . $oRenderer->renderHttpheaderAsTable($oHttpheader->checkHeaders());
            $sReturn.='<br>'
                    . '<br>'
                    . '<strong>'.$this->lB('httpheader.data').'</strong><br><br>'
                    .$this->renderHttpheaderAsTable($oHttpheader->parseHeaders());
            
        }
        /*
        $sReturn.=$this->_renderItemAsTable($aRessourceItem, array(
            '_meta_total_time', 
            '_meta_namelookup_time', 
            '_meta_connect_time', 
            '_meta_pretransfer_time', 
            '_meta_starttransfer_time', 
            '_meta_redirect_time', 
                // '_dlspeed'
        ));
         */
                

        if ($aRessourceItem['errorcount']) {
            $aJson = json_decode($aRessourceItem['lasterror'], true);
            $sReturn.=$this->lB('error')
                    . '<pre>' . print_r($aJson, 1) . '</pre>'
                    ;
        }

        $sReturn.='</div>'
                . '</div>';

        // $sReturn.='<pre>ressource id #'.$aRessourceItem['id'].'<br>'.print_r($aRessourceItem, 1).'</pre>';

        return $sReturn;
    }

    /**
     * render a ressource as a line (for reporting)
     * @param array    $aResourceItem    array of ressurce item
     * @param boolean  $bShowHttpstatus  flasg: show http code? default: false (=no)
     * @param boolean  $bUseLast         add css class "last" to highlight it? default; flase (=no)
     * @return boolean
     */
    public function renderRessourceItemAsLine($aResourceItem, $bShowHttpstatus = false, $bUseLast=false) {
        $sReturn = '';
        if (!is_array($aResourceItem) || !count($aResourceItem) || !array_key_exists('ressourcetype', $aResourceItem)) {
            return false;
        }
        return '<div class="divRessourceAsLine'.($bUseLast ? ' last last-'.$this->_getCssClassesForHttpstatus($aResourceItem['http_code'], true) : '').'">'
                . ' <span style="float: right; font-size: 70%;"><a href="' . $aResourceItem['url'] . '" class="pure-button" style="" title="'.$this->lB('ressources.link-to-url').'" target="_blank">'.$this->_getIcon('link-to-url').'</a></span>'
                . ($bShowHttpstatus ? ' ' . $this->_renderArrayValue('http_code', $aResourceItem) : '')
                . ' ' . $this->_renderArrayValue('type', $aResourceItem)
                . ' ' . $this->_renderArrayValue('ressourcetype', $aResourceItem)
                . ' <a href="?page=ressourcedetail&id=' . $aResourceItem['id'] . '&siteid='.$aResourceItem['siteid'].'" title="'.$this->lB('ressources.link-to-details').'">' . htmlentities($aResourceItem['url']) . '</a>'
                /*
                . (isset($aResourceItem['isExternalRedirect']) && $aResourceItem['isExternalRedirect'] 
                        ? ' <span class="redirect"><nobr>' . $this->_getIcon('ico.redirect') . $this->lB('ressources.link-is-external-redirect') . '</nobr></span>' 
                        : '')
                 * 
                 */
            . '<div style="clear: both;"></div>'
            . '</div>'
            // . print_r($aResourceItem, 1)
            ;
    }

    /**
     * helper function for vis js
     * @param array  $aItem    ressource item
     * @param string $sNodeId  optional id for the node (default is id in ressource item)
     * @return array
     */
    private function _getVisNode($aItem, $sNodeId=''){
        $sNodeLabel=$aItem['url']."\n(".$aItem['type'].' '.$aItem['ressourcetype'].'; '.$aItem['http_code'].')';
        $sNodeId=$sNodeId ? $sNodeId : $aItem['id'];
        return array(
            'id'=>$sNodeId, 
            // 'label'=>$this->renderRessourceItemAsLine($aItem),
            'label'=>$sNodeLabel,
            'group'=>$aItem['ressourcetype'],
            'title'=>$sNodeLabel,
        );
    }
    /**
     * helper function for vis js
     * @param array  $aItem    ressource item
     * @param string $sNodeId  optional id for the node (default is id in ressource item)
     * @return array
     */
    private function _getVisEdge($aOptions){
        $aColors=array(
            'in'=>'#99bb99',
            'out'=>'#9999bb',
        );
        foreach (array('from', 'to') as $sMustKey){
            if (!array_key_exists($sMustKey, $aOptions)){
                echo __METHOD__ . ' WARNING: no '.$sMustKey.' in option array<br>';
                return false;
            }
        }
        $aReturn=array(
                    'from'=>$aOptions['from'],
                    'to'=>$aOptions['to'], 
        );
        
        if(array_key_exists('color', $aOptions) && array_key_exists($aOptions['color'], $aColors)){
            $aOptions['color']=$aColors[$aOptions['color']];
        }
        foreach (array('arrows', 'title', 'color') as $sKey){
            if (array_key_exists($sKey, $aOptions)){
                $aReturn[$sKey]=$aOptions[$sKey];
            }
        }
        return $aReturn;
    }
    
    
        // visualization
        // https://cdnjs.cloudflare.com/ajax/libs/vis/4.20.1/vis.min.js
        // https://cdnjs.cloudflare.com/ajax/libs/vis/4.20.1/vis.min.css

    /**
     * 
     * @param type $aNodes
     * @param type $aEdges
     * @return string
     */
    private function _renderNetwork($aNodes, $aEdges){
        $sIdDiv='visarea';
        $sVisual=''
            . '<!-- for header -->'
            . '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.20.1/vis.min.js"></script>'
            . '<link href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.20.1/vis.min.css" rel="stylesheet" type="text/css" />'
                . '  <style>
                    #'.$sIdDiv.'{
                      height: 500px;
                      width: 60%;
                      border:1px solid lightgrey;
                    }
                </style>'
            . '<!-- for body -->'
                . '<div id="'.$sIdDiv.'"></div>'
                . '<script language="JavaScript">
                    var iIconSize=120;
                    var optionsFA = {
                      groups: {
                        css: {
                          shape: \'icon\',
                          icon: {
                            face: \'FontAwesome\',
                            code: \'\uf15c\',
                            size: iIconSize,
                            color: \'#cccccc\'
                          }
                        },
                        image: {
                          shape: \'icon\',
                          icon: {
                            face: \'FontAwesome\',
                            code: \'\uf1c5\',
                            size: iIconSize,
                            color: \'#eecc22\'
                          }
                        },
                        link: {
                          shape: \'icon\',
                          icon: {
                            face: \'FontAwesome\',
                            code: \'\uf0c1\',
                            size: iIconSize,
                            color: \'#888888\'
                          }
                        },
                        page: {
                          shape: \'box\',
                          shape: \'icon\',
                          color: {background:\'pink\', border:\'purple\'},
                          icon: {
                            face: \'FontAwesome\',
                            code: \'\uf15b\',
                            size: iIconSize,
                            shape: \'box\'
                          }
                        },
                        script: {
                          shape: \'icon\',
                          icon: {
                            face: \'FontAwesome\',
                            code: \'\uf1c9\',
                            size: iIconSize,
                            color: \'#88cccc\'
                          }
                        }
                      },
                      layout: {
                          hierarchical: {
                              direction: "LR",
                              sortMethod: "directed",

                              levelSeparation: 500,
                              nodeSpacing: 200,

                          }
                      },
                      interaction: {dragNodes :false},
                      physics: {
                          enabled: false
                      },
                      configure1: {
                        showButton:false,
                        filter: function (option, path) {
                            if (path.indexOf(\'hierarchical\') !== -1) {
                                return true;
                            }
                            return false;
                        }
                      }
                    };

                    // create a network
                    var containerFA = document.getElementById(\''.$sIdDiv.'\');
                    var dataFA = {
                      nodes: '. json_encode($aNodes).',
                      edges: '. json_encode($aEdges).'
                    };

                    var networkFA = new vis.Network(containerFA, dataFA, optionsFA);

                    networkFA.on("click", function (params) {
                        params.event = "[original event]";
                        // console.log(\'click event, getNodeAt returns: \' + this.getNodeAt(params.pointer.DOM));
                        console.log(\'click event - params: \' + params);
                    });      
                </script>';
        return $sVisual;
    }
    
    /**
     * get html code for a list of ressource items and an added http-status group filter 
     * used in renderRessourceItemFull()
     * 
     * @staticvar int $iListcounter
     * @param array    $aItemlist       array of ressource items to display
     * @param boolean  $bShowIncoming   optional flag: show ressources that use the current ressource? default: true (=yes)
     * @param boolean  $bShowRedirects  optional flag: show redrirects? default: true (=yes)
     * @return string
     */
    protected function _renderRessourceListWithGroups($aItemlist, $bShowIncoming=true, $bShowRedirects=true){
        $sReturn=$this->lB('ressources.total'). ': <strong>' . count($aItemlist) . '</strong>';
        if(!count($aItemlist)){
            return $sReturn;
        }
        static $iListcounter;
        if(!isset($iListcounter)){
            $iListcounter=0;
        }
        $iListcounter++;
        
        $oHttp=new httpstatus();
        $sDivClass='resitemout'.$iListcounter;
        $iReportCounter=1;
        $sFilter='';
        $sOut='';
        $aHttpStatus=array();
        $aTypes=array();
        foreach ($aItemlist as $aTmpItem) {
            $oHttp->setHttpcode($aTmpItem['http_code']);
            $sHttpStatusgroup=$oHttp->getStatus();
            $sRestype=$aTmpItem['ressourcetype'];
            $aHttpStatus[$sHttpStatusgroup]=(isset($aHttpStatus[$sHttpStatusgroup])) ? $aHttpStatus[$sHttpStatusgroup]+1 : 1;
            $aTypes[$sRestype]=(isset($aTypes[$sRestype])) ? $aTypes[$sRestype]+1 : 1;

            $sOut.='<div class="'.$sDivClass.' group-'.$sHttpStatusgroup.' restype-'.$sRestype.'">'
                    . '<div class="counter">'. $iReportCounter++.'</div>'.$this->renderReportForRessource($aTmpItem, $bShowIncoming, $bShowRedirects)
                    . '</div>'
                    ;
        }
        if(count($aHttpStatus)>0){
            ksort($aHttpStatus);
            foreach($aHttpStatus as $sHttpStatusgroup=>$iStatusCount){
                $sCss='http-code-'.implode(' http-code-',explode('-', $sHttpStatusgroup));
                $sFilter.=''
                        . '<a href="#" class="pure-button '.$sCss.'" '
                        . 'onclick="$(this).toggleClass(\''.$sCss.'\'); $(\'div.'.$sDivClass.'.group-'.$sHttpStatusgroup.'\').toggle(); return false;"'
                        . '><strong>'.$iStatusCount . '</strong> x ' .$this->lB('http-status-group-'.$sHttpStatusgroup).'</a>'
                        . ' '
                        ;
            }
            /*
             * only useful with a tab control
             * 
            $sFilter.=$sFilter ? ' ... ' : '';
            foreach($aTypes as $sTypegroup=>$iStatusCount){
                $sCss='restype-'.implode(' restype-',explode('-', $sTypegroup));
                $sFilter.=''
                        . '<a href="#" class="pure-button button-secondary '.$sCss.'" '
                        . 'onclick="$(this).toggleClass(\'button-secondary\'); $(\'div.'.$sDivClass.'.restype-'.$sTypegroup.'\').toggle(); return false;"'
                        . '><strong>'.$iStatusCount . '</strong> x ' .$sTypegroup.'</a>'
                        . ' '
                        ;
            }
             * 
             */
            $sFilter='<div class="actionbox">'
                . $this->_getIcon('ico.filter')
                . $this->lB('ressources.filter').'<br>'
                . $this->lB('ressources.filter-httpgroups').'<br>'
                .'<br>'
                .$sFilter
                .'</div><br>';
        }
        return $sReturn.'<br><br>'.$sFilter.$sOut;
    }    
    /**
     * get html code for full detail of a ressource with properties, in and outs
     * @param array $aItem  ressource item
     * @return string
     */
    public function renderRessourceItemFull($aItem) {
        $sReturn = '';
        $iId = $aItem['id'];
        $aResponsemetadata= json_decode($aItem['header'], 1);
        $aIn = $this->oRes->getRessourceDetailsIncoming($iId);
        $aOut = $this->oRes->getRessourceDetailsOutgoing($iId);
        
        /*
         
        // data mapping into JS to visualize a map
         
        $aNodes=array();
        $aEdges=array();
        
        $sNodeLabel=$aItem['url']."\n(".$aItem['type'].' '.$aItem['ressourcetype'].'; '.$aItem['http_code'].')';
        $aNodes[]=$this->_getVisNode($aItem);
        if (count($aIn)){
            foreach ($aIn as $aTmpItem) {
                $sNodeId=$aTmpItem['id'].'IN';
                $aNodes[]=$this->_getVisNode($aTmpItem,$sNodeId);
                $aEdges[]=$this->_getVisEdge(array(
                    'from'=>$sNodeId,
                    'to'=>$aNodes[0]['id'], 
                    'arrows'=>'to',
                    'color' => 'in',
                ));
            }
        }
        if (count($aOut)){
            foreach ($aOut as $aTmpItem) {
                $sNodeId=$aTmpItem['id'].'OUT';
                $sNodeLabel=$aTmpItem['url']."\n(".$aTmpItem['type'].' '.$aTmpItem['ressourcetype'].')';
                $aNodes[]=$this->_getVisNode($aTmpItem,$sNodeId);
                $aEdges[]=$this->_getVisEdge(array(
                    'from'=>$aNodes[0]['id'],
                    'to'=>$sNodeId, 
                    'arrows'=>'to',
                    'color' => 'out',
                ));
            }
        }
        */

        // --------------------------------------------------
        // table on top
        // --------------------------------------------------
        $sReturn.=''
                . '<table><tr>'
                    . '<td style="vertical-align: top; text-align: center; padding: 0 1em;">'
                        . $this->lB('ressources.references-in') . '<br>'
                        . '<span class="ressourcecounter"><a href="#listIn">' . count($aIn) . '<br><i class="fa fa-arrow-right"></i></a></span>'
                    . '</td>'
                    . '<td>'
                        . $this->renderRessourceItemAsBox($aItem)
                    . '</td>'
                        . '<td style="vertical-align: top; text-align: center; padding: 0 1em;">'
                        . $this->lB('ressources.references-out') . '<br>'
                        . '<span class="ressourcecounter"><a href="#listOut">' . count($aOut) . '<br><i class="fa fa-arrow-right"></i></a></span>'
                    . '</td>'
                . '</tr></table>'
                // . $this->_renderNetwork($aNodes, $aEdges)
                ;
        

        // --------------------------------------------------
        // wher it is linked
        // --------------------------------------------------
        $sReturn.='<h3 id="listIn">' . sprintf($this->lB('ressources.references-h3-in'), count($aIn)) . '</h3>'
                . $this->_renderRessourceListWithGroups($aIn, false, false)
        ;
        // --------------------------------------------------
        // outgoing links / redirects
        // --------------------------------------------------
        $sReturn.='<h3 id="listOut">' . sprintf($this->lB('ressources.references-h3-out'), count($aOut)) . '</h3>'
                . $this->_renderRessourceListWithGroups($aOut,false, true)
        ;
        return $sReturn;
    }

    public function renderRessourceStatus(){
        // $iRessourcesCount=$this->oDB->count('ressources',array('siteid'=>$this->iSiteId));
        $this->_initRessource();
        $iRessourcesCount=$this->oRes->getCount();
        $iExternal=$this->oRes->getCount(array('siteid'=>$this->oRes->iSiteId,'isExternalRedirect'=>'1'));
        
        $dateLast=$this->oRes->getLastRecord();
        $sTiles = ''
            . $this->renderTile('',            $this->lB('ressources.age-scan'), $this->hrAge(date("U", strtotime($dateLast))), $dateLast, '')
            . $this->renderTile('',            $this->lB('ressources.itemstotal'), $iRessourcesCount, '', '')
            . $this->renderTile('',            $this->lB('linkchecker.found-http-external'), $iExternal, '', '')
            ;
        
        return ''
                . $this->renderTileBar($sTiles)
                ;
    }
    
    /**
     * render an icon and a prefix
     * @param type $sType
     * @return type
     */
    public function renderShortInfo($sType){
        return $this->_getIcon('ico.'.$sType, false, 'ico-'.$sType);
    }
    
    
    /**
     * get html code to draw a tile
     * 
     * @param string $sType       type; one of '' |'ok'|'error'
     * @param strng  $sIntro      top text
     * @param string $sCount      counter value
     * @param string $sFoot       footer text
     * @param string $sTargetUrl  linked url
     * @return string
     */
    public function renderTile($sType, $sIntro, $sCount, $sFoot=false, $sTargetUrl=false){
        return '<li>'
            . $this->oHtml->getTag('a', array(
                'href'=>($sTargetUrl ? $sTargetUrl : '#" onclick="return false;'),
                'class'=>'tile '.$sType.' scroll-link '.($sTargetUrl ? '' : 'nonclickable'),
                'label'=> $sIntro
                    . (strstr($sIntro, '<br>') ? '' : '<br>')
                    . '<br>'
                    . '<strong>'.$sCount.'</strong><br>'
                    . $sFoot
            ))
            . '</li>';
    }
    
    /**
     * get html code to wrap all tiles 
     * @see $this->renderTile() to generate the necessary items.
     * 
     * @param string $sTiles      html code with tiles
     * @param string $sType       default type of all tilea; one of '' |'ok'|'error'|'warn'
     * @return string
     */
    public function renderTileBar($sTiles, $sType=''){
        return '<ul class="tiles '.$sType.'">'.$sTiles.'</ul>';
    }
    
    /**
     * get html code to show a toggable content box
     * 
     * @staticvar int $iToggleCounter  counter of toggled box on a page
     * 
     * @param string   $sHeader    clickable header text
     * @param string   $sContent   content
     * @param boolean  $bIsOpen    flag: open box by default? default: false (=closed content)
     * @return string
     */
    public function renderToggledContent($sHeader,$sContent, $bIsOpen=false){
        static $iToggleCounter;
        if(!isset($iToggleCounter)){
            $iToggleCounter=0;
        }
        $iToggleCounter++;
        $sDivId='div-toggle-'.$iToggleCounter;
        return ''
            . '<div class="div-toggle-head">'
                . $this->oHtml->getTag('a', array(
                    'href'=>'#',
                    'class'=>($bIsOpen ? 'open' : ''),
                    'onclick'=>'$(\'#'.$sDivId.'\').slideToggle(); $(this).toggleClass(\'open\'); return false;',
                    'label'=>$sHeader,
                ))
            . '</div>'
            . '<div'.($bIsOpen ? '' : ' style="display:none;"').' id="'.$sDivId.'" class="div-toggle">'
                . $sContent
            . '</div>'
            ;
        
    }
}
