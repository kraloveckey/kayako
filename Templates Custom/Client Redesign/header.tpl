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

  <body class="bodymain<{if $_userIsLoggedIn == true}> is-logged-in<{/if}>">
	<div id="main">

		<!-- ========================================
		     NEW TOP BANNER with nav + account dropdown
		     ======================================== -->
		<div id="topbanner">
			<div class="hd-inner">
				<a href="<{$_baseName}><{$_templateGroupPrefix}>" class="hd-brand">
					<img src="<{$_headerImageSC}>" alt="Logo" class="hd-logo" />
					<span class="hd-brand-name">Helpdesk</span>
				</a>

				<nav class="hd-nav">
					<{if $_userIsLoggedIn == true}>
						<{foreach key=key item=_item from=$_widgetContainer}>
						<{if $_item[displayinnavbar] == '1' && $_item[defaulttitle] != 'Home' && $_item[defaulttitle] != $_language[home]}>
						<a class="hd-nav-link<{if $_item[isactive] == true}> hd-nav-active<{/if}>"
						   href="<{$_item[widgetlink]}>"
						   title="<{$_item[defaulttitle]}>"><{$_item[defaulttitle]}></a>
						<{/if}>
						<{/foreach}>

						<div class="hd-account-menu">
							<span class="hd-account-btn" tabindex="0">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
								<span class="hd-account-label"><{$_language[myaccount]}></span>
								<svg class="hd-chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
							</span>
							<div class="hd-dropdown">
								<!-- Direct href links — no JS redirect, no onclick, guaranteed to work -->
								<a class="hd-dd-item maprofile" href="<{$_swiftPath}>index.php?/Base/UserAccount/Profile"><{$_language[maprofile]}></a>
								<{if ($_settings[user_orgprofileupdate] == 'allusers' && $_user[userorganizationid] != '0') || ($_user[userrole] == 2 && $_settings[user_orgprofileupdate] == 'managersonly' && $_user[userorganizationid] != '0')}>
								<a class="hd-dd-item maorganization" href="<{$_swiftPath}>index.php?/Base/UserAccount/MyOrganization"><{$_language[maorganization]}></a>
								<{/if}>
								<{foreach key=_itemID item=_navbarMenuItem from=$_navbarMenuItemContainer}>
								<a class="hd-dd-item" href="<{$_navbarMenuItem[link]}>"><{$_navbarMenuItem[title]}></a>
								<{/foreach}>
								<a class="hd-dd-item mapreferences" href="<{$_swiftPath}>index.php?/Base/UserAccount/Preferences"><{$_language[mapreferences]}></a>
								<div class="hd-dd-divider"></div>
								<a class="hd-dd-item hd-dd-logout malogout" href="<{$_baseName}><{$_templateGroupPrefix}>/Base/User/Logout"><{$_language[malogout]}></a>
							</div>
						</div>
					<{/if}>
				</nav>
			</div>
		</div>

		<!-- Legacy toolbar — hidden, kept for JS compatibility -->
		<div id="toptoolbar" style="display:none;">
		    <a class="nav-opener" href="#"><span></span></a>
			<div class="innerwrapper">
		        <span id="toptoolbarrightarea">
					<select class="swiftselect" name="languageid" id="languageid" onchange="javascript: LanguageSwitch(false);" style="display:none;">
						<{foreach key=_languageID item=_languageItem from=$_languageContainer}>
						<{if $_languageItem[isenabled] == '1'}>
						<option value="<{$_languageID}>"<{if $_activeLanguageID == $_languageID}> selected<{/if}>><{$_languageItem[title]}></option>
						<{/if}>
						<{/foreach}>
					</select>
		        </span>
	        	<ul id="toptoolbarlinklist"></ul>
	        </div>
      	</div>

      	<div id="maincore">

			<{if $_pageTitle == ""}>
				<{if $_userIsLoggedIn == true}>
				<script type="text/javascript">
				    // Only redirect to Submit from the real home page.
				    // Profile, Preferences, MyOrganization, ChangePassword all have
				    // empty $_pageTitle too — detect them by URL path and skip redirect.
				    (function() {
				        var path = window.location.href.toLowerCase();
				        var isUserAccountPage = path.indexOf('/base/useraccount/') !== -1 ||
				                               path.indexOf('/base/user/') !== -1;
				        if (!isUserAccountPage) {
				            document.getElementById('maincore').style.visibility = 'hidden';
				            window.location.replace("<{$_baseName}><{$_templateGroupPrefix}>/Tickets/Submit");
				        }
				    })();
				</script>
				<{else}>
				<style>
				    #maincore .innerwrapper { justify-content: center !important; }
				    #maincoreleft {
				        float: none !important;
				        width: 100% !important;
				        max-width: 420px !important;
				        margin: 80px auto 40px !important;
				    }
				    #leftlivechatbox,
				    .leftnavboxbox { display: none !important; }
				    #maincorecontent { display: none !important; }
				</style>
				<{/if}>
			<{/if}>
			<!-- Dialog messages — full width, above the flex layout.
			     On login page (not logged in): JS moves this below the login button. -->
			<div id="hd-messages">
				<{foreach key=key item=_item from=$_errorContainer}>
					<div class="dialogerror"><div class="dialogerrorsub"><div class="dialogerrorcontent"><{$_item[message]}></div></div></div>
				<{/foreach}>
				<{foreach key=key item=_item from=$_infoContainer}>
					<div class="dialoginfo"><div class="dialoginfosub"><div class="dialoginfocontent"><{$_item[message]}></div></div></div>
				<{/foreach}>
			</div>
			<{if $_userIsLoggedIn != true}>
			<script type="text/javascript">
			    // On login page: move the error/info messages below the Login button
			    document.addEventListener('DOMContentLoaded', function() {
			        var msgs = document.getElementById('hd-messages');
			        if (!msgs || !msgs.children.length) return;
			        var loginBtn = document.getElementById('loginsubscribebuttons');
			        if (loginBtn && loginBtn.parentNode) {
			            loginBtn.parentNode.insertBefore(msgs, loginBtn.nextSibling);
			            msgs.style.padding = '12px 22px 0';
			        }
			    });
			</script>
			<{/if}>

			<div class="innerwrapper">
        	    <div id="maincoreleft">
 					<div id="leftloginsubscribebox">
              			<{if $_userIsLoggedIn == true}>
                            <!-- Sidebar hidden via CSS on inner pages.
                                 maitem links kept with Redirect() — they work when sidebar visible
                                 (home/profile/prefs pages) and are unused when hidden by CSS. -->
                            <div class="tabrow" id="leftloginsubscribeboxtabs">
                                <a id="leftloginsubscribeboxlogintab" href="#" class="atab">
                                    <span class="tableftgap">&nbsp;</span>
                                    <span class="tabbulk"><span class="tabtext" title="<{$_language[myaccount]}>"><{$_language[myaccount]}></span></span>
                                </a>
                            </div>
	                        <div id="leftloginbox" class="switchingpanel active">
	                            <div class="maitem maprofile" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/UserAccount/Profile');"><{$_language[maprofile]}></div>
	                            <{if ($_settings[user_orgprofileupdate] == 'allusers' && $_user[userorganizationid] != '0') || ($_user[userrole] == 2 && $_settings[user_orgprofileupdate] == 'managersonly' && $_user[userorganizationid] != '0')}>
	                                <div class="maitem maorganization" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/UserAccount/MyOrganization');"><{$_language[maorganization]}></div>
	                            <{/if}>
		                        <{foreach key=_itemID item=_navbarMenuItem from=$_navbarMenuItemContainer}>
		                            <div class="maitem<{if $_navbarMenuItem[class] != ''}> <{$_navbarMenuItem[class]}><{/if}>" onclick="javascript: Redirect('<{$_navbarMenuItem[link]}>');"><{$_navbarMenuItem[title]}></div>
		                        <{/foreach}>
			                    <div class="maitem mapreferences" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/UserAccount/Preferences');"><{$_language[mapreferences]}></div>
			                    <div class="maitem malogout" onclick="javascript: Redirect('<{$_baseName}><{$_templateGroupPrefix}>/Base/User/Logout');"><{$_language[malogout]}></div>
			                </div>

	                        <{else}>

							<form method="post" action="<{$_baseName}><{$_templateGroupPrefix}>/Base/User/Login" name="LoginForm">
								<{if $_canSubscribeNews == true}>
								<div class="tabrow" id="leftloginsubscribeboxtabs">
								    <a id="leftloginsubscribeboxlogintab" href="javascript:void(0);" onclick="ActivateLoginTab();" class="atab">
								        <span class="tabbulk"><span class="tabtext" title="<{$_language[login]}>"><{$_language[login]}></span></span>
								    </a>
								    <a id="leftloginsubscribeboxsubscribetab" href="javascript: void(0);" onclick="javascript: ActivateSubscribeTab();" class="atab inactive">
								        <span class="tabbulk"><span class="tabtext" title="<{$_language[subscribe]}>"><{$_language[subscribe]}></span></span>
								    </a>
								</div>
								<{else}>
								<div class="loginbox-header">
								    <h2 class="loginbox-title"><{$_language[login]}></h2>
								</div>
								<{/if}>
								<div id="leftloginbox" class="switchingpanel active">
									<input type="hidden" name="_redirectAction" value="<{$_redirectAction}>" />
									<input type="hidden" name="_csrfhash" value="<{$_csrfhash}>" />
									<div class="inputframe zebraeven">
									    <input class="loginstyled" placeholder="GW Email | Domain Username" value="<{if $_userLoginEmail != ''}><{$_userLoginEmail}><{/if}>" name="scemail" type="text" autocomplete="email">
									</div>
									<div class="inputframe zebraodd">
									    <input class="loginstyled" placeholder="GW | Domain Password" value="<{$_userLoginPassword}>" name="scpassword" type="password" autocomplete="off">
									</div>
									<div class="inputframe remembermeDiv">
									    <label class="remembermeLabel" for="leftloginboxrememberme">
									        <input id="leftloginboxrememberme" name="rememberme" value="1" type="checkbox"<{if $_userRememberMe == true}> checked<{/if}>>
									        <span id="leftloginboxremembermetext"><{$_language[rememberme]}></span>
									    </label>
									</div>
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
									<div id="divCheckbox" style="display: none;">
									<label> <input name="registrationconsent" type="checkbox" checked/> <{$_language[regpolicytext]}> <a href="<{$_registrationPolicyURL}>" target="_blank"> <{$_language[regpolicyurl]}> </a></label>
									<br />
									</div>
									</div>
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
						<div class="leftnavboxtitle"><span class="leftnavboxtitletext"><{$_language[filterkb]}></span></div>
						<div class="leftnavboxcontent">
							<{foreach key=_knowledgebaseCategoryID item=_knowledgebaseCategory from=$_navKnowledgebaseCategoryContainer}>
								<a class="zebraeven" href="<{$_baseName}><{$_templateGroupPrefix}>/Knowledgebase/List/Index/<{$_knowledgebaseCategoryID}>/<{$_knowledgebaseCategory[seotitle]}>"><{if $_knowledgebaseCategory[totalarticles] > 0}><span class="graytext"><{$_knowledgebaseCategory[totalarticles]}></span><{/if}><{$_knowledgebaseCategory[title]}></a>
							<{/foreach}>
						</div>
					</div>
				    <{/if}>

				    <{if $_filterNews == true}>
				  	    <div class="leftnavboxbox">
				  		    <div class="leftnavboxtitle"><span class="leftnavboxtitletext"><{$_language[filternews]}></span></div>
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


			<script type="text/javascript">
			/* Fix: Kayako does not validate select[multiple] as mandatory.
			   Also: on Submit Ticket page, validate ALL required fields client-side
			   so they all highlight together (server returns errors one at a time). */
			$(document).ready(function() {

			    // Validate all required multiple selects
			    function validateMultipleSelects() {
			        var allValid = true;
			        $('select[multiple]').each(function() {
			            var $select = $(this);
			            var $row = $select.closest('tr');
			            if ($row.find('.customfieldrequired').length > 0) {
			                var hasSelection = $select.val() && $select.val().length > 0;
			                if (!hasSelection) {
			                    $select.addClass('swifttexterror');
			                    allValid = false;
			                } else {
			                    $select.removeClass('swifttexterror');
			                }
			            }
			        });
			        return allValid;
			    }

			    // Validate all required text/select fields on SubmitTicketForm
			    function validateAllRequired() {
			        var allValid = true;
			        // Required text inputs and textareas (rows with .customfieldrequired)
			        $('tr').each(function() {
			            var $row = $(this);
			            if ($row.find('.customfieldrequired').length === 0) return;
			            // Text inputs
			            $row.find('input[type="text"], textarea').each(function() {
			                if ($.trim($(this).val()) === '') {
			                    $(this).addClass('swifttexterror');
			                    allValid = false;
			                } else {
			                    $(this).removeClass('swifttexterror');
			                }
			            });
			            // Regular selects (not multiple)
			            $row.find('select:not([multiple])').each(function() {
			                if (!$(this).val()) {
			                    $(this).addClass('swifttexterror');
			                    allValid = false;
			                } else {
			                    $(this).removeClass('swifttexterror');
			                }
			            });
			            // Radio groups
			            var $radios = $row.find('input[type="radio"]');
			            if ($radios.length > 0 && $radios.filter(':checked').length === 0) {
			                $radios.addClass('swifttexterror');
			                allValid = false;
			            } else {
			                $radios.removeClass('swifttexterror');
			            }
			        });
			        // Subject and Body fields have no .customfieldrequired span — check by name
			        $('input[name="ticketsubject"], textarea[name="ticketmessage"]').each(function() {
			            if ($.trim($(this).val()) === '') {
			                $(this).addClass('swifttexterror');
			                allValid = false;
			            } else {
			                $(this).removeClass('swifttexterror');
			            }
			        });
			        // Also validate multiple selects
			        return validateMultipleSelects() && allValid;
			    }

			    // Patch checkMandatoryCustomFields for View Ticket (Add Reply)
			    var _origCheck = window.checkMandatoryCustomFields;
			    window.checkMandatoryCustomFields = function() {
			        var result = (_origCheck ? _origCheck() : true);
			        // Validate reply body — only when reply form is visible
			        var $replyContainer = $('#postreplycontainer');
			        if ($replyContainer.is(':visible')) {
			            var $reply = $replyContainer.find('textarea[name="replycontents"]');
			            if ($reply.length && $.trim($reply.val()) === '') {
			                $reply.addClass('swifttexterror');
			                result = false;
			            } else {
			                $reply.removeClass('swifttexterror');
			            }
			        }
			        var multiValid = validateMultipleSelects();
			        return result && multiValid;
			    };
			    // Store the reply header text from Kayako language string
			    var _replyHeaderText = '<{$_language[ticket_reply_message]}>';
			    // Change only the FIRST hlineheader text in reply form
			    function fixReplyHeaderText() {
			        if (!_replyHeaderText || _replyHeaderText === '') return;
			        var $th = $('#postreplycontainer table.hlineheader:first th:first');
			        if ($th.length && $th.text() !== _replyHeaderText) {
			            $th.text(_replyHeaderText);
			        }
			    }
			    // Use MutationObserver to catch any DOM changes in postreplycontainer
			    // and immediately re-apply the correct text
			    var _replyObserver = null;
			    function startReplyObserver() {
			        var container = document.getElementById('postreplycontainer');
			        if (!container || _replyObserver) return;
			        _replyObserver = new MutationObserver(function() {
			            fixReplyHeaderText();
			        });
			        _replyObserver.observe(container, { childList: true, subtree: true, characterData: true });
			    }
			    $(document).on('click', '#addreplybutton', function() {
			        setTimeout(function() {
			            fixReplyHeaderText();
			            startReplyObserver();
			        }, 50);
			    });
			    $(document).on('click', '.ticketbarquote', function() {
			        setTimeout(function() {
			            fixReplyHeaderText();
			            startReplyObserver();
			        }, 50);
			    });

			    // Intercept form submit for Submit Ticket — validate all fields client-side
			    $(document).on('submit', 'form[name="SubmitTicketForm"]', function(e) {
			        if (!validateAllRequired()) {
			            e.preventDefault();
			            e.stopPropagation();
			            // Scroll to first error
			            var $first = $('.swifttexterror').first();
			            if ($first.length) {
			                $('html, body').animate({ scrollTop: $first.offset().top - 120 }, 300);
			            }
			            $(this).find('input[type="submit"]').prop('disabled', false);
			            return false;
			        }
			    });

			    // Clear error on input
			    $(document).on('input change', '.swifttexterror', function() {
			        var val = $(this).val();
			        if (val && ($(this).is('select[multiple]') ? val.length > 0 : $.trim(val) !== '')) {
			            $(this).removeClass('swifttexterror');
			        }
			    });
			    $(document).on('change', 'input[type="radio"].swifttexterror', function() {
			        var name = $(this).attr('name');
			        $('input[name="' + name + '"]').removeClass('swifttexterror');
			    });
			});
			</script>