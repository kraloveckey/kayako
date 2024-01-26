<form class="submitticketform" method="post" action="<{$_baseName}><{$_templateGroupPrefix}>/Tickets/Submit/Confirmation" name="SubmitTicketForm" enctype="multipart/form-data" autocomplete="off">
			<div class="boxcontainer">
			<div class="boxcontainerlabel"><{$_language[yourticketdetailstitle]}></div>

			<div class="boxcontainercontent">
			<{$_language[yourticketdetailsdesc]}><br /><br />
			<table class="hlineheader"><tr><th rowspan="2" nowrap><{$_language[generalinformation]}></th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
			<div class="form-table">
			<table width="100%" border="0" cellspacing="1" cellpadding="4">
				<{if $_noContactDetails != '1'}>
				<tr>
					<td width="200" align="left" valign="middle" class="zebraodd"><{$_language[st_fullname]}></td>
					<td><input name="ticketfullname" maxlength="120" type="text" size="25" class="swifttextlarge" value="<{$_ticketFullName}>" /></td>
				</tr>
				<tr>
					<td align="left" valign="middle" class="zebraodd"><{$_language[st_email]}></td>
					<td><input name="ticketemail" type="text" size="25" class="swifttextlarge" value="<{$_ticketEmail}>" /></td>
				</tr>
				<{/if}>
				<{if $_promptTicketType == '1'}>
				<tr>
					<td width="200" align="left" valign="middle" class="zebraodd"><{$_language[st_type]}></td>
					<td><select name="tickettypeid" class="swiftselect"><{foreach key=key item=_item from=$_ticketTypeContainer}>
					<option value="<{$_item[tickettypeid]}>"<{if $_item[selected] == true}> selected="selected"<{/if}>><{escape $_item[title]}></option>
					<{/foreach}></select></td>
				</tr>
				<{/if}>
				<{if $_promptTicketPriority == '1'}>
				<tr>
					<td width="200" align="left" valign="middle" class="zebraodd"><{$_language[st_priority]}></td>
					<td><select name="ticketpriorityid" class="swiftselect"><{foreach key=key item=_item from=$_ticketPriorityContainer}>
					<option value="<{$_item[priorityid]}>"<{if $_item[selected] == true}> selected="selected"<{/if}>><{escape $_item[title]}></option>
					<{/foreach}></select></td>
				</tr>
				<{/if}>
			</table>
			</div>
			<br />
			<{RenderTemplate name="customfields"}>

			<table class="hlineheader"><tr><th rowspan="2" nowrap><{$_language[st_messagedetails]}></th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
			<div class="form-table">
			<table width="100%" border="0" cellspacing="1" cellpadding="4">
	
			</table>
			<table width="100%" border="0" cellspacing="1" cellpadding="4">
				<tr>
					<td width="200" align="left" valign="middle" class="zebraodd"><{$_language[st_subject]}></td>
					<td width=""><input name="ticketsubject" type="text" size="45" class="swifttextwide" id="ticketsubject" value="<{$_ticketSubject}>" /></br></br></td>
				</tr>
				<tr>
					<td colspan="2" align="left" valign="top"><textarea name="ticketmessage" id="ticketmessage" cols="25" rows="15" class="swifttextareawide"><{$_ticketMessage}></textarea><div id="irscontainer" class="irscontainer"><div class="irsui"><div class="irstitle"><{$_language[irsloading]}></div></div></div></td>
				</tr>
			</table>
			</div>
			<br />

			<{if $_settings[t_csccrecipients] == '1'}>
            <table class="hlineheader"><tr><th rowspan="2" nowrap><{$_language[st_recipients]}></th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
            <div class="subcontent"><{$_language[st_recipientsdesc]}></div>
            <table width="100%" border="0" cellspacing="1" cellpadding="4">
            <tr>
                <td width="200" align="left" valign="middle" class="zebraodd"><{$_language[st_cc]}></td>
                <td width=""><input name="ticketcc" type="text" size="25" class="swifttextwide" id="ticketcc" value="<{$_ticketCC}>" /></td>
            </tr>
            </table>
			<br />
			<{/if}>

			<{if $_settings[t_cenattach] == '1'}>
			<table class="hlineheader"><tr><th rowspan="2" nowrap><{$_language[uploadfiles]}> [<div class="addplus"><a href="#ticketattachmentcontainer" onclick="javascript: AddTicketFile();"><{$_language[taddfile]}></a></div>]</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
			<div id="ticketattachmenterror" class="error"><{$_language[requiredfieldempty]}></div>
			<div id="ticketattachmentcontainer">
			</div>
			<{/if}>

			<br />
<!--
			<label> <input name="registrationconsent" type="checkbox"/> <{$_language[regpolicytext]}> <a href="<{$_registrationPolicyURL}>" target="_blank"> <{$_language[regpolicyurl]}> </a></label>
-->
			<div id="divCheckbox" style="display: none;">
			<label> <input name="registrationconsent" type="checkbox" checked/> <{$_language[regpolicytext]}> <a href="<{$_registrationPolicyURL}>" target="_blank"> <{$_language[regpolicyurl]}> </a></label>
			<br />
			</div>
			<br />

			<{if $_canCaptcha == true}>
			<table class="hlineheader"><tr><th rowspan="2"><{$_language[verifyticketsubmission]}></th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
			<{if  $_isRecaptcha == true}>
			<div class="subcontent"><{$_language[recaptchadesc]}></div>
			<{else}>
			<div class="subcontent"><{$_language[captchadesc]}></div>
			<{/if}>
			<{$_captchaHTML}>
			<br />
			<{/if}>

			<div class="subcontent"><input class="rebuttonwide2" value="<{$_language[buttonsubmit]}>" type="submit" name="button" /><input type="hidden" name="departmentid" value="<{$_departmentID}>" /><input type="hidden" name="_csrfhash" value="<{$_csrfhash}>" /></div>
			<script type="text/javascript">
                $(window).on('beforeunload', function () {
                $("input[type=submit], input[type=button]").prop("disabled", "disabled");
               });
            </script>
			<{if $_canIRS == true}>
			<script type="text/javascript">
			StartIRS();
			</script>
			<{/if}>
			</div>
			</div>
		</form>