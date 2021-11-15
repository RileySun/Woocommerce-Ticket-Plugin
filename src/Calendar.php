<?php

function createData($offsetChange = 0) {
	$categoryColors = array(
		'Green' => "Music",
		'Orange' => "Comedy",
		'Blue' => "Attractions",
		'Purple' => "Acrobats and Specialty Shows"
	);
	
	date_default_timezone_set( "America/Chicago" );
	$CalendarOffset = -1 + $offsetChange;
	
	if (isset($_GET['Switch'])) {
		$CalenderSwitch = $_GET['Switch'] === 'true' ? true : false;
	}
	else {
		$CalenderSwitch = true;
	}
	
	$productIDs = [];
	$args = array(
		'post_type' => 'product',
		'orderby' => 'ASC',
		'posts_per_page' => -1
	);
	$the_query = new WP_Query($args);
	if ($the_query->have_posts()) {
		while ($the_query->have_posts()) {
			$the_query->the_post(); 
			$ticketID = $the_query->post->ID;
			$productIDs[] = $ticketID;
		}
		wp_reset_postdata();
	}
	
	$CalenderURL = get_permalink($pageID);
	if (!$CalenderSwitch) {
		$CalendarCurrent = createDates($CalendarOffset);
		$CalendarData = createTickets($productIDs, $CalendarOffset, NULL, $categoryColors);
		
		$title = new DateTime('now');
		$title->modify(((int)$CalendarOffset + 1).' month');
		$CalendarTitle = $title->format("F, Y");
	}
	else {
		$weekDates = createWeekDates($CalendarOffset + 1);
		$CalendarCurrent = array(
			(int)substr($weekDates[0], -2), 
			(int)substr($weekDates[0], 5, 2)
		);
		$CalendarData = createTickets($productIDs, $CalendarOffset, $weekDates, $categoryColors);
		
		$titleDate = new DateTime($weekDates[0]);
		$titleLead = "Week of ".substr($weekDates[0], -2)."-".substr($weekDates[6], -2)." ";
		$CalendarTitle = $titleLead.$titleDate->format("F, Y");
	}
	
	$CalenderSwitch = ($CalenderSwitch) ? 1 : 0;
	
	$calendar = createCalendar($CalendarData, $CalendarCurrent, $CalendarTitle, $CalendarOffset, $CalenderSwitch, $CalenderURL, $categoryColors);
	
	echo $calendar;
}

function createDates($offset) {
	$date = new DateTime('now');
	$date->modify($offset.' month');
	$date->modify('last day of this month');
	
	$daysInMonth = (int)$date->format('t');
	$lastDayofMonth = (int)$date->format('w');
	
	if ($lastDayofMonth != 6) {
		$startDay = $daysInMonth - $lastDayofMonth;
	}
	else {
		$startDay = 1;
	}
	
	$month = new DateTime('now');
	$month->modify(((int)$offset + 1).' month');
	
	$datesData = [$startDay, (int)$month->format("n")];
	
	return $datesData;
} //Month View Dates

function createWeekDates($offset) {
	$weekReference = new DateTime('now');
	$currentDay = $weekReference->format("w");
	
	switch ($currentDay) {
		case 0:
			$thisSwitch = ["today", "next monday", "next tuesday", "next wednesday", "next thursday", "next friday", "next saturday"];
			break;
		case 1:
			$thisSwitch = ["previous sunday", "today", "next tuesday", "next wednesday", "next thursday", "next friday", "next saturday"];
			break;
		case 2:
			$thisSwitch = ["previous sunday", "previous monday", "today", "next wednesday", "next thursday", "next friday", "next saturday"];
			break;
		case 3:
			$thisSwitch = ["previous sunday", "previous monday", "previous tuesday", "today", "next thursday", " friday", "next saturday"];
			break;
		case 4:
			$thisSwitch = ["previous sunday", "previous monday", "previous tuesday", "previous wednesday", "today", "next friday", "next saturday"];
			break;
		case 5:
			$thisSwitch = ["previous sunday", "previous monday", "previous tuesday", "previous wednesday", "previous thursday", "today", "next saturday"];
			break;
		case 6:
			$thisSwitch = ["previous sunday", "previous monday", "previous tuesday", "previous wednesday", "previous thursday", "previous friday", "today"];
			break;
	}
	
	$thisWeekRef = array(
		date("Y-m-d", strtotime($thisSwitch[0])),
		date("Y-m-d", strtotime($thisSwitch[1])),
		date("Y-m-d", strtotime($thisSwitch[2])),
		date("Y-m-d", strtotime($thisSwitch[3])),
		date("Y-m-d", strtotime($thisSwitch[4])),
		date("Y-m-d", strtotime($thisSwitch[5])),
		date("Y-m-d", strtotime($thisSwitch[6]))
	);
	$thisWeek = [];
	
	for ($i = 0; $i < sizeOf($thisWeekRef); $i++) {
		$oldDate = 	new DateTime($thisWeekRef[$i]);
		$oldDate->modify($offset." week");
		$thisWeek[] = $oldDate->format("Y-m-d");
	}
	
	return $thisWeek;
} //Week View Dates

function createTickets($ticketIDs, $offset, $weekDatesRef, $categoryColors) {
	$ticketData = [];
	$dates = [];
	$times = [];
	
	if ($weekDatesRef != NULL) {
		$weekDates = $weekDatesRef;
	}
	
	foreach ($ticketIDs as &$ticketID) {
		$ticketPost = get_post($ticketID);
		$ticketRecur = get_post_meta( $ticketPost->ID, "Ticket-Recur", true);
		$ticketType = get_post_meta( $ticketPost->ID, "Ticket-Recur-Type", true);
		$ticketFirstDate = get_post_meta( $ticketPost->ID, "Ticket-Date", true);
		$ticketFirstTime = get_post_meta( $ticketPost->ID, "Ticket-Time", true);
		$ticketThese = get_post_meta( $ticketPost->ID, "Ticket-Recur-These", true);
		
		$ticketIMGID = get_post_meta( $ticketPost->ID, "_thumbnail_id", true);
		$ticketIMG =  get_post($ticketIMGID)->guid;
		
		$terms = get_the_terms($ticketPost->ID, 'product_cat');
		foreach ($terms as &$term) {
			if ($term->name == $categoryColors["Green"]) {
				$ticketColor = "Green";
			}
			else if ($term->name == $categoryColors["Purple"]) {
				$ticketColor = "Purple";
			}
			else if ($term->name == $categoryColors["Orange"]) {
				$ticketColor = "Orange";
			}
			else if ($term->name == $categoryColors["Blue"]) {
				$ticketColor = "Blue";
			}
		}
		
		$ticketAnotherTime = get_post_meta( $ticketPost->ID, "Ticket-Recur-Another-Time", true);
		$times = explode(",", $ticketAnotherTime);
		array_unshift($times, $ticketFirstTime);
		
		$ticketAnotherDate = get_post_meta( $ticketPost->ID, "Ticket-Recur-Another-Date", true);
		$dates = explode(",", $ticketAnotherDate);
		array_unshift($dates, $ticketFirstDate);
		
		for ($i = 0; $i < sizeOf($dates); $i++) {
			$date = $dates[$i];
			
			$month = new DateTime('now');
			$month->modify((int)($offset + 1).' month');
			
			$timeData = new DateTime($times[$i]);
			$military = $timeData->format("G:i");
			$time = $timeData->format("h:i a");
			
			if ($weekDates == NULL) {
				if ($date != "" && substr($date, 0, 7) == $month->format("Y-m")) {
					$slug = "/product/".$ticketPost->post_name."/?date=".$date."&time=".$timeData->format("H:i");
					$ticketLink = site_url($slug);
				
					add_query_arg( array(
						'time' => $date,
						'date' => $time,
					),  $ticketLink);
					$ticketData[] = array(
						"ticket_name" => $ticketPost->post_title,
						"ticket_date" => $date,
						"ticket_time" => $time,
						"ticket_miltary" => $military,
						"ticket_id" => $ticketID,
						"ticket_color" => $ticketColor,
						"ticket_image" => $ticketIMG,
						"ticket_link" => $ticketLink
					);
				}//Last part calls month variable
			}
			else {
				for ($j = 0; $j < sizeOf($weekDates); $j++) {
					if ($weekDates[$j] == $date) {
						$slug = "/product/".$ticketPost->post_name."/?date=".$date."&time=".$timeData->format("H:i");
						$ticketLink = site_url($slug);
				
						add_query_arg( array(
							'time' => $date,
							'date' => $time,
						),  $ticketLink);
				
						$ticketData[] = array(
							"ticket_name" => $ticketPost->post_title,
							"ticket_date" => $date,
							"ticket_time" => $time,
							"ticket_military" => $military,
							"ticket_id" => $ticketID,
							"ticket_image" => $ticketIMG,
							"ticket_link" => $ticketLink,
							"ticket_color" => $ticketColor
						);
					}
				}
			}
		}
	}
	
	return $ticketData;
}

function createCalendar($data, $current, $title, $offset, $switch, $URL, $categoryColors) {
	$html = '
<div class="Calendar">
	<div class="Calendar-View">
		<div class="Calendar-Previous"></div>
		<h1 class="Calendar-Month"></h1>
		<div class="Calendar-Next"></div>
		<div class="Calendar-Spinner"></div>
		<div class="Calendar-Content">
			<div class="Calendar-Row Calendar-Days">
				<h1 class="Calendar-Day-Name">Sunday</h1><!--
			 --><h1 class="Calendar-Day-Name">Monday</h1><!--
			 --><h1 class="Calendar-Day-Name">Tuesday</h1><!--
			 --><h1 class="Calendar-Day-Name">Wednesday</h1><!--
			 --><h1 class="Calendar-Day-Name">Thursday</h1><!--
			 --><h1 class="Calendar-Day-Name">Friday</h1><!--
			 --><h1 class="Calendar-Day-Name">Saturday</h1>
			</div>
			<div class="Calendar-Row">
				<div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div>
			</div>
	';
	
	if (!$switch) {
		$html .= '
			<div class="Calendar-Row">
				<div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div>
			</div>
			<div class="Calendar-Row">
				<div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div>
			</div>
			<div class="Calendar-Row">
				<div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div>
			</div>
			<div class="Calendar-Row">
				<div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div><!--
			 --><div class="Calendar-Day">
					<span class="Calendar-Day-Date"></span>
				</div>
			</div>
		';
	}
	
	$html .= '
<div class="Calendar-Switch-Div">
				<div class="Calendar-Switch-Title">M W</div>
				<div id="Calendar-Switch" class="Calendar-Switch"></div>
			</div>
		<div class="Calender-Content">
	</div>
</div>
		<div class="Calendar-Legend">
			<div class="Calender-Legend-Item">
				<div class="Calender-Legend-Box Green"></div> = '.$categoryColors['Green'].'
			</div>
			<div class="Calender-Legend-Item">
				<div class="Calender-Legend-Box Blue"></div> = '.$categoryColors['Blue'].'
			</div>
			<div class="Calender-Legend-Item">
				<div class="Calender-Legend-Box Orange"></div> = '.$categoryColors['Orange'].'
			</div>
			<div class="Calender-Legend-Item">
				<div class="Calender-Legend-Box Purple"></div> = '.$categoryColors['Purple'].'
			</div>
		</div>
	</div>
</div>

<style>
	.Calendar {
		position: relative;
		width: 92%;
		margin: 2% auto 0;
		text-align: center;
	}
	
	.Calendar-View {
		position: relative;
		display: block;
		width: 100%;
		margin: 0 auto;
		white-space: nowrap;
	}
	.Calendar-Month, .Calendar-Previous, .Calendar-Next {
		display: inline-block;
		color: #000;
		vertical-align: middle;
	}
	.Calendar-Month {
		margin: 0% 2% 1%;
	}
	.Calendar-Previous:hover, .Calendar-Next:hover {
		color: #2b47a5 !important;
	}
	.Calendar-Previous:before, .Calendar-Next:after {
		font-size: 3vw;
		color: inherit !important;
		cursor: pointer;
	}
	.Calendar-Previous:before {
		content: "\25c4";
	}
	.Calendar-Next:after {
		content: "\25ba";
	}
	
	.Calendar-Content {
		display: none;
	}
	.Calendar-Row {
		display: block;
		width: 100%;
		margin: 0;
		text-align: unset;
		white-space: nowrap;
		vertical-align: top;
	}
	.Calendar-Day-Name {
		display: inline-block;
		width: 14.187%;
		margin: 0 auto;
		padding: 1% 0;
		background: #464646;
		color: #FFF;
		text-align: center;
		font-size: 1.5vw;
		font-weight: 100;
		border: 1px solid #666;
		border-right-width: 0px;
		border-top: none;
		border-bottom: none;
		vertical-align: middle;
	}
	h1.Calendar-Day-Name:nth-child(7), div.Calendar-Day:nth-child(7) {
		border-right-width: 1px;
	}
	.Calendar-Day {
		position: relative;
		display: inline-block;
		width: 14.187%;
		min-height: 35vh;
		text-align: right;
		white-space: normal;
		border: 1px solid rgba(0, 0, 0, 0.6);
		border-top: none;
		border-right-width: 0;
		vertical-align: top;
	}
	.Calendar-Day-Date {
		position: absolute;
		top: 10px;
		left: 3%;
		font-size: 1.65vw;
	}
	.Calendar-Day-Date-Grey {
		color: #a8a7a7;
	}
	
	.Calendar-Item {
		position: relative;
		display: inline-block;
		width: 77%;
		margin: 5% 0 0;
		background: #C00C00;
		color: #FFFFFF;
		text-align: left;
		border-top-left-radius: 10px;
		border-bottom-left-radius: 10px;
		cursor: pointer;
	}
	.Calendar-Item-Div:hover  {
		background: #A9A9A9;
		color: #FFFFFF;
	}
	.Calendar-Item-Title {
		display: inline-block;
		margin: 2% 0 2% 7%;
		color: inherit;
		font-size: 1.2vw;
		line-height: 1.7vw;
	}
	.Calendar-Item-Popup {
		position: absolute;
		display: none;
		width: 129%;
		margin: 0;
		top: 0;
		left: 100%;
		background: #FFF;
		color: #000;
		border: 2px solid;
		z-index: 52;
	}
	.Calendar-Row:nth-child(n) > div:nth-child(7) > div:nth-child(n) > div:nth-child(2) {
		top: 100%;
		left: -31%;
	}
	.Calendar-Item-Popup-IMG {
		display: block;
		width: 100%;
		margin: 0 auto;
	}
	.Calendar-Item-Popup-Title {
		margin: 0;
		text-align: center;
		font-size: 1.5vw;
		line-height: 3vw;
	}
	.Calendar-Item-Popup-Time {
		text-align: center;
		font-size: 2.2vw;
	}
	.Calendar-Item-Popup-Text {
		
	}
	.Calendar-Item-Popup-Button {
		display: block;
		width: 67%;
		margin: 3% auto;
		padding: 1% 0;
		color: #FFF;
		font-size: 1.3vw;
		text-align: center;
		text-decoration: none !important;
		border-radius: 9px;
	}
	.Calendar-Item-Popup-Button:hover {
		color: #A9A9A9 !important;
	}
	.Calendar-Item-Popup-Close {
		position: absolute;
		top: 1%;
		right: 1%;
		color: #A9A9A9;
		font-size: 2vw;
	}
	.Calendar-Item-Popup-Close:hover {
		color: #C00C00 !important;
	}
	
	.Calendar-Switch-Div {
		position: absolute;
		top: 0%;
		right: 0;
	}
	.Calendar-Switch-Title {
		font-size: 1.5vw;
	}
	.Calendar-Switch {
		position: relative;
		background: #A9A9A9;
		width: 3vw;
		height: 1.5vw;
		border-radius: 7px;
	}
	.Calendar-Switch::after {
		content: "";
		position: absolute;
		top: 50%;
		left: 6%;
		width: 1vw;
		height: 1vw;
		background: #FFFFFF;
		border-radius: 100%;
		-webkit-transform: translateY(-50%);
		transform: translateY(-50%);
		-webkit-transition: all 1s;
		transition: all 1s;
		cursor: pointer;
	}
	.Calendar-Switch-Enabled::after {
		left: 56% !important;
	}
	
	.Calendar-Legend {
		position: absolute;
		top: -0.6%;
		left: -4%;
		text-align: left;
	}
	.Calender-Legend-Item {
		margin: 0.5% 0;
		font-size: 1.2vw;
	}
	.Calender-Legend-Box {
		display: inline-block;
		width: 2vw;
		height: 1.5vw;
		vertical-align: middle;
	}
	
	.Green {
		background: #1F8B46;
	}
	.Green:hover {
		background: #15592D;
	}
	.Orange {
		background: #EB652F;
	}
	.Orange:hover {
		background: #D15624;
	}
	.Blue {
		background: #2B47A5;
	}
	.Blue:hover {
		background: #192C6C;
	}
	.Purple {
		background: #6A3597;
	}
	.Purple:hover {
		background: #3C1B59;
	}
	
	.Calendar-Spinner {
		display: block;
		width: 3vw;
		height: 3vw;
		margin: 3% auto 0;
		border: 1vw solid #B9B9B9;
		border-top: 1vw solid #2B47A5;/*Change to theme color idk how yet*/
		border-bottom: 1vw solid #2B47A5;/*Change to theme color idk how yet*/
		border-radius: 50%;
		animation: spin 1s linear infinite;
	}
	
	/*Responsive*/
	@media only screen and (min-width: 768px) and (max-width: 1000px) {
		.Calendar-Month {font-size: 3vw !important;}
		.Calendar-Day-Name {font-size: 2vw;}
		.Calendar-Item-Title {font-size: 1.8vw !important; line-height: 2.5vw !important;}
		.Calendar-Item-Popup-Title {font-size: 2vw !important; line-height: 2.5vw !important;}
		.Calendar-Item-Popup-Button {width: 83% !important; font-size: 1.5vw !important;}
		.Calendar-Switch-Div {top: 0 !important;}
		.Calendar-Legend {top: -41px !important;}
	}
	@media only screen and (max-width: 767px) {
		.Calendar-View {padding: 14% 0 0 !important;}
		.Calendar-Month {font-size: 5vw !important;}
		.Calendar-Row {white-space: normal !important;}
		.Calendar-Days {display: none !important;}
		.Calendar-Day {width: 32.5% !important;}
		.Calendar-Day:nth-child(1), .Calendar-Day:nth-child(2), .Calendar-Day:nth-child(3) {border-top: 1px solid rgba(0, 0, 0, 0.6);}
		.Calendar-Day:nth-child(3n) {border-right-width: 1px;}
		.Calendar-Day-Date {font-size: 3vw !important;}
		.Calendar-Item {width: 82% !important;}
		.Calendar-Item-Title {font-size: 4vw !important; line-height: 5vw !important;}
		.Calendar-Item-Popup-Title {font-size: 4vw !important; line-height: 5vw !important;}
		.Calendar-Item-Popup-Time {font-size: 4vw !important;}
		.Calendar-Item-Popup-Button {width: 79% !important; font-size: 3.6vw !important;}
		.Calendar-Item-Popup-Close {font-size: 5vw !important;}
		.Calendar-Previous::before, .Calendar-Next::after {font-size: 7vw !important;}
		.Calendar-Legend {top: -41px !important;}
		.Calender-Legend-Item {font-size: 4vw !important;}
		.Calender-Legend-Box {display: inline-block; width: 3vw !important; height: 3vw !important; vertical-align: middle !important;}
		.Calendar-Switch-Div {top: -22px !important;}
		.Calendar-Switch-Title {font-size: 4.5vw !important; line-height: 6vw !important;}
		.Calendar-Switch {width: 11vw !important; height: 4.5vw !important;}
		.Calendar-Switch::after {width: 4vw !important; height: 4vw !important;}
	}
</style>

<script>
var weekSwitch = '.$switch.';
var calendarURL = "'.$URL.'";
var calendarData;

function setUp() {
	weekSwitch = (weekSwitch.toString().substr(1, 1) == ".") ? false : weekSwitch;
	calendarURL = (calendarURL.toString().substr(1, 1) == ".") ? "" : calendarURL;
	
	if (weekSwitch) {
		document.getElementById("Calendar-Switch").classList.add("Calendar-Switch-Enabled");
	}
	
	var currentData = '.json_encode($current).';
	calendarData = '.json_encode($data).';
	var calendarTitle = "'.$title.'";
	var currentOffset = "'.$offset.'"
	
	document.getElementsByClassName("Calendar-Month")[0].textContent = calendarTitle;
	
	document.getElementsByClassName("Calendar-Previous")[0].addEventListener("click", function() {offsetChange(-1, currentOffset);}, false);
	document.getElementsByClassName("Calendar-Next")[0].addEventListener("click", function() {offsetChange(1, currentOffset);}, false);
	document.getElementById("Calendar-Switch").addEventListener("click", function() {weekSwitchChange(this);}, false);
	
	setDates(currentData[0], currentData[1]);
	if (calendarData != "") {
		createTickets(calendarData);
	};
	
	var calendarDays = document.getElementsByClassName("Calendar-Day");
	for (var i = 0; i < calendarDays.length; i++) {
		sortTickets(calendarDays[i]);
	}
	
	var callback = function() {
		document.getElementsByClassName("Calendar-Spinner")[0].style.display = "none";
		document.getElementsByClassName("Calendar-Content")[0].style.display = "block";
		fixDayHeight();
	};
	window.setTimeout(callback, 500);
};
function showHideItem(event, target, dir) {
	var popup = target.children[1];
	var popUps = document.getElementsByClassName("Calendar-Item-Popup");
	for (var i = 0; i < popUps.length; i++) {
		popUps[i].style.display = "none";
	};

	if (dir == 0) {
		event.stopPropagation();
	}
	else if (dir == 1) {
		popup.style.display = "block";
	};
};
function fixDayHeight() {
	var days = document.getElementsByClassName("Calendar-Day");
	var maxHeight = 169;
	for (var i = 0; i < days.length; i++) {
		var dayHeightRef = getComputedStyle(days[i]).getPropertyValue("height");
		var dayHeight = parseFloat(dayHeightRef.substr(0, dayHeightRef.length - 2));
		if (dayHeight > maxHeight) {
			maxHeight = dayHeight;
		};
	};
	var finalHeight = maxHeight + 10;
	for (var j = 0; j < days.length; j++) {
		days[j].style.height = finalHeight + "px";
	};
};

function clearDates() {
	var dates = document.getElementsByClassName("Calendar-Day-Date");
	for (var i = 0; i < dates.length; i++) {
		var parent = dates[i].parentElement;
		dates[i].classList.remove("Calendar-Day-Date-Grey");
		dates[i].textContent = "";
		
		if (parent.getAttribute("data-date") != null){
			parent.removeAttribute("data-date");
		}
	};
}
function setDates(startDay, startMonth) {
	clearDates();
	var dates = document.getElementsByClassName("Calendar-Day-Date");
	var monthSwitch, oldMonth = true, newMonth = false;
	var monthValues = [28, 30, 31];
	var current = startDay;
	var monthSelection;
	
	if (!weekSwitch) {
		monthSelection = startMonth - 1;
		monthSwitch = getMonthSwitch(monthSelection);
		
		//Old Month turnover
		if (current < monthValues[monthSwitch] - 6) {
			oldMonth = false;
		}
	
		for (var i = 0; i < dates.length; i++) {
			//Set content and fix leading zer0
			dates[i].textContent = current;
			fixZeros = (current < 10) ? "0" + current : current;
			//Data-ID
			if (oldMonth || newMonth) {
				dates[i].classList.add("Calendar-Day-Date-Grey");
				if (oldMonth) {
					dates[i].parentElement.setAttribute("data-date", "X-" + fixZeros);
				}
				else {
					dates[i].parentElement.setAttribute("data-date", "Y-" + fixZeros);
				}
			}
			else {
				dates[i].parentElement.setAttribute("data-date", fixZeros);
			}
			//Increments
			if (current < monthValues[monthSwitch]) {
				current += 1;
			}
			else {
				current = 1;
				if (!oldMonth) {
					newMonth = true;
				}
				else {
					monthSelection = startMonth;
				}
				monthSwitch = getMonthSwitch(startMonth);
				oldMonth = false;
			};
		};
	}
	else {
		monthSelection = startMonth;
		monthSwitch = getMonthSwitch(monthSelection);
		for (var i = 0; i < dates.length; i++) {
			dates[i].textContent = current;
			fixZeros = (current < 10) ? "0" + current : current;
		
			dates[i].parentElement.setAttribute("data-date", fixZeros);
		
			if (current < monthValues[monthSwitch]) {
				current += 1;
			}
			else {
				current = 1;
				monthSelection = startMonth;
				monthSwitch = getMonthSwitch(startMonth);
			};
		};
	};
};
function getMonthSwitch(switchValue) {
	var index = 0;
	switch (switchValue) {
		case 1:
			index = 2;
			break;
		case 2:
			index = 0;
			break;
		case 3:
			index = 2;
			break;
		case 4:
			index = 1;
			break;
		case 5:
			index = 2;
			break;
		case 6:
			index = 1;
			break;
		case 7:
			index = 2;
			break;
		case 8:
			index = 2;
			break;
		case 9:
			index = 1;
			break;
		case 10:
			index = 2;
			break;
		case 11:
			index = 1;
			break;
		case 12:
			index = 2;
			break;
	};
	return index;
}

function clearTickes() {
	
};
function createTickets(data) {
	var dates = document.getElementsByClassName("Calendar-Day");
	for (var i = 0; i < data.length; i++) {
		var item = document.createElement("div");
		var itemTitle = document.createElement("h2");
		var itemPopup = document.createElement("div");
		var popupIMG = document.createElement("IMG");
		var popupTitle = document.createElement("h1");
		var popupTime = document.createElement("div");
		var popupButton = document.createElement("a");
		var popupClose = document.createElement("div");
		
		item.classList.add("Calendar-Item");
		item.classList.add(data[i].ticket_color);
		itemTitle.classList.add("Calendar-Item-Title");
		itemPopup.classList.add("Calendar-Item-Popup");
		popupIMG.classList.add("Calendar-Item-Popup-IMG");
		popupTitle.classList.add("Calendar-Item-Popup-Title");
		popupTime.classList.add("Calendar-Item-Popup-Time");
		popupButton.classList.add("Calendar-Item-Popup-Button");
		popupButton.classList.add(data[i].ticket_color);
		popupClose.classList.add("Calendar-Item-Popup-Close");
		
		item.setAttribute("data-id", data[i].ticket_id);
		item.setAttribute("data-time", data[i].ticket_military);
		popupButton.setAttribute("target", "_blank");
		
		itemTitle.textContent = data[i].ticket_name + " - " + data[i].ticket_time;
		popupIMG.src = data[i].ticket_image;
		popupTitle.textContent = data[i].ticket_name;
		popupTime.textContent = data[i].ticket_time;
		popupButton.href = data[i].ticket_link;
		popupButton.textContent =  "Buy Ticket(s)";
		popupClose.textContent = "X";
		
		item.addEventListener("click", function(e) {showHideItem(e, this, 1);}, false)
		popupClose.addEventListener("click", function(e) {showHideItem(e, this, 0);}, false)
		
		for (var j = 0; j < dates.length; j++) {
			var date = dates[j]
			var dateID = date.getAttribute("data-date");
			var ticketDate = data[i].ticket_date.substr(-2, 2);
			
			if (dateID == ticketDate && dateID.substr(1, 1) != "-") {
				dates[j].appendChild(item);
			};
		};
		
		item.appendChild(itemTitle);
		item.appendChild(itemPopup);
		itemPopup.appendChild(popupIMG);
		itemPopup.appendChild(popupTitle);
		itemPopup.appendChild(popupTime);
		itemPopup.appendChild(popupButton);
		itemPopup.appendChild(popupClose);
	};
};
function sortTickets(parent) {
	var array = [].slice.call(parent.children);
	array.shift();
	array.sort(function(a, b) {
		return Number(a.getAttribute("data-time").replace(":","")) - Number(b.getAttribute("data-time").replace(":",""));
	});
	array.forEach(function(ele) {
		parent.appendChild(ele);
	});
}

function weekSwitchChange(target) {
	if (target.classList.contains("Calendar-Switch-Enabled")) {
		target.classList.remove("Calendar-Switch-Enabled");
	}
	else {
		target.classList.add("Calendar-Switch-Enabled");
	};
	
	var newSwitch = (weekSwitch) ? "false" : "true";
	var newOffset = -1;
	
	setNewUrl(newOffset, newSwitch);
};
function offsetChange(dir, offset) {
	var newOffset = parseInt(offset);
	
	if (dir > 0) {
		newOffset += 1;
	}
	else {
		newOffset -= 1;
	}
	
	var newSwitch = (weekSwitch == 1) ? "true" : "false";
	
	setNewUrl(newOffset, newSwitch);
};
function setNewUrl(offset, swtch) {
	var callback = function() {window.location.href = calendarURL + "?" + "Offset=" + offset + "&Switch=" + swtch};
	window.setTimeout(callback, 750);
}

window.addEventListener("load", setUp, false);
</script>
	';
	
	return $html;
}

if (isset($_GET['Offset'])) {
	createData((int)$_GET['Offset'] + 1);
}
else {
	createData(0);
}

?>