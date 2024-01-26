</div>
				<style id="antiClickjack">body{display:none !important;}</style>
				<script type="text/javascript">
					if (self === top) {
						var antiClickjack = document.getElementById("antiClickjack");
						antiClickjack.parentNode.removeChild(antiClickjack);
					} else {
						top.location = self.location;
					}
				</script>
				</div>
				<div class="innerwrapper">
					<div id="bottomfooter" class="bottomfooterpadding"><{$_defaultFooter}></div>
				</div>
		</div>
	</div>
			  <!--SWIFT-4941 Check Custom Tweaks compatibility with SWIFT -->
              <{if $_settings[t_tinymceeditor] == '1'}>
		        <script type="text/javascript" src="<{$_settings[general_producturl]}>__swift/apps/base/javascript/__global/thirdparty/TinyMCE/tinymce.min.js"></script>
		        <script>
		tinyMCE.baseURL = "<{$_settings[general_producturl]}>__swift/apps/base/javascript/__global/thirdparty/TinyMCE/";
		</script>

		<script type="text/javascript">
		tinymce.init({
		    mode : "specific_textareas",
		    editor_selector : "swifttextareawide",
		    paste_data_images: true,
		    image_title: true,
			automatic_uploads: true,
			file_picker_types: "image",
			file_picker_callback: function(cb, value, meta) {
				var input = document.createElement("input");
				input.setAttribute("type", "file");
				input.setAttribute("accept", "image/*");
				input.onchange = function() {
					var file = this.files[0];
					var reader = new FileReader();
					reader.onload = function () {
						var id = "blobid" + (new Date()).getTime();
						var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
						var base64 = reader.result.split(",")[1];
						var blobInfo = blobCache.create(id, file, base64);
						blobCache.add(blobInfo);
						cb(blobInfo.blobUri(), { title: file.name });
					};
					reader.readAsDataURL(file);
				};
				input.click();
			},
		    force_p_newlines : false,
			remove_linebreaks : false,
		    browser_spellcheck : true,
		    entity_encoding : "raw",
		    relative_urls : false,
		    remove_script_host : false,
		    convert_urls : true,
		    gecko_spellcheck: true,
		    force_br_newlines : false,              //btw, I still get <p> tags if this is false
		    remove_trailing_nbsp : false,
		    verify_html : false,
		    theme: "modern",
		    plugins: [
		        "advlist autolink lists link image charmap print preview hr anchor pagebreak",
		        "searchreplace wordcount visualblocks visualchars fullscreen",
		        "insertdatetime media nonbreaking save table directionality",
		        " template paste textcolor codesample"
		    ],
		    toolbar1: "undo redo | styleselect | bold italic underline | fontsizeselect fontselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image print preview media | forecolor backcolor ",
		    image_advtab: true,
		    menubar: "file edit insert view format tools",
		    setup: function(ed) {
			        ed.on('init', function(e) {
			            showEditorValidationError();
			        });
    			}

		});
		</script><!--ENDCUSTOMWYSIWYG-->
        <{/if}>

<!--
        <script type="text/javascript">
        	$(function() {
				$('#i-agree-consent').on('click', function(e) {
					e.preventDefault();
					document.cookie = "SWIFT_prconsenturl="+window.location.href;
					$.ajax({
						type: "POST",
						url: "<{$_baseName}><{$_templateGroupPrefix}>/Base/User/UpdateProcessingConsentAJAX",
						data: $('form.processconsentform').serialize(),
						success: function(response) {
							jQuery("#checkoffscreen").dialog("destroy");
						}
					});
					return false;
				});
			});
		</script>
-->
		<!-- BEGIN MODAL FOR YET TO BE CAPTURED CONSENT LOGGEDIN USER -->
<!--
               <div style="display:none" id="checkoffscreen" class="innerwrapper">
               		<form enctype="multipart/form-data" name="processconsentform" autocomplete="off">
							<div>
								<p><{$_language[regpolicytext]}> <a href="<{$_registrationPolicyURL}>" target="_blank"> <{$_language[regpolicyurl]}> </a></p>
								<input name ="processconsent" type="hidden" value="I Agree">
							</div>
							<div>
								<input id="i-agree-consent" type="submit" class="rebuttonwide2" value="<{$_language[cookiepolicyagreement]}>">
							</div>
					</form>
				</div>
-->
		  </body>
</html>