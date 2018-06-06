<?php
/**
 * Copyright (c) 2018
 * CodeCommerce - Christopher Bauer
 * www.codecommerce.de
 */
$sMetadataVersion = "1.1";

$aModule = [
    'id'          => 'codecommerce_dsgvo_userdata',
    'title'       => '<img src="../modules/codecommerce/cc_modul.png" alt="CodeCommerce.de" title="CodeCommerce.de"> CodeCommerce.de :: DSGVO Userdaten Export',
    'description' => 'Exportiert die Nutzerdaten in einer Maschinenlesbaren Form<br><br><a href="https://oxidforge.org/en/how-we-temporarily-handle-the-right-to-data-portability-art-20-gdpr.html">Link zum Beitrag für dieses Modul</a>',
    'thumbnail'   => '../logo.png',
    'version'     => '1.0',
    'author'      => 'C. Bauer',
    'email'       => 'info@codecommerce.de',
    'url'         => 'http://www.codecommerce.de',
    'extend'      => [
        'oxemail' => 'codecommerce/dsgvo_userdata/models/cc_dsgvo_userdata__oxemail',
    ],
    'files'       => [
        'cc_dsgvo_userdata_export' => 'codecommerce/dsgvo_userdata/controllers/admin/cc_dsgvo_userdata_export.php',
        'cc_dsgvo_userdata_init'   => 'codecommerce/dsgvo_userdata/controllers/cc_dsgvo_userdata_init.php',
    ],
    'templates'   => [
        'cc_dsgvo_userdata_export.tpl'  => 'codecommerce/dsgvo_userdata/views/tpl/cc_dsgvo_userdata_export.tpl',
        'email/html/dsgvo_userdata.tpl' => 'codecommerce/dsgvo_userdata/views/tpl/dsgvo_userdata.tpl',
    ],
    'events'      => [
        'onActivate' => 'cc_dsgvo_userdata_init::onActivate',
    ],
];