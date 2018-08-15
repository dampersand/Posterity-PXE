//Global Variables Section
var payloadEntry;
var payloads;

//functions

//function to handle errors - this is a living function, not yet an all-encompassing one.
//put various handling here.
function errorHandle(data) {
	alert(data['text']);
}

//function to get all the existing pxe payloads
function getPayloads() {
	$.ajax({
		type: "GET",
		url: "resources/api/getSystems.php",
		success:function(data) {
			if (!$.parseJSON(data)['success']) {
				errorHandle($.parseJSON(data));
			} else {
				payloads = $.parseJSON(data)['data'];
				buildPayloadTable();
			}
		},
		error:function(data) {
			var outputData = $.parseJSON(data.responseText);
			errorHandle(outputData);
		}
	});
}

//wipe the payload table and re-make it with data in 'payloads'
function buildPayloadTable() {
	//remove old payloads from the table
	$(".payload").remove();

	//build payload entries and attach them to table
	$(payloads).each(function(index, payload){
		var newLoad = payloadEntry.clone();
		newLoad.data("index",index);
		newLoad.html(newLoad.html() + payload['name']);
		newLoad.click(function(){selectMe(this)});
		$("#leftColumn").append(newLoad);
	});

	//clear form
	$("#payloadForm")[0].reset();

	//select 'new payload'
	$(".selectedPayload").removeClass("selectedPayload");
	$(".newLoad").addClass("selectedPayload");
	$(".newLoad").find(".leftTriangle").removeClass("leftTriangle").addClass("selectedTriangle");
	$("[name=name]").prop("disabled", false);
}

//function to select a given payload
function selectMe(thisObj) {
	$(".selectedPayload").removeClass("selectedPayload");
	$(".selectedTriangle").removeClass("selectedTriangle").addClass("leftTriangle");
	$(thisObj).find(".leftTriangle").addClass("selectedTriangle");
	$(thisObj).addClass("selectedPayload");

	//if the 'new payload' button was clicked, 
	if ($(thisObj).hasClass("newLoad")) {
		$("#payloadForm")[0].reset();
		$("[name=name]").prop("disabled", false);
		$(".ipInfo").prop("disabled",false);
		return;
	}

	//if a current payload button was clicked, disable the 'name' box
	$("[name=name]").prop("disabled", true);

	//populate the payload form
	var ind = $(thisObj).data("index");
	$("[name=name]").val(payloads[ind]['name']);
	$("#profileMenu option[value=" + payloads[ind]['profile'].replace(/\./g, '\\.') + "]").prop('selected',true); //dear god the regex.  Thanks, jquery, for not being able to pass yourself string literals.
	//$("[value=" + payloads[ind]['profile'].replace(/\./g, '\\.') + "]").prop('checked','checked'); //dear god the regex.  Thanks, jquery, for not being able to pass yourself string literals.
	$("[name=IP").val(payloads[ind]['ks_meta']['ip']);
	$("[name=netmask]").val(payloads[ind]['ks_meta']['netmask']);
	$("[name=gateway]").val(payloads[ind]['ks_meta']['gateway']);
	$("[name=interface]").val(payloads[ind]['ks_meta']['device']);
	$("[name=mac]").val(payloads[ind]['interfaces']['eth1']['mac_address']);
	if (payloads[ind]['netboot_enabled'] == true) {
		$("[name=netboot-enabled][value=true]").prop('checked','checked');
	} else {
		$("[name=netboot-enabled][value=false]").prop('checked','checked');
	}
	if (payloads[ind]['ks_meta']['install_option'] == 'vanilla') {
		$("[name=install_option]").prop('checked','checked');
	} else {
		$("[name=install_option]").prop('checked','');
	}
	if ("swraid" in payloads[ind]['ks_meta']) {
		if (payloads[ind]['ks_meta']['swraid'] == true || payloads[ind]['ks_meta']['swraid'] == 'True' || payloads[ind]['ks_meta']['swraid'] == 'true') {
			$("[name=swraid]").prop('checked','checked');
		} else {
			$("[name=swraid]").prop('checked','');
		}
	} else {
		$("[name=swraid]").prop('checked','');
	}
	if ("gpt" in payloads[ind]['ks_meta']) {
		if (payloads[ind]['ks_meta']['gpt'] == true || payloads[ind]['ks_meta']['gpt'] == 'True' || payloads[ind]['ks_meta']['gpt'] == 'true') {
			$("[name=gpt]").prop('checked','checked');
		} else {
			$("[name=gpt]").prop('checked','');
		}
	} else {
		$("[name=gpt]").prop('checked','');
	}

	//if profile was using a yesip/noip thing, disable/enable ipinfo
	$(".noIP").each(function() {
		if(this.selected) {
			$(".ipInfo").prop("disabled",true);
		}
	});

	$(".yesIP").each(function() {
		if(this.selected) {
			$(".ipInfo").prop("disabled",false);
		}
	});
}

//function to delete a given payload
function deletePayload() {

	//don't do anything if 'new payload' was selected
	if ($(".selectedPayload").hasClass("newLoad")) {
		return;
	}

	var inputJSON = new Object;
	inputJSON['name'] = payloads[$(".selectedPayload").data("index")]['name'];

	$.ajax({
		type: "POST",
		data: inputJSON,
		url: "resources/api/deleteSystem.php",
		success:function(data) {
			if (!$.parseJSON(data)['success']) {
				errorHandle($.parseJSON(data));
			} else {
				getPayloads();
			}
		},
		error:function(data) {
			var outputData = $.parseJSON(data.responseText);
			errorHandle(outputData);
		}
	});
}

//function to submit a new payload (or edits to same)
function submitPayload() {

	//if the name is empty, stop, because I don't wanna error out.
	if ($("[name=name]").val() == '') {
		return;
	}

	//if the MAC address isn't fully fleshed out, or is in use elsewhere, stop, cuz I don't wanna error out.
	if ($("[name=mac]").val().indexOf("_") >=0 || $("[name=mac]").val() == "") {
		alert("Cannot continue - new or updated payloads MUST have a full mac address.");
		return;
	}

	for (index in payloads) {
		if (payloads[index]['interfaces']['eth1']['mac_address'].toUpperCase() == $("[name=mac]").val().toUpperCase() && payloads[index]['name'] != $("[name=name]").val()) {
			alert("Cannot continue - mac address already in use elsewhere.");
			return;
		}
	}
	//build the input JSON from the payload form
	var inputJSON = new Object;
	inputJSON['name'] = $("[name=name]").val().replace(/_/g, "");
	inputJSON['ip'] = $("[name=IP]").val().replace(/_/g, "");
	inputJSON['netmask'] = $("[name=netmask]").val().replace(/_/g, "");
	inputJSON['gateway'] = $("[name=gateway]").val().replace(/_/g, "");
	inputJSON['interface'] = $("[name=interface]").val();
	inputJSON['mac'] = $("[name=mac]").val().replace(/_/g, "");

	//set install_option to full_install by default.  If vanilla is checked, it'll get overwritten.
	inputJSON['install_option'] = 'full_install';

	//set all checked boxes in inputJSON
	//I'm gonna let php determine the difference between 'true' and true, because it seems impossible to pass booleans via post anyway.  Everything at this level is strings.
	$("input:checked").each(function(){
		inputJSON[$(this).attr("name")] = $(this).val();
	});

	$("option:selected").each(function(){
  	inputJSON[$(this).parent("select").attr("name")] = $(this).val();
  });

	$.ajax({
		type: "POST",
		data: inputJSON,
		url: "resources/api/submitSystem.php",
		success:function(data) {
			if (!$.parseJSON(data)['success']) {
				errorHandle($.parseJSON(data));
			} else {
				alert('Payload creation/edit accepted by the server!')
				getPayloads();
			}
		},
		error:function(data) {
			var outputData = $.parseJSON(data.responseText);
			errorHandle(outputData);
		}
	});
}


//Click handlers and things to be run on ready state
$(document).ready(function(){

	//clone repeatable elements
	payloadEntry = $(".payload").clone(true);
	$(".remove").remove();

	//add click handlers
	$(".newLoad").click(function(){selectMe(this)});
	$("#deleteButton").click(deletePayload);
	$("#submitButton").click(submitPayload);
	$("#profileMenu").change(function(){
		if ($("#profileMenu option:selected").hasClass("noIP")) {
			$(".ipInfo").prop("disabled",true);
		} else if ($("#profileMenu option:selected").hasClass("yesIP")) {
			$(".ipInfo").prop("disabled",false);
		}
	});

	//set input masks
	$("[name=IP]").inputmask('ip');
	$("[name=netmask]").inputmask('ip');
	$("[name=gateway]").inputmask('ip');
	$("[name=mac]").inputmask('mac');

	//initialize existing payloads
	getPayloads();

});
