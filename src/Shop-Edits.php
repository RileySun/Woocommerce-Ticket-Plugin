<?php

add_action( 'woocommerce_before_variations_form', 'calenderPicker' );
function calenderPicker() {
	global $product;
	$ID = $product->get_id();
	
	$ticketFirstDate = get_post_meta( $ID, "Ticket-Date", true);
	$ticketFirstTime = get_post_meta( $ID, "Ticket-Time", true);
	
	$ticketAnotherTime = get_post_meta( $ID, "Ticket-Recur-Another-Time", true);
	$ticketTimes = explode(",", $ticketAnotherTime);
	array_unshift($ticketTimes, $ticketFirstTime);
	
	$ticketAnotherDate = get_post_meta( $ID, "Ticket-Recur-Another-Date", true);
	$ticketDates = explode(",", $ticketAnotherDate);
	array_unshift($ticketDates, $ticketFirstDate);
	
	if (isset($_GET['date'])) {
		$date = $_GET['date'];
	}
	else {
		$date = "";
	}
	if (isset($_GET['time'])) {
		$time = $_GET['time'];
	}
	else {
		$time = "";
	}
	
	$html = '
<link href="https://code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css" rel="stylesheet">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.11.3/jquery-ui.min.js"></script>
<div class="Woocommerce-Ticket">
	<h4>Ticket Date</h4>
	<input id="Woocommerce-Ticket-Picker-Date" name="Woocommerce-Ticket-Picker-Date" type="text" value="" /><br />
	<h4>Ticket Time</h4>
	<select id="Woocommerce-Ticket-Picker-Time" name="Woocommerce-Ticket-Picker-Time"></select>
	<div id="Woocommerce-Ticket-Time-Available"></div>
</div>
<style>
	.Woocommerce-Ticket {
		position: relative;
	}
	.Woocommerce-Ticket h4, .Woocommerce-Ticket input, .Woocommerce-Ticket select, .Woocommerce-Ticket div {
		display: inline-block;
		margin: 0 1% 4% 0;
		vertical-align: middle;
	}
	.Woocommerce-Ticket input, .Woocommerce-Ticket select {
		font-size: 1.3vw;
	}
	#ui-datepicker-div {
		top: 30% !important;
		right: 6% !important;
	}
</style>
<script>
var phpInput = ['.json_encode($ticketDates).', '.json_encode($ticketTimes).'];
var urlInput = ["'.$date.'", "'.$time.'"];
var ticketDates = phpInput[0].constructor === Array ? phpInput[0] : "";
var ticketTimes = phpInput[1].constructor === Array ? phpInput[1] : "";
function setUp() {
	var datePicker = document.getElementById("Woocommerce-Ticket-Picker-Date");
	var timePicker = document.getElementById("Woocommerce-Ticket-Picker-Time");
	//Disable add to cart till ticket is picked
	
	document.getElementsByClassName("reset_variations")[0].click();
	document.querySelectorAll(".variations select")[0].selectedIndex = 0;
	document.querySelectorAll(".variations select")[0].children[0].textContent = "Please select a date.";
	document.querySelectorAll(".variations select")[0].disabled = true;
	
	//if date from url exists, pick a time
	var selectedDate = urlInput[0].substr(0, 2) != "." ? urlInput[0] : "";
	var selectedTime = urlInput[1].substr(0, 2) != "." ? urlInput[1] : "";
	if (selectedDate != "") {
		var parseDate = Date.parse(selectedDate);
		var nowDate = Date.now();
		var check = nowDate - parseDate;
		//Uknown
		if (check > 86400000) {
			selectedDate = "";
			selectedTime = "";
		}
	}

	//If url select that date
	datePicker.value = selectedDate;
	timePicker.value = selectedTime;
	pickSelectOptions(selectedTime);
	
	//hmmm this might be it
	var datePickerTimeout = function() {window.setTimeout(setUpSelectOptions, 10); window.setTimeout(dateChanged, 30);}
	datePicker.addEventListener("focus", datePickerTimeout, false);
	dateChanged();
	
	$("#Woocommerce-Ticket-Picker-Date").datepicker({beforeShowDay: setDatePicker});
	setUpAddToCart();
};
function setUpAddToCart() {
	document.getElementById("ticket-type").addEventListener("blur", checkAddToCart, false);
	var datePickerCallBack = function() {window.setTimeout(function() {checkAddToCart();}, 100)};
	document.getElementById("Woocommerce-Ticket-Picker-Date").addEventListener("blur", datePickerCallBack, false);
	checkAddToCart();
};
function checkAddToCart() {
	var variation = document.getElementById("ticket-type");
	var button = document.getElementsByClassName("single_add_to_cart_button")[0];
	var date = document.getElementById("Woocommerce-Ticket-Picker-Date");
	var time = document.getElementById("Woocommerce-Ticket-Picker-Time");
	if (variation.value != "" && date.value != "" && time.value != "") {button.classList.remove("disabled");}
	else {button.classList.add("disabled");}
};
function dateChanged() {
	var input = document.getElementById("Woocommerce-Ticket-Picker-Date");
	var target = document.getElementById("Woocommerce-Ticket-Picker-Time")
	var valueTemp = input.value.replace(/\//g, "-");
	var valueFormatted = valueTemp.substr(6, 4) + "-" + valueTemp.substr(0, 2) + "-" + valueTemp.substr(3, 2);
	var value = (input.value.includes("/")) ? valueFormatted : input.value;
	
	for (var i = 0; i < ticketDates.length; i++) {
		if (ticketDates[i] == value) {
			createSelectOption(ticketTimes[i]);
		};
	};
};
function setUpSelectOptions() {
	var pickerLinks = document.getElementsByClassName("ui-state-default");
	var disabledClasses = ["ui-datepicker-unselectable", "ui-state-disabled"]
	for (var i = 0; i < pickerLinks.length; i++) {
		var pickerClasses = pickerLinks[i].parentElement.classList;
		var pickerFunction = function() {clearSelectOptions(); dateChanged();};
		var pickerTimeout = function() {window.setTimeout(pickerFunction, 1);};
		if (!pickerClasses.contains(disabledClasses[0]) || !pickerClasses.contains(disabledClasses[1])) {
			pickerLinks[i].addEventListener("click", pickerTimeout, false);
		};
	};
}
function pickSelectOptions(selection) {
	var select = document.getElementById("Woocommerce-Ticket-Picker-Time");
	var options = select.children;
	
	for (var i = 0; i < options.length; i++) {
		if (options[i].value == selection) {
			select.selectedIndex = select.children.indexOf(options[i]);
		};
	};
};
function clearSelectOptions() {
	var select = document.getElementById("Woocommerce-Ticket-Picker-Time");
	while (select.children.length > 0) {
		select.removeChild(select.children[0]);
	};
};
function createSelectOption(time) {
	var option = document.createElement("option");
	option.value = time;
	option.textContent = formatTime(time);
	document.getElementById("Woocommerce-Ticket-Picker-Time").appendChild(option);
	
	options = document.getElementById("Woocommerce-Ticket-Picker-Time").children;
	document.getElementById("Woocommerce-Ticket-Time-Available").textContent = options.length + " Show Times Available";
	document.querySelectorAll(".variations select")[0].children[0].textContent = "Select ticket type";
	document.querySelectorAll(".variations select")[0].disabled = false;
};
function setDatePicker(date) {
	var year = date.getFullYear();
	var month = ("0" + (date.getMonth() + 1)).slice(-2);
	var day = ("0" + (date.getDate())).slice(-2);
	var formatted = year + "-" + month + "-" + day;
	
	var checkDate = Date.parse(year + "-" + month + "-" + day);
	var nowDate = Date.now();
	var checkResult = nowDate - checkDate;
	
	if ($.inArray(formatted, ticketDates) != -1 && checkResult < 86400000) {
		return [true, "","Available"]; 
	} 
	else {
		return [false,"","unAvailable"]; 
	}
}
function formatTime(time) {
	var date = new Date("January 01, 2000 " + time + ":00");
	var options = {hour: "numeric", minute: "numeric", hour12: true};
	var timeString = date.toLocaleString("en-US", options);
	return timeString;
}
window.addEventListener("load", setUp, false);
</script>
	';
	
	echo $html;
}

add_filter( 'woocommerce_add_cart_item_data', 'itemLoad', 10, 2);
add_filter( 'woocommerce_cart_item_name', 'itemEdits', 10, 3);
function itemLoad($cart_item_data, $product_id) {
	global $woocommerce;
	$dateString = $_POST['Woocommerce-Ticket-Picker-Date'].' '.$_POST['Woocommerce-Ticket-Picker-Time'];
	$ticketDateTime = DateTime::createFromFormat("m/d/Y H:i", $dateString);
	if ($ticketDateTime != false) {
		$cart_item_data['Selected-Ticket'] = array($ticketDateTime->format("m/d/Y"), $ticketDateTime->format("g:i a"));
	}
	return $cart_item_data; 
}
function itemEdits($item_name, $cart_item_data) {
	$end = " |  ".$cart_item_data['Selected-Ticket'][0]." ".$cart_item_data['Selected-Ticket'][1]."</a>";
	$item_name = substr($item_name, 0, -4).$end;
    return $item_name;
}

add_action('woocommerce_checkout_create_order_line_item', 'orderMeta', 10, 4);
function orderMeta($item, $cart_item_key, $values, $order) {
    if( isset( $values['Selected-Ticket'] ) ) {
    	$selectedTicket = $values['Selected-Ticket'][0]." ".$values['Selected-Ticket'][1];
        $item->update_meta_data(__('Ticket', 'woocommerce'), $selectedTicket);
    }
}

?>