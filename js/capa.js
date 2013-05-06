
	jQuery(document).ready( function($) {
		$('a[href*=#capa-scroll-]').click(function() {
			if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
				var $target = $(this.hash);
				$target = $target.length && $target || $('[name=' + this.hash.slice(1) +']');

				if ($target.length) {
					var targetOffset = $target.offset().top;

					$('html,body').animate({scrollTop: targetOffset}, 1000);
					return false;
				}
			}
		});
	});

// Mass Check / Uncheck
	function capa_check(element,trigger){
			val = document.getElementById(trigger).checked;
				if(val){ val = "checked"; }else{ val = ""; }

			all_check_boxes = document.getElementsByName(element);
				for (var i = 0; i < all_check_boxes.length; i++)
						all_check_boxes[i].checked = val;
	}

	function capa_check_slug(element,trigger){
		val = document.getElementById(trigger).value;
		the_form = document.capa_protect;

			if(val > 0){
				val = "";
				document.getElementById(trigger).value = 0;
			}else{
				val = "checked";
				document.getElementById(trigger).value = 1;
			}

		for(i=0, n=the_form.length; i < n; i++){
			if(the_form.elements[i].className.indexOf(element) != -1){
				the_form.elements[i].checked = val;
			}
		}

	}

	function capa_get_checked_value(radioObj) {
		if(!radioObj)
			return "";

		var radioLength = radioObj.length;

			if (radioLength == undefined) {
				if (radioObj.checked)
					return radioObj.value;
				else
					return "";
			}

				for(var i = 0; i < radioLength; i++) {
					if(radioObj[i].checked)
						return radioObj[i].value;
				}
		return "";
	}


	function capa_enable_disable_form_elements(){
		the_form = document.capa_protect;

		if (capa_get_checked_value(the_form.capa_protect_post_policy) == "show title") {
			document.getElementById('capa_protect_private_message').style.color = 'black';
			the_form.capa_protect_private_message.disabled = false;
			the_form.capa_protect_private_message.style.color = "black";
		}else{
			if(capa_get_checked_value(the_form.capa_protect_comment_policy) == "show name"){
				document.getElementById('capa_protect_private_message').style.color = 'black';
				the_form.capa_protect_private_message.disabled = false;
				the_form.capa_protect_private_message.style.color = "black";
			}else{
				document.getElementById('capa_protect_private_message').style.color = 'gray';
				the_form.capa_protect_private_message.disabled = true;
				the_form.capa_protect_private_message.style.color = "gray";
			}
		}


		if (the_form.capa_protect_show_only_allowed_attachments.checked) {
			document.getElementById('capa_protect_show_unattached_files').style.color = 'black';
			the_form.capa_protect_show_unattached_files.disabled = false;
		} else {
			document.getElementById('capa_protect_show_unattached_files').style.color = 'gray';
			the_form.capa_protect_show_unattached_files.disabled = true;
		}


		if (the_form.capa_protect_show_private_categories.checked) {
			document.getElementById('capa_protect_show_padlock_on_private_categories').style.color = 'black';
			the_form.capa_protect_show_padlock_on_private_categories.disabled = false;
		} else {
			document.getElementById('capa_protect_show_padlock_on_private_categories').style.color = 'gray';
			the_form.capa_protect_show_padlock_on_private_categories.disabled = true;
		}


	}
