[{assign var="shop"      value=$oEmailView->getShop()}]
[{assign var="oViewConf" value=$oEmailView->getViewConfig()}]
[{assign var="user"      value=$oEmailView->getUser()}]


[{include file="email/html/header.tpl"}]

<p>[{oxcontent ident="cc_dsgvo_userdata"}]</p>

[{include file="email/html/footer.tpl"}]
