<form method="post" action="<{$_baseName}><{$_templateGroupPrefix}>/Base/UserRegistration/RegisterSubmit" enctype="multipart/form-data" name="RegisterForm" autocomplete="off">
			<div class="boxcontainer">
			<div class="boxcontainerlabel"><{$_language[registertitle]}></div>

			<div class="boxcontainercontent">
			<{$_language[registerdesc]}><br /><br />
			<table class="hlineheader"><tr><th rowspan="2" nowrap><{$_language[generalinformation]}></th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
			<table width="100%" border="0" cellspacing="1" cellpadding="4">
				<tr>
					<td width="200" align="left" valign="middle" class="zebraodd"><{$_language[regfullname]}></td>
					<td><input name="fullname" type="text" size="25" class="swifttextlarge" value="<{$_userFullName}>" /></td>
				</tr>
				<tr>
					<td align="left" valign="middle" class="zebraodd"><{$_language[regemail]}></td>
					<td><input name="regemail" type="text" size="25" class="swifttextlarge" value="<{$_userEmail}>" /></td>
				</tr>
				<tr>
					<td align="left" valign="middle" class="zebraodd"><{$_language[regpassword]}></td>
					<td><input name="regpassword" type="password" size="20" class="swifttextlarge" /></td>
				</tr>
				<tr>
					<td align="left" valign="middle" class="zebraodd"><{$_language[regpasswordrepeat]}></td>
					<td><input name="passwordrepeat" type="password" size="20" class="swifttextlarge" /></td>
				</tr>
			</table>
			<br />
<!--
			<label> <input name="registrationconsent" type="checkbox"/> <{$_language[regpolicytext]}> <a href="<{$_registrationPolicyURL}>" target="_blank"> <{$_language[regpolicyurl]}> </a></label>
-->
			<div id="divCheckbox" style="display: none;">
			<label> <input name="registrationconsent" type="checkbox" checked/> <{$_language[regpolicytext]}> <a href="<{$_registrationPolicyURL}>" target="_blank"> <{$_language[regpolicyurl]}> </a></label>
			<br />
			</div>
			<br />
			<{RenderTemplate name="customfields"}>
			<{if $_canCaptcha == true}>
			<table class="hlineheader"><tr><th rowspan="2"><{$_language[verifyregistration]}></th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
			<{if  $_isRecaptcha == true}>
			<div class="subcontent"><{$_language[recaptchadesc]}></div>
			<{else}>
			<div class="subcontent"><{$_language[captchadesc]}></div>
			<{/if}>
			<{$_captchaHTML}>
			<br />
			<{/if}>
			<{$_registerExtendedForms}>

			<div class="subcontent"><input class="rebuttonwide2 rebuttonwide2final" value="<{$_language[regsignup]}>" type="submit" name="button" /></div>

			</div>
			</div>
		</form>