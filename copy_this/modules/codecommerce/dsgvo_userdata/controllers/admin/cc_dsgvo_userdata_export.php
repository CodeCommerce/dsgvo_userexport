<?php
/**
 * Copyright (c) 2018
 * CodeCommerce - Christopher Bauer
 * www.codecommerce.de
 */

class cc_dsgvo_userdata_export extends oxAdminView
{

    /**
     * @var string
     * oxuser.oxid for user to export
     */
    protected $sUserId;

    protected $_sThisTemplate = 'cc_dsgvo_userdata_export.tpl';

    /**
     * render function
     * @return string
     */
    public function render()
    {
        parent::render();

        return $this->_sThisTemplate;
    }

    /**
     * generate export file for userdata
     */
    public function exportUserData()
    {
        if (!$this->sUserId = $this->getEditObjectId()) {
            return FALSE;
        }

        $sFilePath = $this->getUserExportPath();
        $f = fopen($sFilePath, "w+");
        if ($f) {
            $aData = [];
            $aData['oxuser'] = $this->getOxUser();
            $aData['oxnewssubscribed'] = $this->getOxNewslettersubscribed();
            $aData['oxaddress'] = $this->getOxaddress();
            $aData['oxorder'] = $this->getOxOrder();
            $aData['noticelist'] = $this->getOxNoticelist();
            $aData['wishlist'] = $this->getOxWishlist();
            $aData['oxrecommlists'] = $this->getOxrecommlist();
            $aData['oxorderfiles'] = $this->getOxorderfiles();
            $aData['oxuserbasketitems'] = $this->getStoresBasketItems();
            $aData['oxratings'] = $this->getOxratings();
            $aData['oxreviews'] = $this->getOxreviews();
            $aData['oxinvitations'] = $this->getOxinvitations();
            $aData['oxpricealarm'] = $this->getOxpricealarm();
            $aData['oxvouchers'] = $this->getOxvouchers();
            $aData['oxpayments'] = $this->getUserPayments();
            $aData['oxacceptedterms'] = $this->getOxacceptedterms();
            $aData = array_merge($aData, $this->externalDataHook());

            fwrite($f, json_encode($aData));

            fclose($f);
            $this->addTplParam('sFilePath', "../export/userexport/" . $this->getUserExportFilename());

            if (oxRegistry::getConfig()->getRequestParameter('sendUserMail')) {
                $this->sendUserEmail();
            }
        }

        return FALSE;
    }

    /**
     * Place your external functions here
     * @return array
     */
    public function externalDataHook()
    {
        return [];
    }
    
    /**
     * sends mail with attached export file
     */
    protected function sendUserEmail()
    {
        $this->addTplParam('blSend', TRUE);
        if (!$this->sUserId) {
            $this->addTplParam('blUserMailSend', FALSE);

            return FALSE;
        }

        $oUser = oxNew('oxUser');
        if ($oUser->load($this->sUserId)) {
            $oEmail = oxNew('oxEmail');

            if ($oEmail->sendUserDataMail($this->getUserExportPath(), $oUser))
                $this->addTplParam('blUserMailSend', TRUE);
            else
                $this->addTplParam('blUserMailSend', FALSE);
        }

        return FALSE;
    }

    /**
     * delete export file from filesystem
     * @return bool
     */
    public function delete()
    {
        if (!$this->sUserId = $this->getEditObjectId())
            return FALSE;

        $sFile = $this->getUserExportPath();
        if (file_exists($sFile)) {
            unlink($sFile);
            $this->addTplParam('blFileRemoved', TRUE);
        }
    }

    /**
     * get Path to exported File
     * @return bool|string
     */
    public function getUserExportPath()
    {
        $sDirPath = getShopBasePath() . 'export/userexport/';
        if (!is_dir($sDirPath))
            mkdir($sDirPath, 0755, TRUE);
        if ($this->sUserId)
            return $sDirPath . $this->getUserExportFilename();

        return FALSE;
    }

    public function getUserExportFilename()
    {
        return "userdata_" . substr($this->sUserId, 0, 8) . ".json";
    }

    /**
     * get Infos for oxuser database
     * @return false|string
     * @throws oxConnectionException
     */
    public function getOxUser()
    {
        /**
         * Data from oxuser
         * We are skipping (add them if needed):
         * `OXID`, `OXRIGHTS`, `OXPASSWORD`, `OXPASSSALT`
         */
        $sSql = "SELECT `OXACTIVE`, `OXSHOPID`, IFNULL((SELECT `OXNAME` FROM `oxshops` WHERE `OXID`=`OXSHOPID`), '') AS OXSHOPNAME, `OXUSERNAME`, `OXCUSTNR`, `OXUSTID`, `OXCOMPANY`, `OXFNAME`, `OXLNAME`, `OXSTREET`, `OXSTREETNR`, `OXADDINFO`, `OXCITY`, `OXCOUNTRYID`, IFNULL((SELECT `OXTITLE` FROM `oxcountry` WHERE `OXID`=`OXCOUNTRYID`), '') AS OXCOUNTRYNAME, `OXSTATEID`, IFNULL((SELECT `OXTITLE` FROM `oxstates` WHERE `OXID`=`OXSTATEID`), '') AS OXSTATENAME, `OXZIP`, `OXFON`, `OXFAX`, `OXSAL`, `OXBONI`, `OXCREATE`, `OXREGISTER`, `OXPRIVFON`, `OXMOBFON`, `OXBIRTHDATE`, `OXURL`, `OXUPDATEKEY`, `OXUPDATEEXP`, `OXPOINTS`, `OXTIMESTAMP` FROM `oxuser` WHERE `OXID`= '{$this->sUserId}'";

        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes[0];

        return FALSE;
    }

    /**
     * Data from oxnewssubscribed
     * @return bool
     * @throws oxConnectionException
     */
    public function getOxNewslettersubscribed()
    {
        /**
         * Newsletter status
         */
        $sSql = "SELECT IF(`OXDBOPTIN`>0,'Subscribed','Unsubscribed') AS OXNEWSLETTERSTATUS FROM `oxnewssubscribed` WHERE `OXUSERID`= '{$this->sUserId}';";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes[0];

        return FALSE;
    }

    /**
     * get data from oxaddress
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxaddress()
    {
        /**
         * Stored additional delivery addresses for this user
         * We are skipping (add them if needed):
         * `OXID`, `OXUSERID`, `OXADDRESSUSERID`
         */
        $sSql = "SELECT `OXCOMPANY`, `OXFNAME`, `OXLNAME`, `OXSTREET`, `OXSTREETNR`, `OXADDINFO`, `OXCITY`, `OXCOUNTRY`, `OXCOUNTRYID`, `OXSTATEID`, IFNULL((SELECT `OXTITLE` FROM `oxstates` WHERE `OXID`=`OXSTATEID`), '') AS OXSTATENAME, `OXZIP`, `OXFON`, `OXFAX`, `OXSAL`, `OXTIMESTAMP` FROM `oxaddress` WHERE `OXUSERID`= '{$this->sUserId}' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get all information from oxorder and oxorderarticles
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxOrder()
    {
        $sSql = "SELECT `OXSHOPID`, IFNULL((SELECT `OXNAME` FROM `oxshops` WHERE `OXID`=`OXSHOPID`), '') AS OXSHOPNAME,  `OXORDERDATE`, `OXORDERNR`, `OXBILLCOMPANY`, `OXBILLEMAIL`, `OXBILLFNAME`, `OXBILLLNAME`, `OXBILLSTREET`, `OXBILLSTREETNR`, `OXBILLADDINFO`, `OXBILLUSTID`, `OXBILLCITY`, `OXBILLCOUNTRYID`, IFNULL((SELECT `OXTITLE` FROM `oxcountry` WHERE `OXID`=`OXBILLCOUNTRYID`), '') AS OXBILLCOUNTRYNAME, `OXBILLSTATEID`, IFNULL((SELECT `OXTITLE` FROM `oxstates` WHERE `OXID`=`OXBILLSTATEID`), '') AS OXBILLSTATENAME, `OXBILLZIP`, `OXBILLFON`, `OXBILLFAX`, `OXBILLSAL`, `OXDELCOMPANY`, `OXDELFNAME`, `OXDELLNAME`, `OXDELSTREET`, `OXDELSTREETNR`, `OXDELADDINFO`, `OXDELCITY`, `OXDELCOUNTRYID`, IFNULL((SELECT `OXTITLE` FROM `oxcountry` WHERE `OXID`=`OXDELCOUNTRYID`), '') AS OXDELCOUNTRYNAME, `OXDELSTATEID`, IFNULL((SELECT `OXTITLE` FROM `oxstates` WHERE `OXID`=`OXDELSTATEID`), '') AS OXDELSTATENAME, `OXDELZIP`, `OXDELFON`, `OXDELFAX`, `OXDELSAL`, `OXPAYMENTTYPE`, `OXTOTALNETSUM`, `OXTOTALBRUTSUM`, `OXTOTALORDERSUM`, `OXARTVAT1`, `OXARTVATPRICE1`, `OXARTVAT2`, `OXARTVATPRICE2`, `OXDELCOST`, `OXDELVAT`, `OXPAYCOST`, `OXPAYVAT`, `OXWRAPCOST`, `OXWRAPVAT`, `OXGIFTCARDCOST`, `OXGIFTCARDVAT`, `OXCARDID`, `OXCARDTEXT`, `OXDISCOUNT`, `OXEXPORT`, `OXBILLNR`, `OXBILLDATE`, `OXTRACKCODE`, `OXSENDDATE`, `OXREMARK`, `OXVOUCHERDISCOUNT`, `OXCURRENCY`, `OXCURRATE`, `OXFOLDER`, `OXTRANSID`, `OXPAYID`, `OXXID`, `OXPAID`, `OXSTORNO`, `OXIP`, `OXTRANSSTATUS`, `OXLANG`, `OXINVOICENR`, `OXDELTYPE`, `OXTIMESTAMP`, `OXISNETTOMODE`, (SELECT GROUP_CONCAT(CONCAT(`OXARTNUM`,'~',`OXTITLE`,'~',`OXAMOUNT`,'~',`OXBRUTPRICE`) SEPARATOR '%') FROM `oxorderarticles` WHERE `OXORDERID`=`oxorder`.`OXID`) AS OXARTICLESINORDER FROM `oxorder` WHERE `OXUSERID`= '{$this->sUserId}' ORDER BY `OXORDERDATE`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get wishlist data from oxuserbaskets
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxNoticelist()
    {
        /*
         * Stored wishlist data for this user
         * We are skipping (add them if needed):
         * `OXID`, `OXUSERID`, `OXTITLE`
         *
         * We are aggregating the articles of the wishlist in OXARTICLESINWISHLIST from two tables with separators as follows:
         * % to separate one article from another
         * ~ to separate the article fields from one another
         *
         * From the table oxuserbasketitems we are skipping (add them if needed):
         * `OXID`, `OXBASKETID`, `OXARTID`, `OXSELLIST`, `OXPERSPARAM`
         *
         * From the table oxarticles we are skipping (add them if needed):
         * `OXID`, `OXSHOPID`, `OXPARENTID`, `OXACTIVE`, `OXHIDDEN`, `OXACTIVEFROM`, `OXACTIVETO`, `OXEAN`, `OXDISTEAN`, `OXMPN`, `OXSHORTDESC`, `OXPRICE`, `OXBLFIXEDPRICE`, `OXPRICEA`, `OXPRICEB`, `OXPRICEC`, `OXBPRICE`, `OXTPRICE`, `OXUNITNAME`, `OXUNITQUANTITY`, `OXEXTURL`, `OXURLDESC`, `OXURLIMG`, `OXVAT`, `OXTHUMB`, `OXICON`, `OXPIC1`, `OXPIC2`, `OXPIC3`, `OXPIC4`, `OXPIC5`, `OXPIC6`, `OXPIC7`, `OXPIC8`, `OXPIC9`, `OXPIC10`, `OXPIC11`, `OXPIC12`, `OXWEIGHT`, `OXSTOCK`, `OXSTOCKFLAG`, `OXSTOCKTEXT`, `OXNOSTOCKTEXT`, `OXDELIVERY`, `OXINSERT`, `OXTIMESTAMP`, `OXLENGTH`, `OXWIDTH`, `OXHEIGHT`, `OXFILE`, `OXSEARCHKEYS`, `OXTEMPLATE`, `OXQUESTIONEMAIL`, `OXISSEARCH`, `OXISCONFIGURABLE`, `OXVARNAME`, `OXVARSTOCK`, `OXVARCOUNT`, `OXVARSELECT`, `OXVARMINPRICE`, `OXVARMAXPRICE`, `OXVARNAME_1`, `OXVARSELECT_1`, `OXVARNAME_2`, `OXVARSELECT_2`, `OXVARNAME_3`, `OXVARSELECT_3`, `OXTITLE_1`, `OXSHORTDESC_1`, `OXURLDESC_1`, `OXSEARCHKEYS_1`, `OXTITLE_2`, `OXSHORTDESC_2`, `OXURLDESC_2`, `OXSEARCHKEYS_2`, `OXTITLE_3`, `OXSHORTDESC_3`, `OXURLDESC_3`, `OXSEARCHKEYS_3`, `OXBUNDLEID`, `OXFOLDER`, `OXSUBCLASS`, `OXSTOCKTEXT_1`, `OXSTOCKTEXT_2`, `OXSTOCKTEXT_3`, `OXNOSTOCKTEXT_1`, `OXNOSTOCKTEXT_2`, `OXNOSTOCKTEXT_3`, `OXSORT`, `OXSOLDAMOUNT`, `OXNONMATERIAL`, `OXFREESHIPPING`, `OXREMINDACTIVE`, `OXREMINDAMOUNT`, `OXAMITEMID`, `OXAMTASKID`, `OXVENDORID`, `OXMANUFACTURERID`, `OXSKIPDISCOUNTS`, `OXRATING`, `OXRATINGCNT`, `OXMINDELTIME`, `OXMAXDELTIME`, `OXDELTIMEUNIT`, `OXUPDATEPRICE`, `OXUPDATEPRICEA`, `OXUPDATEPRICEB`, `OXUPDATEPRICEC`, `OXUPDATEPRICETIME`, `OXISDOWNLOADABLE`, `OXSHOWCUSTOMAGREEMENT`
         */

        $sSql = "SELECT `OXTIMESTAMP`, `OXPUBLIC`, `OXUPDATE`, (SELECT GROUP_CONCAT(CONCAT((SELECT CONCAT(`OXARTNUM`,'~',`OXTITLE`,'~',`OXPRICE`) FROM `oxarticles` WHERE `oxarticles`.`OXID`=`OXARTID`),'~',`OXAMOUNT`,'~',`OXTIMESTAMP`) SEPARATOR '%') FROM `oxuserbasketitems` WHERE `oxuserbaskets`.`OXID`=`OXBASKETID`) AS OXARTICLESINWISHLIST FROM `oxuserbaskets` WHERE `OXUSERID`= '{$this->sUserId}' AND `OXTITLE`='noticelist' ORDER BY `OXTIMESTAMP`";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get gift registry data for this user
     */
    public function getOxWishlist()
    {
        /*
         * Stored gift registry data for this user
         * We are skipping (add them if needed):
         * `OXID`, `OXUSERID`, `OXTITLE`
         *
         * We are aggregating the articles of the gift registry in OXARTICLESINREGISTRY from two tables with separators as follows:
         * % to separate one article from another
         * ~ to separate the article fields from one another
         *
         * From the table oxuserbasketitems we are skipping (add them if needed):
         * `OXID`, `OXBASKETID`, `OXARTID`, `OXSELLIST`, `OXPERSPARAM`
         * From the table oxarticles we are skipping (add them if needed):
         * `OXID`, `OXSHOPID`, `OXPARENTID`, `OXACTIVE`, `OXHIDDEN`, `OXACTIVEFROM`, `OXACTIVETO`, `OXEAN`, `OXDISTEAN`, `OXMPN`, `OXSHORTDESC`, `OXPRICE`, `OXBLFIXEDPRICE`, `OXPRICEA`, `OXPRICEB`, `OXPRICEC`, `OXBPRICE`, `OXTPRICE`, `OXUNITNAME`, `OXUNITQUANTITY`, `OXEXTURL`, `OXURLDESC`, `OXURLIMG`, `OXVAT`, `OXTHUMB`, `OXICON`, `OXPIC1`, `OXPIC2`, `OXPIC3`, `OXPIC4`, `OXPIC5`, `OXPIC6`, `OXPIC7`, `OXPIC8`, `OXPIC9`, `OXPIC10`, `OXPIC11`, `OXPIC12`, `OXWEIGHT`, `OXSTOCK`, `OXSTOCKFLAG`, `OXSTOCKTEXT`, `OXNOSTOCKTEXT`, `OXDELIVERY`, `OXINSERT`, `OXTIMESTAMP`, `OXLENGTH`, `OXWIDTH`, `OXHEIGHT`, `OXFILE`, `OXSEARCHKEYS`, `OXTEMPLATE`, `OXQUESTIONEMAIL`, `OXISSEARCH`, `OXISCONFIGURABLE`, `OXVARNAME`, `OXVARSTOCK`, `OXVARCOUNT`, `OXVARSELECT`, `OXVARMINPRICE`, `OXVARMAXPRICE`, `OXVARNAME_1`, `OXVARSELECT_1`, `OXVARNAME_2`, `OXVARSELECT_2`, `OXVARNAME_3`, `OXVARSELECT_3`, `OXTITLE_1`, `OXSHORTDESC_1`, `OXURLDESC_1`, `OXSEARCHKEYS_1`, `OXTITLE_2`, `OXSHORTDESC_2`, `OXURLDESC_2`, `OXSEARCHKEYS_2`, `OXTITLE_3`, `OXSHORTDESC_3`, `OXURLDESC_3`, `OXSEARCHKEYS_3`, `OXBUNDLEID`, `OXFOLDER`, `OXSUBCLASS`, `OXSTOCKTEXT_1`, `OXSTOCKTEXT_2`, `OXSTOCKTEXT_3`, `OXNOSTOCKTEXT_1`, `OXNOSTOCKTEXT_2`, `OXNOSTOCKTEXT_3`, `OXSORT`, `OXSOLDAMOUNT`, `OXNONMATERIAL`, `OXFREESHIPPING`, `OXREMINDACTIVE`, `OXREMINDAMOUNT`, `OXAMITEMID`, `OXAMTASKID`, `OXVENDORID`, `OXMANUFACTURERID`, `OXSKIPDISCOUNTS`, `OXRATING`, `OXRATINGCNT`, `OXMINDELTIME`, `OXMAXDELTIME`, `OXDELTIMEUNIT`, `OXUPDATEPRICE`, `OXUPDATEPRICEA`, `OXUPDATEPRICEB`, `OXUPDATEPRICEC`, `OXUPDATEPRICETIME`, `OXISDOWNLOADABLE`, `OXSHOWCUSTOMAGREEMENT`
         */
        $sSql = "SELECT `OXTIMESTAMP`, `OXPUBLIC`, `OXUPDATE`, (SELECT GROUP_CONCAT(CONCAT((SELECT CONCAT(`OXARTNUM`,'~',`OXTITLE`,'~',`OXPRICE`) FROM `oxarticles` WHERE `oxarticles`.`OXID`=`OXARTID`),'~',`OXAMOUNT`,'~',`OXTIMESTAMP`) SEPARATOR '%') FROM `oxuserbasketitems` WHERE `oxuserbaskets`.`OXID`=`OXBASKETID`) AS OXARTICLESINREGISTRY FROM `oxuserbaskets` WHERE `OXUSERID`= '{$this->sUserId}' AND `OXTITLE`='wishlist' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get recommendationlist for user
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxrecommlist()
    {
        /*
         * Stored listmania data for this user
         * We are skipping (add them if needed):
         * `OXID`, `OXUSERID`
         */
        $sSql = "SELECT `OXSHOPID`, IFNULL((SELECT `OXNAME` FROM `oxshops` WHERE `OXID`=`OXSHOPID`), '') AS OXSHOPNAME, `OXAUTHOR`, `OXTITLE`, `OXDESC`, `OXRATINGCNT`, `OXRATING`, `OXTIMESTAMP` FROM `oxrecommlists` WHERE `OXUSERID`= '{$this->sUserId}' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * Stored download orders for this user
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxorderfiles()
    {

        $sSql = "SELECT `OXFILENAME`, `OXSHOPID`, IFNULL((SELECT `OXNAME` FROM `oxshops` WHERE `OXID`=`OXSHOPID`), '') AS OXSHOPNAME, `OXFIRSTDOWNLOAD`, `OXLASTDOWNLOAD`, `OXDOWNLOADCOUNT`, `OXMAXDOWNLOADCOUNT`, `OXDOWNLOADEXPIRATIONTIME`, `OXLINKEXPIRATIONTIME`, `OXRESETCOUNT`, `OXVALIDUNTIL`, `OXTIMESTAMP` FROM `oxorderfiles` WHERE `OXORDERID` IN (SELECT `OXORDERID` FROM `oxorder` WHERE `OXUSERID`='{$this->sUserId}') ORDER BY `OXTIMESTAMP`";

        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get stored user basket items from oxuserbasketitem
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getStoresBasketItems()
    {
        /*
         * Stored basket data for this user
         * We are skipping (add them if needed):
         * `OXID`, `OXUSERID`, `OXTITLE`
         *
         * We are aggregating the articles of the basket in OXARTICLESINBASKET from two tables with separators as follows:
         * % to separate one article from another
         * ~ to separate the article fields from one another
         *
         * From the table oxuserbasketitems we are skipping (add them if needed):
         * `OXID`, `OXBASKETID`, `OXARTID`, `OXSELLIST`, `OXPERSPARAM`
         * From the table oxarticles we are skipping (add them if needed):
         * `OXID`, `OXSHOPID`, `OXPARENTID`, `OXACTIVE`, `OXHIDDEN`, `OXACTIVEFROM`, `OXACTIVETO`, `OXEAN`, `OXDISTEAN`, `OXMPN`, `OXSHORTDESC`, `OXPRICE`, `OXBLFIXEDPRICE`, `OXPRICEA`, `OXPRICEB`, `OXPRICEC`, `OXBPRICE`, `OXTPRICE`, `OXUNITNAME`, `OXUNITQUANTITY`, `OXEXTURL`, `OXURLDESC`, `OXURLIMG`, `OXVAT`, `OXTHUMB`, `OXICON`, `OXPIC1`, `OXPIC2`, `OXPIC3`, `OXPIC4`, `OXPIC5`, `OXPIC6`, `OXPIC7`, `OXPIC8`, `OXPIC9`, `OXPIC10`, `OXPIC11`, `OXPIC12`, `OXWEIGHT`, `OXSTOCK`, `OXSTOCKFLAG`, `OXSTOCKTEXT`, `OXNOSTOCKTEXT`, `OXDELIVERY`, `OXINSERT`, `OXTIMESTAMP`, `OXLENGTH`, `OXWIDTH`, `OXHEIGHT`, `OXFILE`, `OXSEARCHKEYS`, `OXTEMPLATE`, `OXQUESTIONEMAIL`, `OXISSEARCH`, `OXISCONFIGURABLE`, `OXVARNAME`, `OXVARSTOCK`, `OXVARCOUNT`, `OXVARSELECT`, `OXVARMINPRICE`, `OXVARMAXPRICE`, `OXVARNAME_1`, `OXVARSELECT_1`, `OXVARNAME_2`, `OXVARSELECT_2`, `OXVARNAME_3`, `OXVARSELECT_3`, `OXTITLE_1`, `OXSHORTDESC_1`, `OXURLDESC_1`, `OXSEARCHKEYS_1`, `OXTITLE_2`, `OXSHORTDESC_2`, `OXURLDESC_2`, `OXSEARCHKEYS_2`, `OXTITLE_3`, `OXSHORTDESC_3`, `OXURLDESC_3`, `OXSEARCHKEYS_3`, `OXBUNDLEID`, `OXFOLDER`, `OXSUBCLASS`, `OXSTOCKTEXT_1`, `OXSTOCKTEXT_2`, `OXSTOCKTEXT_3`, `OXNOSTOCKTEXT_1`, `OXNOSTOCKTEXT_2`, `OXNOSTOCKTEXT_3`, `OXSORT`, `OXSOLDAMOUNT`, `OXNONMATERIAL`, `OXFREESHIPPING`, `OXREMINDACTIVE`, `OXREMINDAMOUNT`, `OXAMITEMID`, `OXAMTASKID`, `OXVENDORID`, `OXMANUFACTURERID`, `OXSKIPDISCOUNTS`, `OXRATING`, `OXRATINGCNT`, `OXMINDELTIME`, `OXMAXDELTIME`, `OXDELTIMEUNIT`, `OXUPDATEPRICE`, `OXUPDATEPRICEA`, `OXUPDATEPRICEB`, `OXUPDATEPRICEC`, `OXUPDATEPRICETIME`, `OXISDOWNLOADABLE`, `OXSHOWCUSTOMAGREEMENT`
         */
        $sSql = "SELECT `OXTIMESTAMP`, `OXPUBLIC`, `OXUPDATE`, (SELECT GROUP_CONCAT(CONCAT((SELECT CONCAT(`OXARTNUM`,'~',`OXTITLE`,'~',`OXPRICE`) FROM `oxarticles` WHERE `oxarticles`.`OXID`=`OXARTID`),'~',`OXAMOUNT`,'~',`OXTIMESTAMP`) SEPARATOR '%') FROM `oxuserbasketitems` WHERE `oxuserbaskets`.`OXID`=`OXBASKETID`) AS OXARTICLESINBASKET FROM `oxuserbaskets` WHERE `OXUSERID`='{$this->sUserId}' AND `OXTITLE`='savedbasket' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get user ratings from oxratings
     * @return array
     * @throws oxConnectionException
     */
    public function getOxratings()
    {
        /*
         * Stored ratings from this user
         * We are skipping (add them if needed):
         * `OXID`, `OXUSERID`, `OXTYPE`, `OXOBJECTID`
         *
         * We are aggregating the article fields for the review in OXARTICLEREVIEW from oxarticles as follows:
         * ~ to separate the article fields from one another
         *
         * From the table oxarticles we are skipping (add them if needed):
         * `OXID`, `OXSHOPID`, `OXPARENTID`, `OXACTIVE`, `OXHIDDEN`, `OXACTIVEFROM`, `OXACTIVETO`, `OXEAN`, `OXDISTEAN`, `OXMPN`, `OXSHORTDESC`, `OXPRICE`, `OXBLFIXEDPRICE`, `OXPRICEA`, `OXPRICEB`, `OXPRICEC`, `OXBPRICE`, `OXTPRICE`, `OXUNITNAME`, `OXUNITQUANTITY`, `OXEXTURL`, `OXURLDESC`, `OXURLIMG`, `OXVAT`, `OXTHUMB`, `OXICON`, `OXPIC1`, `OXPIC2`, `OXPIC3`, `OXPIC4`, `OXPIC5`, `OXPIC6`, `OXPIC7`, `OXPIC8`, `OXPIC9`, `OXPIC10`, `OXPIC11`, `OXPIC12`, `OXWEIGHT`, `OXSTOCK`, `OXSTOCKFLAG`, `OXSTOCKTEXT`, `OXNOSTOCKTEXT`, `OXDELIVERY`, `OXINSERT`, `OXTIMESTAMP`, `OXLENGTH`, `OXWIDTH`, `OXHEIGHT`, `OXFILE`, `OXSEARCHKEYS`, `OXTEMPLATE`, `OXQUESTIONEMAIL`, `OXISSEARCH`, `OXISCONFIGURABLE`, `OXVARNAME`, `OXVARSTOCK`, `OXVARCOUNT`, `OXVARSELECT`, `OXVARMINPRICE`, `OXVARMAXPRICE`, `OXVARNAME_1`, `OXVARSELECT_1`, `OXVARNAME_2`, `OXVARSELECT_2`, `OXVARNAME_3`, `OXVARSELECT_3`, `OXTITLE_1`, `OXSHORTDESC_1`, `OXURLDESC_1`, `OXSEARCHKEYS_1`, `OXTITLE_2`, `OXSHORTDESC_2`, `OXURLDESC_2`, `OXSEARCHKEYS_2`, `OXTITLE_3`, `OXSHORTDESC_3`, `OXURLDESC_3`, `OXSEARCHKEYS_3`, `OXBUNDLEID`, `OXFOLDER`, `OXSUBCLASS`, `OXSTOCKTEXT_1`, `OXSTOCKTEXT_2`, `OXSTOCKTEXT_3`, `OXNOSTOCKTEXT_1`, `OXNOSTOCKTEXT_2`, `OXNOSTOCKTEXT_3`, `OXSORT`, `OXSOLDAMOUNT`, `OXNONMATERIAL`, `OXFREESHIPPING`, `OXREMINDACTIVE`, `OXREMINDAMOUNT`, `OXAMITEMID`, `OXAMTASKID`, `OXVENDORID`, `OXMANUFACTURERID`, `OXSKIPDISCOUNTS`, `OXRATING`, `OXRATINGCNT`, `OXMINDELTIME`, `OXMAXDELTIME`, `OXDELTIMEUNIT`, `OXUPDATEPRICE`, `OXUPDATEPRICEA`, `OXUPDATEPRICEB`, `OXUPDATEPRICEC`, `OXUPDATEPRICETIME`, `OXISDOWNLOADABLE`, `OXSHOWCUSTOMAGREEMENT`
         */

        $sSql = "SELECT `OXSHOPID`, IFNULL((SELECT `OXNAME` FROM `oxshops` WHERE `OXID`=`OXSHOPID`), '') AS OXSHOPNAME, `OXRATING`, `OXTIMESTAMP`,(SELECT CONCAT(`OXARTNUM`,'~',`OXTITLE`,'~',`OXPRICE`) FROM `oxarticles` WHERE `oxarticles`.`OXID`=`OXOBJECTID`) AS OXARTICLEREVIEW FROM `oxratings` WHERE `OXUSERID`= '{$this->sUserId}' AND `OXTYPE`='oxarticle';";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get user reviews
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxreviews()
    {
        /*
         * Stored reviews from this user
         * We are skipping (add them if needed):
         * `OXID`, `OXOBJECTID`, `OXTYPE`, `OXUSERID`, `OXLANG`
         *
         * We are aggregating the article fields for the review in OXARTICLEREVIEW from oxarticles as follows:
         * ~ to separate the article fields from one another
         *
         * From the table oxarticles we are skipping (add them if needed):
         * `OXID`, `OXSHOPID`, `OXPARENTID`, `OXACTIVE`, `OXHIDDEN`, `OXACTIVEFROM`, `OXACTIVETO`, `OXEAN`, `OXDISTEAN`, `OXMPN`, `OXSHORTDESC`, `OXPRICE`, `OXBLFIXEDPRICE`, `OXPRICEA`, `OXPRICEB`, `OXPRICEC`, `OXBPRICE`, `OXTPRICE`, `OXUNITNAME`, `OXUNITQUANTITY`, `OXEXTURL`, `OXURLDESC`, `OXURLIMG`, `OXVAT`, `OXTHUMB`, `OXICON`, `OXPIC1`, `OXPIC2`, `OXPIC3`, `OXPIC4`, `OXPIC5`, `OXPIC6`, `OXPIC7`, `OXPIC8`, `OXPIC9`, `OXPIC10`, `OXPIC11`, `OXPIC12`, `OXWEIGHT`, `OXSTOCK`, `OXSTOCKFLAG`, `OXSTOCKTEXT`, `OXNOSTOCKTEXT`, `OXDELIVERY`, `OXINSERT`, `OXTIMESTAMP`, `OXLENGTH`, `OXWIDTH`, `OXHEIGHT`, `OXFILE`, `OXSEARCHKEYS`, `OXTEMPLATE`, `OXQUESTIONEMAIL`, `OXISSEARCH`, `OXISCONFIGURABLE`, `OXVARNAME`, `OXVARSTOCK`, `OXVARCOUNT`, `OXVARSELECT`, `OXVARMINPRICE`, `OXVARMAXPRICE`, `OXVARNAME_1`, `OXVARSELECT_1`, `OXVARNAME_2`, `OXVARSELECT_2`, `OXVARNAME_3`, `OXVARSELECT_3`, `OXTITLE_1`, `OXSHORTDESC_1`, `OXURLDESC_1`, `OXSEARCHKEYS_1`, `OXTITLE_2`, `OXSHORTDESC_2`, `OXURLDESC_2`, `OXSEARCHKEYS_2`, `OXTITLE_3`, `OXSHORTDESC_3`, `OXURLDESC_3`, `OXSEARCHKEYS_3`, `OXBUNDLEID`, `OXFOLDER`, `OXSUBCLASS`, `OXSTOCKTEXT_1`, `OXSTOCKTEXT_2`, `OXSTOCKTEXT_3`, `OXNOSTOCKTEXT_1`, `OXNOSTOCKTEXT_2`, `OXNOSTOCKTEXT_3`, `OXSORT`, `OXSOLDAMOUNT`, `OXNONMATERIAL`, `OXFREESHIPPING`, `OXREMINDACTIVE`, `OXREMINDAMOUNT`, `OXAMITEMID`, `OXAMTASKID`, `OXVENDORID`, `OXMANUFACTURERID`, `OXSKIPDISCOUNTS`, `OXRATING`, `OXRATINGCNT`, `OXMINDELTIME`, `OXMAXDELTIME`, `OXDELTIMEUNIT`, `OXUPDATEPRICE`, `OXUPDATEPRICEA`, `OXUPDATEPRICEB`, `OXUPDATEPRICEC`, `OXUPDATEPRICETIME`, `OXISDOWNLOADABLE`, `OXSHOWCUSTOMAGREEMENT`
         */

        $sSql = "SELECT `OXACTIVE`, `OXTEXT`, `OXCREATE`, `OXRATING`, `OXTIMESTAMP`,(SELECT CONCAT(`OXARTNUM`,'~',`OXTITLE`,'~',`OXPRICE`) FROM `oxarticles` WHERE `oxarticles`.`OXID`=`OXOBJECTID`) AS OXARTICLEREVIEW FROM `oxreviews` WHERE `OXUSERID`='{$this->sUserId}' AND `OXTYPE`='oxarticle' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get data from oxinvitations
     *
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxinvitations()
    {
        /*
         * Stored invitations from this user
         * We are skipping (add them if needed):
         * `OXUSERID`, `OXPENDING`, `OXACCEPTED`
         */
        $sSql = "SELECT `OXDATE`, `OXEMAIL`, `OXTYPE`, `OXTIMESTAMP` FROM `oxinvitations` WHERE `OXUSERID`='{$this->sUserId}' ORDER BY `OXTIMESTAMP`, `OXEMAIL`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get user data from oxpricealarm
     *
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxpricealarm()
    {
        /*
         * Stored price alarms for this user
         * We are skipping (add them if needed):
         * `OXID`, `OXUSERID`, `OXARTID`, `OXLANG`
         *
         * We are aggregating the article fields for the review in OXARTICLEALARM from oxarticles as follows:
         * ~ to separate the article fields from one another
         *
         * From the table oxarticles we are skipping (add them if needed):
         * `OXID`, `OXSHOPID`, `OXPARENTID`, `OXACTIVE`, `OXHIDDEN`, `OXACTIVEFROM`, `OXACTIVETO`, `OXEAN`, `OXDISTEAN`, `OXMPN`, `OXSHORTDESC`, `OXPRICE`, `OXBLFIXEDPRICE`, `OXPRICEA`, `OXPRICEB`, `OXPRICEC`, `OXBPRICE`, `OXTPRICE`, `OXUNITNAME`, `OXUNITQUANTITY`, `OXEXTURL`, `OXURLDESC`, `OXURLIMG`, `OXVAT`, `OXTHUMB`, `OXICON`, `OXPIC1`, `OXPIC2`, `OXPIC3`, `OXPIC4`, `OXPIC5`, `OXPIC6`, `OXPIC7`, `OXPIC8`, `OXPIC9`, `OXPIC10`, `OXPIC11`, `OXPIC12`, `OXWEIGHT`, `OXSTOCK`, `OXSTOCKFLAG`, `OXSTOCKTEXT`, `OXNOSTOCKTEXT`, `OXDELIVERY`, `OXINSERT`, `OXTIMESTAMP`, `OXLENGTH`, `OXWIDTH`, `OXHEIGHT`, `OXFILE`, `OXSEARCHKEYS`, `OXTEMPLATE`, `OXQUESTIONEMAIL`, `OXISSEARCH`, `OXISCONFIGURABLE`, `OXVARNAME`, `OXVARSTOCK`, `OXVARCOUNT`, `OXVARSELECT`, `OXVARMINPRICE`, `OXVARMAXPRICE`, `OXVARNAME_1`, `OXVARSELECT_1`, `OXVARNAME_2`, `OXVARSELECT_2`, `OXVARNAME_3`, `OXVARSELECT_3`, `OXTITLE_1`, `OXSHORTDESC_1`, `OXURLDESC_1`, `OXSEARCHKEYS_1`, `OXTITLE_2`, `OXSHORTDESC_2`, `OXURLDESC_2`, `OXSEARCHKEYS_2`, `OXTITLE_3`, `OXSHORTDESC_3`, `OXURLDESC_3`, `OXSEARCHKEYS_3`, `OXBUNDLEID`, `OXFOLDER`, `OXSUBCLASS`, `OXSTOCKTEXT_1`, `OXSTOCKTEXT_2`, `OXSTOCKTEXT_3`, `OXNOSTOCKTEXT_1`, `OXNOSTOCKTEXT_2`, `OXNOSTOCKTEXT_3`, `OXSORT`, `OXSOLDAMOUNT`, `OXNONMATERIAL`, `OXFREESHIPPING`, `OXREMINDACTIVE`, `OXREMINDAMOUNT`, `OXAMITEMID`, `OXAMTASKID`, `OXVENDORID`, `OXMANUFACTURERID`, `OXSKIPDISCOUNTS`, `OXRATING`, `OXRATINGCNT`, `OXMINDELTIME`, `OXMAXDELTIME`, `OXDELTIMEUNIT`, `OXUPDATEPRICE`, `OXUPDATEPRICEA`, `OXUPDATEPRICEB`, `OXUPDATEPRICEC`, `OXUPDATEPRICETIME`, `OXISDOWNLOADABLE`, `OXSHOWCUSTOMAGREEMENT`
         */

        $sSql = "SELECT `OXSHOPID`, IFNULL((SELECT `OXNAME` FROM `oxshops` WHERE `OXID`=`OXSHOPID`), '') AS OXSHOPNAME, `OXEMAIL`, `OXPRICE`, `OXCURRENCY`, `OXINSERT`, `OXSENDED`, `OXTIMESTAMP`,(SELECT CONCAT(`OXARTNUM`,'~',`OXTITLE`,'~',`OXPRICE`) FROM `oxarticles` WHERE `oxarticles`.`OXID`=`OXARTID`) AS OXARTICLEALARM FROM `oxpricealarm` WHERE `OXUSERID`='{$this->sUserId}' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get vouchers used by user
     *
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxvouchers()
    {
        $sSql = "SELECT `OXDATEUSED`, `OXVOUCHERNR`, `OXDISCOUNT`, `OXTIMESTAMP` FROM `oxvouchers` WHERE `OXUSERID`='{$this->sUserId}' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * get all data for payments user performed
     *
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getUserPayments()
    {
        $sSql = "SELECT `OXPAYMENTSID`, `OXVALUE`, `OXTIMESTAMP` FROM `oxuserpayments` WHERE `OXUSERID`='{$this->sUserId}' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

    /**
     * When Private Sales is enabled it is also stored if a customer agreed to the terms
     *
     * @return array|bool
     * @throws oxConnectionException
     */
    public function getOxacceptedterms()
    {
        $sSql = "SELECT `OXSHOPID`, IFNULL((SELECT `OXNAME` FROM `oxshops` WHERE `OXID` = `OXSHOPID`), '') AS OXSHOPNAME, `OXTERMVERSION`, `OXACCEPTEDTIME`, `OXTIMESTAMP` FROM `oxacceptedterms` WHERE `OXUSERID` = '{$this->sUserId}' ORDER BY `OXTIMESTAMP`;";
        if ($aRes = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getAll($sSql))
            return $aRes;

        return FALSE;
    }

}
