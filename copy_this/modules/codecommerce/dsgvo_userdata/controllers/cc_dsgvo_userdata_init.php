<?php
/**
 * Copyright (c) 2018
 * CodeCommerce - Christopher Bauer
 * www.codecommerce.de
 */

/**
 * modul init class
 */
class cc_dsgvo_userdata_init
{

    /**
     * array with modules depended to this use this module
     * @var array
     */
    private $_aModules;

    /**
     * array with multishoptable
     * @var array
     */
    private $_aMultiShopTables;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->_aModules = [];

        $this->_aMultiShopTables = [];
    }

    /**
     * on active function
     */
    static function onActivate()
    {
        /**
         * check if dependencies are set
         * insert sql files
         */
        try {

            $oInit = oxNew("cc_dsgvo_userdata_init");
            foreach ($oInit->getDependencies() as $sModuleId) {
                $oModule = oxNew("oxModule");
                if (!$oModule->load($sModuleId) || !$oModule->isActive()) {
                    $oEx = oxNew('oxException');
                    $oEx->setMessage('MODULE_NOT_FOUND');
                    throw $oEx;
                }
            }

            $oInit->checkSql();

            $oInit->addMultiShopTables();

        } catch (oxException $oExcp) {
            oxRegistry::get("oxUtilsView")->addErrorToDisplay($oExcp);

            /**
             * deactivate module if not all dependencies are active
             */
            $oModule = oxNew("oxModule");
            $oModule->load('cc_dsgvo_userdata');

            $oModuleCache = oxNew('oxModuleCache', $oModule);
            /** @var oxModuleInstaller $oModuleInstaller */
            $oModuleInstaller = oxNew('oxModuleInstaller', $oModuleCache);

            $oModuleInstaller->deactivate($oModule);
        }
    }

    /**
     * returns dependencies - module id's
     * @return array
     */
    public function getDependencies()
    {
        return $this->_aModules;
    }

    /**
     * checks if sql must be installed / updated
     */
    private function checkSql()
    {
        /**
         * Create db tables
         * @todo check table / fields
         * SHOW COLUMNS FROM `table` LIKE 'fieldname';
         * SHOW TABLES LIKE 'tablename'
         */
        $oRs = oxDb::getDb()->Execute("SELECT * FROM oxcontents WHERE OXID = 'e44dd16ba472a81a025fdbba2c2af26f'");
        if ($oRs != FALSE && $oRs->RecordCount() == 0) {
            /**
             * getting sql from file
             */
            $sSqlFile = file_get_contents(getShopBasePath() . 'modules/codecommerce/dsgvo_userdata/setup/install.sql');
            $aSqlRows = explode(";", $sSqlFile);
            /**
             * execute sql
             */
            foreach ($aSqlRows as $sSqlRow) {
                if (trim($sSqlRow) !== '') {
                    oxDb::getDb()->Execute($sSqlRow);
                }
            }

            /**
             * update views
             */
            $oDbHandler = oxNew('oxDbMetaDataHandler');
            $oDbHandler->updateViews();

            $oEx = oxNew('oxexception');
            $oEx->setMessage(oxRegistry::getLang()->translateString('DB_INSTALLED_AND_VIEWS_UPDATED'));
            throw $oEx;
        }
    }

    /**
     * adds multishop tables to oxconfig
     */
    protected function addMultiShopTables()
    {
        $aMultiShopTables = array_merge(oxRegistry::getConfig()->getConfigParam('aMultiShopTables'), $this->_aMultiShopTables);
        $aMultiShopTables = array_unique($aMultiShopTables);

        oxRegistry::getConfig()->saveShopConfVar('arr', 'aMultiShopTables', $aMultiShopTables);
    }

    /**
     * unset table from multishoptable config
     * @param $aMultiShopTables array for all multishop tables
     * @param $sTableName       tablename
     * @return mixed
     */
    protected function removeMultiShopTableFromConfig($aMultiShopTables, $sTableName)
    {
        foreach ($aMultiShopTables as $key => $svalue) {
            if ($svalue == '$sTableName')
                unset($aMultiShopTables[$key]);
        }

        return $aMultiShopTables;
    }
}