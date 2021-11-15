<?php

add_action( 'add_meta_boxes', 'ticketsMetaBoxAdd' );
add_action( 'save_post', 'ticketsMetaBoxSave' );
function ticketsMetaBoxAdd() {
    add_meta_box( 'tickets_meta', 'Tickets Meta Boxes', 'ticketsMetaBoxCall', 'product', 'normal', 'low' );
}
function ticketsMetaBoxCall( $post ) {
	$ticketRecur = 0;
	$ticketTemp = get_post_meta( $post->ID, "Ticket-Recur", true);
	if ($ticketTemp == "on") {
		$ticketRecur = 1;
	}
	
	$ticketDate = get_post_meta( $post->ID, "Ticket-Date", true);
	$ticketTime = get_post_meta( $post->ID, "Ticket-Time", true);
	$ticketRecurType = get_post_meta( $post->ID, "Ticket-Recur-Type", true);
	$ticketAnotherDateTemp = get_post_meta( $post->ID, "Ticket-Recur-Another-Date", true);
	$ticketAnotherDate = explode(",", $ticketAnotherDateTemp);
	$ticketAnotherTimeTemp = get_post_meta( $post->ID, "Ticket-Recur-Another-Time", true);
	$ticketAnotherTime = explode(",", $ticketAnotherTimeTemp);
	$ticketTheseTemp = get_post_meta( $post->ID, "Ticket-Recur-These", true);
	$ticketTheseTempArray = explode(",", $ticketTheseTemp);
	$ticketThese = [];
	$ticketAnotherData = [];
	
	for ($i = 0; $i < sizeOf($ticketAnotherDate); $i++) {
		$ticketAnotherData[] = array(
			"date" => $ticketAnotherDate[$i],
			"time" => $ticketAnotherTime[$i]
		);
	}
	if ($ticketAnotherData[0]['date'] == "") {
		$ticketAnotherData = [];
	};
	
	foreach ($ticketTheseTempArray as &$day) {
		if ($day == "on") {
			$ticketThese[] = 1;
		}
		else {
			$ticketThese[] = 0;
		}
	}
	
	$html = '	
		<div class="Tickets-Meta">
			<h4 style="display: inline-block;">Ticket Date</h4>
			<input id="Ticket-Date" name="Ticket-Date" type="date" value="'.$ticketDate.'">
			<input id="Ticket-Time" name="Ticket-Time" type="time" value="'.$ticketTime.'"><br />
			<input id="Ticket-Recur" name="Ticket-Recur" type="checkbox" '.checked( $ticketRecur, 1, false ).' />
			<h4 style="display: inline-block;">Ticket Recurring?</h4><br />
			<div id="Ticket-Recur-Another" style="display: block">
				<input id="Ticket-Recur-Another-Add" name="Ticket-Recur-Another-Add" type="button" value="Another Date" />
			</div><br />
		</div>
		<script>
		var recur, another;
		function setUp() {
			recur = document.getElementById("Ticket-Recur");
			another = document.getElementById("Ticket-Recur-Another");
			add = document.getElementById("Ticket-Recur-Another-Add");

			var anotherData = '.json_encode($ticketAnotherData).';
			if (anotherData != "") {	
				ticketRecurAnother(anotherData);
			};

			recur.addEventListener("change", ticketRecurCheck, false);
			add.addEventListener("click", ticketCreateAnother, false);
			ticketRecurCheck();
		};
		function ticketRecurCheck() {
			var brs = document.querySelectorAll(".Branson-Meta > br");
			if (recur.checked) {
				showHide(another, 1, "inline-block");
				for (var i = 0; i < brs.length; i++) {
					brs[i].style.display = "";
				};
			}
			else {
				showHide(another, 0, "inline-block");
				for (var i = 0; i < brs.length; i++) {
					if (i > 1) {
						brs[i].style.display = "none";
					};
				};
			};
		};
		function ticketRecurAnother(data) {
			for (var i = 0; i < data.length; i++) {
				var dataPoint = data[i];
				ticketCreateAnother(dataPoint);
			};
		};
		function ticketCreateAnother(data) {
			var existing = document.getElementsByClassName("Ticket-Another-Date");
			var high = 1;
			if (high <= existing.length) {
				high = existing.length + 1;
			};

			var add = document.getElementById("Ticket-Recur-Another-Add");
			var date = document.createElement("input");
			var time = document.createElement("input");
			var remove = document.createElement("input");
			var br = document.createElement("br");
			var id;

			date.id = "Ticket-Recur-Another-Date-" + high;
			time.id = "Ticket-Recur-Another-Time-" + high;
			remove.id = "Ticket-Recur-Another-Delete-" + high;

			date.name = "Ticket-Recur-Another-Date-" + high;
			time.name = "Ticket-Recur-Another-Time-" + high;
			remove.name = "Ticket-Recur-Another-Delete-" + high;

			date.classList.add("Ticket-Another-Date");
			time.classList.add("Ticket-Another-Time");
			remove.classList.add("Ticket-Another-Delete");

			date.type = "date";
			time.type = "time";
			remove.type = "button";

			remove.value = "X";

			remove.addEventListener("click", function() {ticketDeleteAnother(this);}, false)

			if (data != undefined) {
				date.value = data.date;
				time.value = data.time;
			};

			add.before(date);
			add.before(time);
			add.before(remove);
			add.before(br);
			add.before(br);
		};
		function ticketDeleteAnother(target) {
			var siblings = target.parentElement.children;
			var targetIndex = Array.prototype.indexOf.call(siblings, target);
			var date = siblings[targetIndex - 2];
			var time = siblings[targetIndex - 1];
			var br = siblings[targetIndex + 1];

			target.parentElement.removeChild(date);
			target.parentElement.removeChild(time);
			target.parentElement.removeChild(br);
			target.parentElement.removeChild(target);
		};
		function showHide(target, dir, type) {
			if (dir == 0) {
				target.style.display = "none";
			}
			else {
				target.style.display = type;
			};
		};
		window.addEventListener("load", setUp, false);
		</script>
	';
	echo $html;
}
function ticketsMetaBoxSave() {
	global $post;
	$post_id = $post->ID;
	
	$anotherDate = "";
	$anotherTime = "";
	foreach($_POST as $key => $value) {
		$one = strpos($key , "Ticket-Recur-Another-Date-");
		if ($one === 0){
			$anotherDate .= $value.",";
		}
		$two = strpos($key , "Ticket-Recur-Another-Time-");
		if ($two === 0){
			$anotherTime .= $value.",";
		};
	}
	$anotherDate = substr($anotherDate, 0, -1);
	$anotherTime = substr($anotherTime, 0, -1);
	
	$another = [];
	
	$list = array('Ticket-Date', 'Ticket-Time', 'Ticket-Recur', 'Ticket-Recur-Another-Date', 'Ticket-Recur-Another-Time');
	foreach ($list as &$listItem) {
		if ($listItem == 'Ticket-Recur-Another-Date') {
			$new_meta_value = $anotherDate;
		}
		else if ($listItem == 'Ticket-Recur-Another-Time') {
			$new_meta_value = $anotherTime;
		}
		else {
			$new_meta_value = ( isset( $_POST[$listItem] ) ? $_POST[$listItem] : '' );
		}
		$meta_key = $listItem;
		$meta_value = get_post_meta( $post_id, $meta_key, true );
		
		if ( $new_meta_value && '' == $meta_value ) {
			add_post_meta( $post_id, $meta_key, $new_meta_value, true );
		}
		elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
			update_post_meta( $post_id, $meta_key, $new_meta_value );
		}
		elseif ( '' == $new_meta_value && $meta_value ) {
			delete_post_meta( $post_id, $meta_key, $new_meta_value );
		}
	}
}

?>