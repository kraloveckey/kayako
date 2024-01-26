<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

  <head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=<{$_language[charset]}>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><{if $_pageTitle != ""}><{$_pageTitle}><{else}><{$_companyName}><{/if}> - <{$_poweredByNotice}></title>
    <meta name="KEYWORDS" content="Home" />
    <{if isset($_robotsNoIndex) && $_robotsNoIndex == 'true'}>
    <meta name="robots" content="noindex,nofollow" />
    <{else}>
    <meta name="robots" content="index,follow" />
    <{/if}>

<!--
	<script>
	window.addEventListener("load", function(){
	window.cookieconsent.initialise({
	  "palette": {
		"popup": {
		  "background": "#000"
		},
		"button": {
		  "background": "#f1d600"
		}
	  },
	  "content": {
	  	"header": 'Cookie Consent',
	  	"message":'<{$_language[cookiepolicytext]|escape}>',
	  	"link":'<{$_language[cookiepolicyurl]|escape}>',
	  	"href":'<{$_registrationPolicyURL}>',
	  	"close":'&#x274c',
	  	"dismiss":'<{$_language[cookiepolicyagreement]|escape}>',
	  },
	  "cookie": {
	  	"name": "SWIFT_cookieconsent"
	  },
	  onStatusChange: function(status) {
	  	if (this.hasConsented) {
	  		document.cookie = "SWIFT_cookieconsenturl="+window.location.href;
	  		<{if isset($_userIsLoggedIn) && $_userIsLoggedIn == 'true'}>
	  			$.ajax({
						type: "POST",
						url: "<{$_baseName}><{$_templateGroupPrefix}>/Base/User/UpdateCookieConsentAJAX",
					});
    		<{/if}>
	  	}
	  }
	})});
	</script>
-->

    <link rel="icon" href="<{$_swiftPath}>favicon.ico" type="image/x-icon"/>
	<{if $_settings[nw_enablerss] == '1'}>
    <link rel="alternate" type="application/rss+xml" title="RSS" href="<{$_swiftPath}>rss/index.php?<{$_templateGroupPrefix}>/News/Feed" />
	<{/if}>
	<script language="Javascript" type="text/javascript">
	var _themePath = "<{$_themePath}>";
	var _swiftPath = "<{$_swiftPath}>";
	var _baseName = "<{$_baseName}>";
	var datePickerDefaults = {showOn: "both", buttonImage: "<{$_themePath}>images/icon_calendar.svg", changeMonth: true, changeYear: true, buttonImageOnly: true, dateFormat: '<{if $_settings[dt_caltype] == 'us'}>mm/dd/yy<{else}>dd/mm/yy<{/if}>'};
	</script>

	<link rel="stylesheet" type="text/css" media="all" href="<{$_baseName}><{$_templateGroupPrefix}>/Core/Default/Compressor/css" />
	<script type="text/javascript" src="<{$_baseName}><{$_templateGroupPrefix}>/Core/Default/Compressor/js"></script>
	<script language="Javascript" type="text/javascript">
	<{$_jsInitPayload}>
	</script>
	 <{if isset($_showCheckOffScreen) && $_showCheckOffScreen == 'true'}>
    	<script>
    	window.addEventListener("load", function(){
    		jQuery("#checkoffscreen").dialog({
    			autoOpen: true
    		});
		});
    	</script>
    <{/if}>
  </head>

  <body class="bodymain">
	<div id="main">
		<div id="topbanner">
			<div class="innerwrapper">
      			<a href="<{$_baseName}><{$_templateGroupPrefix}>"><img border="0" src="<{$_headerImageSC}>" alt="Kayako logo" id="logo" width="120px;"/></a>
			</div>
      	</div>

      	<div id="toptoolbar">
      	    <a class="nav-opener" href="#"><span></span></a>
      		<div class="innerwrapper">
		        <span id="toptoolbarrightarea">
		        	<{if $_pageTitle != ""}>
<!--
					<div class="topbar_searchdiv">
						<form method="post" id="searchformTopbar" action="<{$_baseName}><{$_templateGroupPrefix}>/Base/Search/Index" name="SearchFormTopbar">
						<span class="searchinputcontainer_topbar"><input type="text" name="searchquery" class="searchquery_topbar" onclick="javascript: if ($(this).val() == '<{$_language[pleasetypeyourquestion]}>' || $(this).val() == '<{$_language[pleasetypeyourquery]}>') { $(this).val('').addClass('searchqueryactive'); }" value="<{if $_baseIndex == true}><{$_language[pleasetypeyourquestion]}><{else}><{$_language[pleasetypeyourquery]}><{/if}>" /></span>

<span class="searchbuttoncontainer_topbar">
							<i class="fa fa-search" onclick="$('#searchformTopbar').submit();" aria-hidden="true"></i></span>
						</form>
					</div>
-->
					<{/if}>

					<select class="swiftselect" name="languageid" id="languageid" onchange="javascript: LanguageSwitch(false);">
						<{foreach key=_languageID item=_languageItem from=$_languageContainer}>
						<{if $_languageItem[isenabled] == '1'}>
						<option value="<{$_languageID}>"<{if $_activeLanguageID == $_languageID}> selected<{/if}>><{$_languageItem[title]}></option>
						<{/if}>
						<{/foreach}>
					</select>
		        </span>

	        	<ul id="toptoolbarlinklist">
                    <{foreach key=key item=_item from=$_widgetContainer}>
                    <{if $_item[displayinnavbar] == '1'}>
                    <li<{if $_item[isactive] == true}> class="current"<{/if}>><a class="toptoolbarlink" href="<{$_item[widgetlink]}>" title="<{$_item[defaulttitle]}>"><{$_item[defaulttitle]}></a></li>
                    <{/if}>
                    <{/foreach}>
	        	</ul>
	        </div>
      	</div>

      	<div id="maincore">
      		<{if $_pageTitle == ""}>
      		<div class="uppermainsearch">
                <div class="helptitle"><{$_language[pleasetypeyourquestion]}></div>
<!--
					<div id="breadcrumbbar">
						<span class="breadcrumb lastcrumb"><{$_language[home]}></span>
					</div>

	                <form method="post" id="searchform" action="<{$_baseName}><{$_templateGroupPrefix}>/Base/Search/Index" name="SearchForm">
	                	<div class="helptitle"><{if $_baseIndex == true}><{$_language[pleasetypeyourquestion]}><{else}><{$_language[pleasetypeyourquery]}><{/if}></div>
						<div class="searchboxcontainer">
							<div class="searchbox">
								<span class="searchbuttoncontainer">
									<a class="searchbutton" href="javascript: void(0);" onclick="$('#searchform').submit();"><span></span><{$_language[searchbutton]}></a>
								</span>
								<span class="searchinputcontainer"><input type="text" name="searchquery" class="searchquery" onclick="javascript: if ($(this).val() == '<{$_language[pleasetypeyourquestion]}>' || $(this).val() == '<{$_language[pleasetypeyourquery]}>') { $(this).val('').addClass('searchqueryactive'); }" value="<{if $_baseIndex == true}><{$_language[pleasetypeyourquestion]}><{else}><{$_language[pleasetypeyourquery]}><{/if}>" /></span>
							</div>
						</div>
					</form>
-->
					<div id="corewidgetbox"><div class="widgetrow"><{foreach key=key item=_item from=$_widgetContainer}><{if $_item[displayinindex] == '1'}><span onclick="javascript: window.location.href='<{$_item[widgetlink]}>';"><a href="<{$_item[widgetlink]}>" class="widgetrowitem defaultwidget" style="<{if $_item[defaulticon] != ''}>background-image: URL('<{$_item[defaulticon]}>');background-size: 36px 36px;<{/if}>" title="<{$_item[defaulttitle]}>"><span class="widgetitemtitle"><{$_item[defaulttitle]}></span></a></span><{/if}><{/foreach}></div></div>

      		</div>
      		<{/if}>
			<div class="innerwrapper">
        	    <div id="maincoreleft">
 					<div id="leftloginsubscribebox">
              			<{if $_userIsLoggedIn == true}>
                            <div class="tabrow" id="leftloginsubscribeboxtabs"><a id="leftloginsubscribeboxlogintab" href="#" class="atab"><span class="tableftgap">&nbsp;</span><span class="tabbulk"><span class="tabtext" title="<{$_language[myaccount]}>"><{$_language[myaccount]}></span></span></a></div>
	                        <div id="leftloginbox" class="switchingpanel active">
	                            <div class="maitem maprofile" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/UserAccount/Profile');"><{$_language[maprofile]}></div>
	                            <{if ($_settings[user_orgprofileupdate] == 'allusers' && $_user[userorganizationid] != '0') || ($_user[userrole] == 2 && $_settings[user_orgprofileupdate] == 'managersonly' && $_user[userorganizationid] != '0')}>
	                                <div class="maitem maorganization" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/UserAccount/MyOrganization');"><{$_language[maorganization]}></div>
	                            <{/if}>
		                        <{foreach key=_itemID item=_navbarMenuItem from=$_navbarMenuItemContainer}>
		                            <div class="maitem<{if $_navbarMenuItem[class] != ''}> <{$_navbarMenuItem[class]}><{/if}>" onclick="javascript: Redirect('<{$_navbarMenuItem[link]}>');"><{$_navbarMenuItem[title]}></div>
		                        <{/foreach}>
				                <div class="maitem mapreferences" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/UserAccount/Preferences');"><{$_language[mapreferences]}></div>
				                <div class="maitem machangepassword" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/UserAccount/ChangePassword');"><{$_language[machangepassword]}></div>
				                <div class="maitem malogout" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/User/Logout');"><{$_language[malogout]}></div>
				            </div>

	                        <{else}>

							<form method="post" action="<{$_baseName}><{$_templateGroupPrefix}>/Base/User/Login" name="LoginForm">
								<div class="tabrow" id="leftloginsubscribeboxtabs"><a id="leftloginsubscribeboxlogintab" href="javascript:void(0);" onclick="ActivateLoginTab();" class="atab"><span class="tableftgap">&nbsp;</span><span class="tabbulk"><span class="tabtext" title="<{$_language[login]}>"><{$_language[login]}></span></span></a><{if $_canSubscribeNews == true}><a id="leftloginsubscribeboxsubscribetab" href="javascript: void(0);" onclick="javascript: ActivateSubscribeTab();" class="atab inactive"><span class="tableftgap">&nbsp;</span><span class="tabbulk"><span class="tabtext" title="<{$_language[subscribe]}>"><{$_language[subscribe]}></span></span></a><{/if}></div>
								<div id="leftloginbox" class="switchingpanel active">
									<input type="hidden" name="_redirectAction" value="<{$_redirectAction}>" />
									<input type="hidden" name="_csrfhash" value="<{$_csrfhash}>" />
									<div class="inputframe zebraeven"><input class="loginstyled<{if $_userLoginEmail != ''}><{else}>label<{/if}>" value="<{if $_userLoginEmail != ''}><{$_userLoginEmail}><{else}><{$_language[loginenteremail]}><{/if}>" onfocus="javascript: ResetLabel(this, '<{$_language[loginenteremail]}>', 'loginstyled');" name="scemail" type="text"></div>
									<div class="inputframe zebraodd"><input class="loginstyled" value="<{$_userLoginPassword}>" name="scpassword" type="password" autocomplete="off"></div>
									<div class="inputframe remembermeDiv"><input id="leftloginboxrememberme" name="rememberme" value="1" type="checkbox"<{if $_userRememberMe == true}> checked<{/if}>><label for="leftloginboxrememberme"><span id="leftloginboxremembermetext"><{$_language[rememberme]}></span></label></div>
									<hr class="vdivider">
									<div id="logintext"><a href="<{$_baseName}><{$_templateGroupPrefix}>/Base/UserLostPassword/Index" title="<{$_language[lostpassword]}>"><{$_language[lostpassword]}></a></div>
									<div id="loginsubscribebuttons"><input class="rebutton" value="<{$_language[login]}>" type="submit" title="<{$_language[login]}>" /></div>
								</div>
							</form>

              			    <{if $_canSubscribeNews == true}>

								<form method="post" action="<{$_baseName}><{$_templateGroupPrefix}>/News/Subscriber/Subscribe" name="SubscribeForm">
								<input type="hidden" name="_csrfhash" value="<{$_csrfhash}>" />
									<div id="leftsubscribebox" class="switchingpanel">
										<div class="inputframe zebraeven"><input class="emailstyledlabel" value="<{$_language[loginenteremail]}>" onfocus="javascript: ResetLabel(this, '<{$_language[loginenteremail]}>', 'emailstyled');" name="subscribeemail" type="text">
										<br>

<!--
										<label> <input name="registrationconsent" type="checkbox"/> <{$_language[regpolicytext]}> <a href="<{$_registrationPolicyURL}>" target="_blank"> <{$_language[regpolicyurl]}> </a></label>
-->
										<div id="divCheckbox" style="display: none;">
										<label> <input name="registrationconsent" type="checkbox" checked/> <{$_language[regpolicytext]}> <a href="<{$_registrationPolicyURL}>" target="_blank"> <{$_language[regpolicyurl]}> </a></label>
										<br />
										</div>
										</div>
										<hr class="vdivider">
										<div id="logintext">&nbsp;</div>
										<div id="loginsubscribebuttons"><input class="rebutton" value="<{$_language[buttonsubmit]}>" type="submit"></div>
									</div>
								</form>
  						    <{/if}>
              			<{/if}>
            		</div>

		  		    <{if $_settings[ls_displaystatus] == '1'}>
			            <div id="leftlivechatbox">
	                        <!-- BEGIN TAG CODE --><div><div id="proactivechatcontainernc2v4biell"></div><table border="0" cellspacing="2" cellpadding="2"><tr><td align="center" id="swifttagcontainernc2v4biell"><div style="display: inline;" id="swifttagdatacontainer"></div></td> </tr><tr><td align="center"><!-- DO NOT REMOVE --><div style="MARGIN-TOP: 2px; WIDTH: 100%; TEXT-ALIGN: center;"><span style="FONT-SIZE: 9px; FONT-FAMILY: 'segoe ui','helvetica neue', arial, helvetica, sans-serif;"><a href="http://www.kayako.com/products/live-chat-software/" style="TEXT-DECORATION: none; COLOR: #000000" target="_blank" rel="noopener noreferrer">Live Chat Software</a><span style="COLOR: #000000"> by </span>Kayako</span></div><!-- DO NOT REMOVE --></td></tr></table></div> <script type="text/javascript">var swiftscriptelemnc2v4biell=document.createElement("script");swiftscriptelemnc2v4biell.type="text/javascript";var swiftrandom = Math.floor(Math.random()*1001); var swiftuniqueid = "nc2v4biell"; var swifttagurlnc2v4biell="<{$_swiftPath}>visitor/index.php?<{$_templateGroupPrefix}>/LiveChat/HTML/HTMLButtonBase";setTimeout("swiftscriptelemnc2v4biell.src=swifttagurlnc2v4biell;document.getElementById('swifttagcontainernc2v4biell').appendChild(swiftscriptelemnc2v4biell);",1);</script><!-- END TAG CODE -->
			            </div>
		  		    <{/if}>

				    <{if $_filterKnowledgebase == true}>
						<div class="leftnavboxbox">
							<div class="leftnavboxtitle"><span class="leftnavboxtitleleftgap">&nbsp;</span><span class="leftnavboxtitlebulk"><span class="leftnavboxtitletext"><{$_language[filterkb]}></span></span></div>
							<div class="leftnavboxcontent">
								<{foreach key=_knowledgebaseCategoryID item=_knowledgebaseCategory from=$_navKnowledgebaseCategoryContainer}>
									<a class="zebraeven" href="<{$_baseName}><{$_templateGroupPrefix}>/Knowledgebase/List/Index/<{$_knowledgebaseCategoryID}>/<{$_knowledgebaseCategory[seotitle]}>"><{if $_knowledgebaseCategory[totalarticles] > 0}><span class="graytext"><{$_knowledgebaseCategory[totalarticles]}></span><{/if}><{$_knowledgebaseCategory[title]}></a>
								<{/foreach}>
							</div>
						</div>
				    <{/if}>

				    <{if $_filterNews == true}>
				  	    <div class="leftnavboxbox">
				  		    <div class="leftnavboxtitle"><span class="leftnavboxtitleleftgap">&nbsp;</span><span class="leftnavboxtitlebulk"><span class="leftnavboxtitletext"><{$_language[filternews]}></span></span></div>
				  		    <div class="leftnavboxcontent">
					            <{foreach key=_newsCategoryID item=_newsCategory from=$_newsCategoryContainer}>
						            <{if $_newsCategory[totalitems] != '0'}>
						                <a class="zebraeven" href="<{$_baseName}><{$_templateGroupPrefix}>/News/List/Index/<{$_newsCategoryID}>"><{if $_newsCategory[totalitems] > 0}><span class="graytext"><{$_newsCategory[totalitems]}></span><{/if}><{$_newsCategory[categorytitle]}></a>
					                <{/if}>
						        <{/foreach}>
				  		    </div>
				  	    </div>
				    <{/if}>
                </div>

	            <div id="maincorecontent">
					<!-- BEGIN DIALOG PROCESSING -->
					<{foreach key=key item=_item from=$_errorContainer}>
						<div class="dialogerror"><div class="dialogerrorsub"><div class="dialogerrorcontent"><{$_item[message]}></div></div></div>
					<{/foreach}>
					<{foreach key=key item=_item from=$_infoContainer}>
						<div class="dialoginfo"><div class="dialoginfosub"><div class="dialoginfocontent"><{$_item[message]}></div></div></div>
					<{/foreach}>