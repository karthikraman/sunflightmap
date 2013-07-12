<?php
include("lib/global.php");

// allow input from URL
$autoload = false;
$flightcode = $cfg["EXAMPLE_FLIGHT_ROUTES"][array_rand($cfg["EXAMPLE_FLIGHT_ROUTES"])]; //'JQ7';
if (array_key_exists("flightcode", $_GET)) {
	$flightcode = $_GET['flightcode'];
	$autoload = true;
}
$date_depart=date("Y-m-d");
if (array_key_exists("date", $_GET)) {
	$date_depart=$_GET['date'];
	$autoload = true;
}

if(array_key_exists("autoload", $_GET)) {
	$autoload = true;
}

?>
<!DOCTYPE html> 
<html> 

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<title>SunFlight.net - Day and Night Flight Map</title>

	<meta name="description" content="Map the path of your flight and the sun. Built at tnooz tHack Singapore!">
	<!--<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">-->
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	
	<meta property="og:title" content="SunFlight.net" /> 
	<meta property="og:description" content="Chase the sun and map the path of your flight with it." /> 
	
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/excite-bike/jquery-ui.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" type="text/css"/>
	<link rel="stylesheet" href="css/stylesheet.css" type="text/css" media="screen, projection" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />

	<!-- libraries -->
	<!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>-->
	<!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>-->
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?libraries=geometry&sensor=false"></script>
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.css" />
	<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
	<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
	<script src="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.js"></script>

	<!-- custom code -->
	<script type="text/javascript" src="/js/daynightmaptype.js"></script>
	<script type="text/javascript" src="/js/jQueryRotate.2.2.js"></script>
	<script type="text/javascript" src="/js/richmarker-compiled.js"></script>

	<script type="text/javascript">
	
	var map;
	var flightPaths = Array();
	var markers = Array();
	var flightMarker = null;
	var sunMarker = null;
	var dn = null;
	// day night shadow
	// track if we have initialised the slider yet
	var timeslider = null;
	var firstLoad = true;

	$(document).ready(function() {

		
	    function initializeMap() {
	        var myOptions = {
	            zoom: 1,
	            maxZoom: 3,
	            minZoom: 1,
	            center: new google.maps.LatLng( 10, 150.644),
	            mapTypeId: google.maps.MapTypeId.ROADMAP,
	            streetViewControl: false,
	            mapTypeControl: false,
	            panControl: false,
    			draggable: false,
    			scrollwheel: false
	        };
	        map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
	        <?php
	        if ($autoload) { ?>
	        	mapFlight(); 
	        <?
	        } ?>
	    }

	    expandFlightCodeContainer = function() {
	    	$('#enter-flight-code-title').text("Enter your flight code (example: QF1)");
	    	$('#map_container').hide();
	    	$('#results-panel').hide();
	    	$('#show-developer-info').hide();
	    }

	    collapseFlightCodeContainer = function() {

	    	// validate input
	        if (!validateInput()) {
	            return;
	        }

	        // update enter flights header
	       	$('#enter-flight-code-title').text("Flight " + getInputCarrierCode().toUpperCase() + getInputServiceNumber() + " departing " + getInputRequestDate() + " (Edit)");
	    }

	    main = function() {
	        //google.maps.event.addDomListener(window, 'load', initializeMap);
	        $("#requestDate").datepicker({
	            dateFormat: 'yy-mm-dd',
	            defaultDate: +0
	        });
	        <?php
	        if (array_key_exists("debug", $_GET)) { ?>$('#debug').show(); <?
	        } ?>
	        updatePermalink();
	        
	        $('#enter-flight-code').bind('expand', expandFlightCodeContainer);
	        $('#enter-flight-code').bind('collapse', collapseFlightCodeContainer);


	        <?php
	        if (!isChrome()) { ?>$("#chrome_note").show(); <?
	        } ?>
	        //init();
	    }

	    clearMapRoutes = function() {
	        //alert(flightPaths.length);
	        // remove existing polys
	        for (i = 0; i < flightPaths.length; i++) {
	            flightPaths[i].setMap(null);
	        }
	        for (i = 0; i < markers.length; i++) {
	            markers[i].setMap(null);
	        }
	        // reset array
	        flightPaths = Array();
	        markers = Array();
	    }

	    trim = function(str) {
	        return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
	    }

	    getInputCarrierCode = function() {
	        return trim($('#carrierCodeAndServiceNumber').val().replace(/[\d.]/g, ''));
	        // "JQ"
	    }

	    getInputServiceNumber = function() {
	        return trim($('#carrierCodeAndServiceNumber').val().replace(/[A-Za-z$-]/g, ''));
	        // 7
	    }

	    getInputRequestDate = function() {
	        return trim($('#requestDate').val());
	    }

	    validateInput = function() {
	        if (getInputCarrierCode() == "") {
	            alert("Please enter a carrier code (ie: JQ)");
	            return false;
	        }
	        if (getInputServiceNumber() == "") {
	            alert("Please enter a service number (ie: JQ7)");
	            return false;
	        }
	        if (getInputRequestDate() == "") {
	            alert("Please enter a date of travel (ie: 2011-10-14)");
	            return false;
	        }
	        return true;
	        // valid!
	    }

	    mapFlight = function() {

	        // validate input
	        if (!validateInput()) {
	            return;
	        }

	        // make permalink
	        updatePermalink();

	        // clear previous map routes
	        clearMapRoutes();

	        // show loading page
	        $.mobile.showPageLoadingMsg(); //$('#loading-page').show();
	        $('#results-panel').hide();

	        // lookup flight data from OAG wrapper
	        $.getJSON("/ajax/ajax-flight-route.php?callback=?",
	        {
	            carrier_code: getInputCarrierCode(),
	            // JQ
	            service_number: getInputServiceNumber(),
	            // "7",
	            request_date: getInputRequestDate()
	            //"2011-10-14"
	        },
	        function(data) {

	        	if (data.error != null) {
	        		$.mobile.hidePageLoadingMsg();
	        		//$('#loading-page').hide();
	        		alert(data.error);
	        	} else {
	        		$("#cached_result").val(data.cached);
	        		initFlightRoutes(data.flight_segments);
	        	}
	        });
	    }

	    drawFlightRoute = function(data) {

            // get lat lon
            var fromLatLng = new google.maps.LatLng(data.from_lat, data.from_lon);
            var toLatLng = new google.maps.LatLng(data.to_lat, data.to_lon);

            // get date
            var depart_date = Date.parse(data.depart_time);
            //alert(depart_date.format("UTC:h:MM:ss TT Z"));

            // draw path of flight
            var flightPath = new google.maps.Polyline({
                path: [fromLatLng, toLatLng],
                strokeColor: "#FF0080",
                strokeOpacity: 1.0,
                strokeWeight: 2,
                geodesic: true,
                clickable: false
            });
            flightPaths.push(flightPath);
            flightPath.setMap(map);

	    }


	    drawFlightData = function(data) {

	    	return; 

       		var content_html = "<div>";

            content_html += "<table class='flightdata' width='240'>";

            content_html += "<tr><td colspan='2'>";
            content_html += "<table width='240'><tr>";
            content_html += "<td><span class='flightdata from_airport'>" + data.from_airport + "</span></td>";
            content_html += "<td><span class='flightdata plane_icon'>&#9992;</span></td>";
            content_html += "<td><span class='flightdata to_airport'>" + data.to_airport + "</span></td>";
            content_html += "</tr></table>";
            content_html += "</td></tr>";

            content_html += "<tr>";
            content_html += "<td width='50%'><span class='flightdata depart_city'>Depart " + data.from_city + "</span></td>";
       		content_html += "<td width='50%'><span class='flightdata arrive_city'>Arrive " + data.to_city + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td width='50%'><span class='flightdata scheduled_time'>Scheduled<br>" + data.depart_time.replace("T", " ") + "</span></td>";
       		content_html += "<td width='50%'><span class='flightdata scheduled_time'>Scheduled<br>" + data.arrival_time.replace("T", " ") + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata duration'>Duration: " + formatMinutes(data.elapsed_time) + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
       		var miles_to_km = 0.621371192;
            content_html += "<td colspan='2'><span class='flightdata distance'>Distance: " + addCommas(Math.round(data.distance_km * miles_to_km)) + " miles";
            content_html += " (" + addCommas(data.distance_km ) + " km)</span></td>";
       		content_html += "</tr>";

       		//content_html += "<tr>";
            //content_html += "<td colspan='2'><span class='flightdata operation'>Days of Operation: " + data.days_of_op + "</span></td>";
       		//content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata left'>Sun Left Hand Side: " + data.flight_stats.percent_left + "% (" + data.flight_stats.total_minutes_left + " mins)</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata right'>Sun Right Hand Side: " + data.flight_stats.percent_right + "% (" + data.flight_stats.total_minutes_right + " mins)</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata night'>Night time: " + data.flight_stats.percent_night + "% (" + data.flight_stats.total_minutes_night + " mins)</span></td>";
       		content_html += "</tr>";

            content_html += "</table>";

            content_html += "<hr></div>";

           	$('#results-panel').append(content_html);
	    }


        function addCommas(nStr)
		{
		  nStr += '';
		  x = nStr.split('.');
		  x1 = x[0];
		  x2 = x.length > 1 ? '.' + x[1] : '';
		  var rgx = /(\d+)(\d{3})/;
		  while (rgx.test(x1)) {
		    x1 = x1.replace(rgx, '$1' + ',' + '$2');
		  }
		  return x1 + x2;
		}


	    formatMinutes = function(minutes) {
	    	var hours = Math.floor(minutes / 60);
	    	if (hours > 0) {
	    		var text = hours + "hrs " + (minutes % 60) + " mins";
	    	} else {
	    		var text = (minutes % 60) + " mins";
	    	}
	    	return text;
	    }

	    resetResults = function() {

	    	// show map
	    	$('#map_container').show();
	    	
	    	// initialise google map if first time
	    	if (firstLoad) {
				initializeMap();
				firstLoad = false;
			} else {
				// need to set resize the map otherwise we get missing tiles
				// http://stackoverflow.com/questions/10489264/jquery-mobile-and-google-maps-not-rendering-correctly
				setTimeout(function() {
            		google.maps.event.trigger(map,'resize');
        		}, 500);
			}

	    	$.mobile.hidePageLoadingMsg(); // $('#loading-page').hide();
	    	$('#enter-flight-code').trigger('collapse');
	    	$('#results-panel').html("");
	    }

	    drawFlightEndPoints = function(data) {

	    	var circle = {
    			path: google.maps.SymbolPath.CIRCLE,
    			scale: 3.0,
    			fillColor: "#F00",
    			strokeColor: "#eee",
    			stokeWeight: 0.1
  			};

            var flagimage = new google.maps.MarkerImage('images/flag.png',
	            new google.maps.Size(30, 36),
	            // marker dimensions
	            new google.maps.Point(0, 0),
	            // origin of image
	            new google.maps.Point(2, 36));

	            // anchor of image
	            var toLatLngFlag = new google.maps.LatLng(data.to_lat, data.to_lon);
	            var toMarker = new google.maps.Marker({
	                position: toLatLngFlag,
	                map: map,
	                title: 'Destination',
	                icon: circle
	                //'/images/flag.png'
            });
            markers.push(toMarker);
	    }

	    initTimeSlider = function(flightdata) {

	    	var first_flight = flightdata[0];
	    	var last_flight = flightdata[flightdata.length - 1];

	    	// calculate total flight time
	    	// use elapsed_time for each flight plus the time until the next departure
	    	var total_minutes = 0;
	    	for (var i = 0; i < flightdata.length; i++) {
				// calculate flight duration including layover time for next segment
	    		if (i < flightdata.length - 1) {
	    			var this_flight_arrival_time = new Date(Date.parse(flightdata[i].arrival_time));
	    			var next_flight_start_time = new Date(Date.parse(flightdata[i+1].depart_time));
	    			var flight_time_diff = Math.abs(next_flight_start_time.getTime() - this_flight_arrival_time.getTime());
	    			var flight_time_including_stopover = flightdata[i].elapsed_time + Math.ceil(flight_time_diff / 1000 / 60);
	    		} else {
	    			var flight_time_including_stopover = flightdata[i].elapsed_time;
	    		}

	    		total_minutes += flight_time_including_stopover; // add flight time included stop over
	    	}


			// record flight segment index
			var flight_segment_by_minute = []; // track which flight segment a given minute is in
			var flight_segment_start_time = []; // track the start time (of the total journey) of a flight

	    	for (var i = 0; i < flightdata.length; i++) {

	    		// calculate flight duration including layover time for next segment
	    		if (i < flightdata.length - 1) {
	    			var this_flight_arrival_time = new Date(Date.parse(flightdata[i].arrival_time));
	    			var next_flight_start_time = new Date(Date.parse(flightdata[i+1].depart_time));
	    			var flight_time_diff = Math.abs(next_flight_start_time.getTime() - this_flight_arrival_time.getTime());
	    			var flight_time_including_stopover = flightdata[i].elapsed_time + Math.ceil(flight_time_diff / 1000 / 60);
	    			//alert(flight_time_including_stopover);
	    		} else {
	    			var flight_time_including_stopover = flightdata[i].elapsed_time;
	    		}

	    		// for each minute of flight time, record the segment index (i)
	    		var flight_segment_by_minute_length = flight_segment_by_minute.length;
	    		//alert(flight_segment_by_minute_length + flight_time_including_stopover);
	    		for (var j = flight_segment_by_minute_length; j <= flight_segment_by_minute_length + flight_time_including_stopover; j++) {
	    			flight_segment_by_minute[j] = i;
	    			flight_segment_start_time[i] = j;
	    		}
	    	}

			if (timeslider != null) {
                timeslider = $("#slider").slider("destroy");
            }

	    	$("#slider_holder").empty();
            $("#slider_holder").append('<input type="range" name="slider-time" id="slider" value="0" min="0" max="100"/>');

            // init jquery mobile slider
            $("#slider_holder").show();
            $("#slider_holder input").attr('value', 0);
            $("#slider_holder input").attr('max', total_minutes);
			$("#slider_holder").trigger("create");


			$("#slider").bind("change", function(event, ui) {

  				clearTimeout(this.id);

                this.id = setTimeout(function() {

                	var slider_value = parseInt($("#slider_holder input").val());

	                //console.log(first_flight.depart_time_utc);
	                mapSunPosition(flightPaths, map, new Date(Date.parse(first_flight.depart_time_utc)), total_minutes, slider_value);
	                
	                // map path of the sun
	                // work out which flight segment we are in using flight_segment_by_minute index
	                var flight_segment = flight_segment_by_minute[slider_value];
	                var current_flight = flightdata[flight_segment];
	                $("#flight_segment").val(flight_segment + 1);
	                var relative_ui_value = slider_value;
	                if (flight_segment > 0) {
	                	relative_ui_value -= flight_segment_start_time[flight_segment-1]; // offset with previous flight
	                }
	                var minute_of_segment = relative_ui_value;


	                var flight_points = current_flight["flight_points"];
	                $("#minute_of_segment").val(relative_ui_value);

	                if (minute_of_segment < current_flight.elapsed_time) {
						var flight_point = flight_points[minute_of_segment];
	                	$("#sfcalc_sun_side").val(flight_point["sun_side"]);
	                	$("#sfcalc_tod").val(flight_point["tod"]);
	                	$("#sfcalc_sun_east_west").val(flight_point["sun_east_west"]);
	                	$("#sfcalc_azimuth_from_north").val(flight_point["azimuth_from_north"]);
	                	$("#sfcalc_bearing_from_north").val(flight_point["bearing_from_north"]);
	                } else {
	                	$("#sfcalc_sun_side").val("stopover");
	                	$("#sfcalc_tod").val("stopover");
	                	$("#sfcalc_sun_east_west").val("stopover");
	                	$("#sfcalc_azimuth_from_north").val("stopover");
	                	$("#sfcalc_bearing_from_north").val("stopover");
	                }

			        if (minute_of_segment < current_flight.elapsed_time) {
			        	current_bearing = flight_point["bearing_from_north"];
			        }
	                mapFlightPosition(flightPaths, map, current_flight.from_lat, current_flight.from_lon, current_flight.to_lat, current_flight.to_lon, current_flight.elapsed_time, relative_ui_value, current_bearing);
	                
	                // map path of the sun
	                mapDayNightShadow(map, new Date(Date.parse(first_flight.depart_time_utc)), slider_value);
	                $("#minutes_travelled").val(slider_value);
	                updateSliderTime(slider_value, total_minutes);  

				}, 10); // end set timeout
            }); // end change event

			// update slider to begin with
            mapSunPosition(flightPaths, map, new Date(Date.parse(first_flight.depart_time_utc)), total_minutes, 0);
            // map path of the sun
            mapFlightPosition(flightPaths, map, first_flight.from_lat, first_flight.from_lon, first_flight.to_lat, first_flight.to_lon, first_flight.elapsed_time, 0, first_flight.flight_points[0]["bearing_from_north"]);
            // map path of the sun
            mapDayNightShadow(map, new Date(Date.parse(flightdata[0].depart_time_utc)), 0);
            $("#minutes_travelled").val(0);
            updateSliderTime(0, total_minutes);

	    }

	    initFlightRoutes = function(flightdata) {

            // get back jsonp
            // flightmap({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-16T12:00:00","elapsed_time": 470})
			resetResults();

	        // draw flight routes
	        for(var i = 0; i < flightdata.length; i++) {

	        	// check for errors
	        	if (flightdata[i].error != "") {
	        		alert("Error processing flight route data: " + data.error);
	        		return;
	        	}

	        	drawFlightRoute(flightdata[i]);
	        	drawFlightData(flightdata[i]);
	        	drawFlightEndPoints(flightdata[i]);
	        }	      

	        // show slider
	        $('#slider-container').show();
	       	$('#results-panel').fadeIn();
	       	$('#show-developer-info').show();

	    	initTimeSlider(flightdata);

	    }

	    mapDayNightShadow = function(map, UTCTime, minutesOffset) {
	        //alert(maptime);
	        if (dn == null) {

	            dn = new DayNightMapType(UTCTime, minutesOffset);
	            map.overlayMapTypes.insertAt(0, dn);
	            dn.setMap(map);
	            //dn.setAutoRefresh(10);
	            dn.setShowLights(1);
	        }
	        else {
	            dn.calcCurrentTime(UTCTime, minutesOffset);
	            dn.redoTiles();
	        }
	    }

	    mapSunPosition = function(flightPaths, map, start_time_at_gmt, duration_minutes, minutes_travelled) {

	        // Sun is directly overhead LatLng(0, 0) at 12:00:00 midday
	        // 1440 minutes / 1 minute = 0.25 degrees
	        // Assuming maximum trip duration of 24 hours / single leg
	        // Calculate sun's starting longitude from the start time at gmt
	        //console.log(start_time_at_gmt);
	        //console.log(new Date(start_time_at_gmt).getTimezoneOffset());
	        local_offset = new Date(start_time_at_gmt).getTimezoneOffset();
	        minutes_gmt = local_offset + (start_time_at_gmt.getHours() * 60) + start_time_at_gmt.getMinutes();
	        //console.log(minutes_gmt);
	        from_deg = 180 - minutes_gmt * 0.25;

	        duration_deg = duration_minutes * 0.25 * (minutes_travelled / duration_minutes);
	        to_deg = from_deg - duration_deg;

			var dayofyear= (start_time_at_gmt - new Date(start_time_at_gmt.getFullYear(),0,1)) / 86400000;
			var sunlat = -23.44*Math.sin(((dayofyear + 10 + 91.25)*Math.PI)/(365/2));

			// Starting longitude is positive
			var toLatLng = new google.maps.LatLng(sunlat, to_deg);

	        // draw sun marker
	        if (sunMarker != null) {
	            sunMarker.setMap(null);
	        }

	        var sunimage = new google.maps.MarkerImage('images/sun.png',
	        new google.maps.Size(32, 32),
	        // marker dimensions
	        new google.maps.Point(0, 0),
	        // origin of image
	        new google.maps.Point(16, 16));
	        // anchor of image
	        sunMarker = new google.maps.Marker({
	            position: toLatLng,
	            map: map,
	            title: 'Sun Position: ' + to_deg,
	            icon: sunimage
	        });
	        markers.push(sunMarker);

	    }

	    mapFlightPosition = function(flightPaths, map, startLat, startLon, endLat, endLon, duration_minutes, minutes_travelled, bearing) {

	        // draw flight marker
	        if (flightMarker != null) {
	            flightMarker.setMap(null);
	        }

	        if (minutes_travelled > duration_minutes) {
	        	minutes_travelled = duration_minutes;
	        }

	        percentage_travelled = minutes_travelled / duration_minutes;

	        var fromLatLng = new google.maps.LatLng(startLat, startLon);
	        var toLatLng = new google.maps.LatLng(endLat, endLon);

	        try {
	            var flightpos = google.maps.geometry.spherical.interpolate(fromLatLng, toLatLng, percentage_travelled);
	        }
	        catch(error) {
	            // ignore it
	        }


	        var planeimage = new google.maps.MarkerImage('images/airplane.svg', null, null, null, new google.maps.Size(32, 32));

	        flightMarker = new google.maps.Marker({
	            position: flightpos,
	            map: map,
	            title: 'Flight position: ' + to_deg,
	            icon: {
			        scale: 1.2,
			        path: "m16.194347,3.509549c0.7269,0 1.333155,0.605579 1.333155,1.372868l0,8.077136l11.306938,6.34025l0,2.826685l-11.306938,-3.270845l0,5.895784l2.665304,1.978716l0,2.342138l-3.99892,-1.333424l-3.997725,1.3325l0,-2.342411l2.665575,-1.978714l0,-5.895763l-11.308268,3.271421l0,-2.826664l11.308268,-6.340271l0,-8.077136c0,-0.767288 0.604597,-1.372868 1.332093,-1.372868l0.000519,0.000597z",
			        origin: new google.maps.Point(0, 0),
			        anchor: new google.maps.Point(16, 16),
			        strokeWeight: 0.5,
			        fillOpacity: 1,
			        fillColor: "#FF0",
			        //strokeColor: "#303030",
			        rotation: bearing
			    }
	        	});
	        markers.push(flightMarker);
	    }

	    function updateSliderTime(t, max)
	    {
	        //slider_text = t + " mins";
	        slider_text = formatMinutes(t);
	        if (t == 0) {
	            slider_text = "Take off...";
	        }
	        else if (t == max) {
	            slider_text = "Landed!";
	        }

	        $("#map_container label").text(slider_text);
	        $('#slider_time').val(slider_text);
	    }

	    function updatePermalink()
	    {
	        $('#permalink').attr("href", "http://" + window.location.hostname + "/?flightcode=" + getInputCarrierCode() + getInputServiceNumber() + "&date=" + getInputRequestDate());
	    }

	    hideWelcomeWindow = function()
	    {
	        aboutClicked = false;
	        $('#welcome').fadeOut();
	    }

	    showWelcomeWindow = function()
	    {
	        $('#welcome').show();
	    }

	    <?php if ($autoload) { ?>
	        	resetResults();
	    <? } ?>

	    // let's do it!
	    main();

	});
	</script>
		
	
</head>
<body>

	<!-- Start of first page: #one -->
<div data-role="page" id="sunflight">

	<div data-role="header">
		<h1>Day and Night Map</h1>
		<div data-role="navbar">
			<ul>
				<li><a href="#home" class="ui-btn-active">Flight Map</a></li>
				<li><a href="#faq">FAQ</a></li>
			</ul>
		</div><!-- /navbar -->

	</div><!-- /header -->

	<div data-role="content" id="home">	
		
			<div id="chrome_note" style="display: none;"><p style="font-size: smaller;">(Please note: This app works best in Google Chrome)</p></div>

			<div data-role="collapsible" data-collapsed="false" id="enter-flight-code">
	   			<h3><span id="enter-flight-code-title">Enter Flight Code</span></h3>

				<input id="carrierCodeAndServiceNumber" value="<?php print($flightcode);?>" size="5">
				<input id="requestDate" value="<?php print($date_depart); ?>" size="12">
				<button onClick="mapFlight();" data-theme="e">Show Flight Map</button>
				<div id="random_flight">
					<p>Or, show me a <a rel=external href="/?autoload">random flight</a></p>
				</div>
			</div>


			<div id="map_container" style="display: none;">
				<label for="slider-0">(Mins)</label>
				<div id="slider_holder" style="width: 100%; display: none;">
   					<input type="range" name="slider-time" id="slider" value="0" min="0" max="100"/>
   				</div>

				<div id="map_canvas" style="width: 100%;">Loading map...</div>

				<p><a id="permalink" style="color: blue;" rel=external href="#">Link to this map</a></p>
    		</div>

    		<div id="results-panel"></div>

			<div data-role="collapsible" id="show-developer-info" style="display: none;">
	   			<h3>Advanced</h3>

	   			<div data-role="fieldcontain">
					<label for="minutes_travelled">Minutes Traveled:</label>
					<input type="text" name="minutes_travelled" id="minutes_travelled" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="slider_time">Hours Traveled:</label>
					<input type="text" name="slider_time" id="slider_time" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="flight_segment">Flight Segment:</label>
					<input type="text" name="flight_segment" id="flight_segment" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="minute_of_segment">Minute of Segment:</label>
					<input type="text" name="minute_of_segment" id="minute_of_segment" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="sfcalc_sun_side">Sun position:</label>
					<input type="text" name="sfcalc_sun_side" id="sfcalc_sun_side" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="sfcalc_tod">Time of day:</label>
					<input type="text" name="sfcalc_tod" id="sfcalc_tod" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="sfcalc_sun_east_west">Sun East/West:</label>
					<input type="text" name="sfcalc_sun_east_west" id="sfcalc_sun_east_west" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="sfcalc_azimuth_from_north">Solar Azimuth (from North):</label>
					<input type="text" name="sfcalc_azimuth_from_north" id="sfcalc_azimuth_from_north" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="sfcalc_azimuth_from_north">Flight bearing (from North):</label>
					<input type="text" name="sfcalc_bearing_from_north" id="sfcalc_bearing_from_north" value="" />
				</div>

				<div data-role="fieldcontain">
					<label for="cached_result">OAG Cached result:</label>
					<input type="text" name="cached_result" id="cached_result" value="" />
				</div>

			</div>

	</div><!-- /content -->
	
	<div data-role="footer" data-theme="d">
		<h4>Like this app? <a href="#donate">Donate $5</a></h4>
	</div><!-- /footer -->
</div><!-- /home page -->



<!-- Start of second page: #faq -->
<div data-role="page" id="faq" data-theme="a">

	<div data-role="header">
		<h1>Frequently Asked Questions</h1>
	</div><!-- /header -->

	<div data-role="content" data-theme="a">	
		<h2>FAQ</h2>
		<p><strong>Q: How can I contact the creator of this site?</strong>
		<br>A: Contact me at <a href="mailto:ian@travelmassive.com">ian@travelmassive.com</a>
		</p>
		<p><a href="#one" data-rel="back" data-role="button" data-inline="true" data-icon="back">Back to home page</a></p>	
		
	</div><!-- /content -->
	
	<div data-role="footer">
		<h4>Like this app? <a href="#donate">Donate $5</a></h4>
	</div><!-- /footer -->
</div><!-- /page faq -->



<!-- Start of third page: #donate -->
<div data-role="page" id="donate">

	<div data-role="header">
		<h1>Please Donate</h1>
	</div><!-- /header -->

	<div data-role="content" data-theme="e">	
		<h2>Why should I donate?</h2>
		<p>To keep this site advertisement free for the flyer community I need donations. This helps me pay for commercial data feeds ($200 a month) which provides you with reliable and accurate flight information.</p>
		<p>Please consider helping out by clicking the PayPal button below. Every $5 counts. Thanks!</p>		
		<p>
<!-- start paypal donation button -->
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<input type="image" src="https://www.paypalobjects.com/en_AU/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal — The safer, easier way to pay online.">
<img alt="" border="0" src="https://www.paypalobjects.com/en_AU/i/scr/pixel.gif" width="1" height="1">
</form>
</p>
<!-- end paypal donation button -->

		<p><a href="#one" data-rel="back" data-role="button" data-inline="true" data-icon="back">Back to home page</a></p>	
	</div><!-- /content -->
	
	<div data-role="footer">
		<h4>SunFlight.net</h4>
	</div><!-- /footer -->
</div><!-- /page donate -->

	<!-- google anlaytics -->
	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-26345499-1']);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>
	<!-- analytics -->
	
</body>
</html>
