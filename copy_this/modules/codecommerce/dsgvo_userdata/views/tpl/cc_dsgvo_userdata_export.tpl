[{include file="headitem.tpl" title="CONTENT_MAIN_TITLE"|oxmultilangassign}]
<script type="text/javascript">
    <!--
    function editThis(sID) {
        var oTransfer = top.basefrm.edit.document.getElementById("transfer");
        oTransfer.oxid.value = sID;
        oTransfer.cl.value = top.basefrm.list.sDefClass;

        //forcing edit frame to reload after submit
        top.forceReloadingEditFrame();

        var oSearch = top.basefrm.list.document.getElementById("search");
        oSearch.oxid.value = sID;
        oSearch.actedit.value = 0;
        oSearch.submit();
    }

    window.onload = function () {
        [{if $updatelist == 1}]
        top.oxid.admin.updateList('[{$oxid}]');
            [{if $oxid}]
                top.oxid.admin.editThis('[{$oxid}]');
            [{/if}]
        [{/if}]

        var oField = top.oxid.admin.getLockTarget();
        oField.onchange = oField.onkeyup = oField.onmouseout = top.oxid.admin.unlockSave;
    }
    //-->
</script>

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" id="oxid" value="[{$oxid}]">
    <input type="hidden" name="cl" value="cc_dsgvo_userdata_export">
    <input type="hidden" name="editlanguage" value="[{$editlanguage}]">
</form>
<form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="cc_dsgvo_userdata_export">
    <input type="hidden" name="fnc" value="exportUserData">
    <input type="hidden" name="oxid" id="oxid" value="[{$oxid}]">

    <p>[{oxmultilang ident="CC_USERDATA_EXPORT_INFOTEXT"}]</p>

    <input type="checkbox" name="sendUserMail">
    <label for="sendUserMail">[{oxmultilang ident="CC_SEND_DOWNLOAD_FILE"}]</label>
    <br><br>
    <button>[{oxmultilang ident="CC_USERDATA_EXPORT"}]</button>
    [{if $sFilePath}]
        [{if $blSend}]
            <p>
                [{if $blUserMailSend}]
                    [{oxmultilang ident="CC_MAIL_SEND"}]
                [{else}]
                    [{oxmultilang ident="CC_MAIL_NOT_SEND"}]
                [{/if}]
            </p>
        [{/if}]
        <div>
            <br><br>
            <a href="[{$sFilePath}]" download>[{oxmultilang ident="CC_DOWNLOAD_FILE"}]</a>
            <br><br>
            <button onclick="myedit.fnc.value='delete';">[{oxmultilang ident="CC_DELETE_DOWNLOAD_FILE"}]</button>
        </div>
    [{/if}]
    [{if $blFileRemoved}]
        <br><br>
        [{oxmultilang ident="CC_DOWNLOAD_FILE_REMOVED"}]
    [{/if}]
</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]