<!--
 Copyright 2008 Google Inc.
 Licensed under the Apache License, Version 2.0:
 http://www.apache.org/licenses/LICENSE-2.0
 -->
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Google AJAX Local Search API + Maps API v3 demo</title>
    <link href="http://www.google.com/uds/css/gsearch.css" rel="stylesheet" type="text/css"/>
    <link  rel="stylesheet" type="text/css"/>
	<style type="text/css">
	#searchwell {
  width : 330px;
}

#searchwell .unselected {
  padding-left: 18px;
  padding-top: 1px;
  margin-top: 5px;
  background-image: url("http://labs.google.com/ridefinder/images/mm_20_yellow.png");
  background-repeat: no-repeat;
  background-position: top left;
}

.unselected .gs-watermark {
  display: none;
}

.unselected .gs-directions {
  display: none;
}

.unselected .gs-directions-to-from {
  display: none;
}

.unselected .select {
  cursor: pointer;
  text-decoration: underline;
  color: #7777cc;
}

#searchwell .red {
  background-image: url("http://labs.google.com/ridefinder/images/mm_20_red.png");
}
	
	</style>
    <script src="http://maps.google.com/maps/api/js?sensor=false"></script>

    <script src="http://www.google.com/uds/api?file=uds.js&amp;v=1.0&amp;key=ABQIAAAAjU0EJWnWPMv7oQ-jjS7dYxQ82LsCgTSsdpNEnBsExtoeJv4cdBSUkiLH6ntmAr_5O4EfjDwOa0oZBQ" type="text/javascript"></script>
    <script type="text/javascript">
    //<![CDATA[

    // Our global state
    var gLocalSearch;
    var gMap;
    var gInfoWindow;
    var gSelectedResults = [];
    var gCurrentResults = [];
    var gSearchForm;
    var boxpolys = null;
    var sw = new google.maps.LatLng(10.85849,106.788801);
    var ne = new google.maps.LatLng(10.850487,106.753286);
    var bounds = new google.maps.LatLngBounds(ne,sw);
    // Create our "tiny" marker icon
    var gYellowIcon = new google.maps.MarkerImage(
      "http://labs.google.com/ridefinder/images/mm_20_yellow.png",
      new google.maps.Size(12, 20),
      new google.maps.Point(0, 0),
      new google.maps.Point(6, 20));
    var gRedIcon = new google.maps.MarkerImage(
      "http://labs.google.com/ridefinder/images/mm_20_red.png",
      new google.maps.Size(12, 20),
      new google.maps.Point(0, 0),
      new google.maps.Point(6, 20));
    var gSmallShadow = new google.maps.MarkerImage(
      "http://labs.google.com/ridefinder/images/mm_20_shadow.png",
      new google.maps.Size(22, 20),
      new google.maps.Point(0, 0),
      new google.maps.Point(6, 20));
var center = new google.maps.LatLng(10.849765,106.77223);
     // Set up the map and the local searcher.
    function OnLoad() {

      // Initialize the map with default UI.
      gMap = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(10.849765,106.77223),
        zoom: 13,
        mapTypeId: 'roadmap'
      });
      // Create one InfoWindow to open when a marker is clicked.
      gInfoWindow = new google.maps.InfoWindow;
      google.maps.event.addListener(gInfoWindow, 'closeclick', function() {
        unselectMarkers();
      });
      
     var boxpolys = new google.maps.Rectangle({
          bounds: bounds,
          fillOpacity: 0,
          strokeOpacity: 1.0,
          strokeColor: '#000000',
          strokeWeight: 1,
          map: gMap
     });
      // Initialize the local searcher
     gLocalSearch = new GlocalSearch();
     gLocalSearch.setSearchCompleteCallback(null, OnLocalSearch);

         		  
    		}
    

    function unselectMarkers() {
      for (var i = 0; i < gCurrentResults.length; i++) {
        gCurrentResults[i].unselect();
      }
    }

    function doSearch() {
      //var query = document.getElementById("queryInput").value;
       var query = {
    location: center,
    radius: '500' ,
    name: ['kfc']
  };
      
      gLocalSearch.setCenterPoint(gMap.getCenter());
      gLocalSearch.execute(query);
    }

    // Called when Local Search results are returned, we clear the old
    // results and load the new ones.
    function OnLocalSearch() {
      if (!gLocalSearch.results) return;
      var searchWell = document.getElementById("searchwell");

      // Clear the map and the old search well
      searchWell.innerHTML = "";
      for (var i = 0; i < gCurrentResults.length; i++) {
        gCurrentResults[i].marker().setMap(null);
      }
      // Close the infowindow
      gInfoWindow.close();

      gCurrentResults = [];
      for (var i = 0; i < gLocalSearch.results.length; i++) {
        gCurrentResults.push(new LocalResult(gLocalSearch.results[i]));
      }

      var attribution = gLocalSearch.getAttribution();
      if (attribution) {
        document.getElementById("searchwell").appendChild(attribution);
      }

      // Move the map to the first result
      var first = gLocalSearch.results[0];
      gMap.setCenter(new google.maps.LatLng(parseFloat(first.lat),
                                            parseFloat(first.lng)));

    }

    // Cancel the form submission, executing an AJAX Search API search.
    function CaptureForm(searchForm) {
      gLocalSearch.execute(searchForm.input.value);
      return false;
    }



    // A class representing a single Local Search result returned by the
    // Google AJAX Search API.
    function LocalResult(result) {
      var me = this;
      me.result_ = result;
      me.resultNode_ = me.node();
      me.marker_ = me.marker();
      google.maps.event.addDomListener(me.resultNode_, 'mouseover', function() {
        // Highlight the marker and result icon when the result is
        // mouseovered.  Do not remove any other highlighting at this time.
        me.highlight(true);
      });
      google.maps.event.addDomListener(me.resultNode_, 'mouseout', function() {
        // Remove highlighting unless this marker is selected (the info
        // window is open).
        if (!me.selected_) me.highlight(false);
      });
      google.maps.event.addDomListener(me.resultNode_, 'click', function() {
        me.select();
      });
      document.getElementById("searchwell").appendChild(me.resultNode_);
    }

    LocalResult.prototype.node = function() {
      if (this.resultNode_) return this.resultNode_;
      return this.html();
    };

    // Returns the GMap marker for this result, creating it with the given
    // icon if it has not already been created.
    LocalResult.prototype.marker = function() {
      var me = this;
      if (me.marker_) return me.marker_;
      var marker = me.marker_ = new google.maps.Marker({
        position: new google.maps.LatLng(parseFloat(me.result_.lat),
                                         parseFloat(me.result_.lng)),
        icon: gYellowIcon, shadow: gSmallShadow, map: gMap});
      google.maps.event.addListener(marker, "click", function() {
        me.select();
      });
      return marker;
    };

    // Unselect any selected markers and then highlight this result and
    // display the info window on it.
    LocalResult.prototype.select = function() {
      unselectMarkers();
      this.selected_ = true;
      this.highlight(true);
      gInfoWindow.setContent(this.html(true));
      gInfoWindow.open(gMap, this.marker());
    };

    LocalResult.prototype.isSelected = function() {
      return this.selected_;
    };

    // Remove any highlighting on this result.
    LocalResult.prototype.unselect = function() {
      this.selected_ = false;
      this.highlight(false);
    };

    // Returns the HTML we display for a result before it has been "saved"
    LocalResult.prototype.html = function() {
      var me = this;
      var container = document.createElement("div");
      container.className = "unselected";
      container.appendChild(me.result_.html.cloneNode(true));
      return container;
    }

    LocalResult.prototype.highlight = function(highlight) {
      this.marker().setOptions({icon: highlight ? gRedIcon : gYellowIcon});
      this.node().className = "unselected" + (highlight ? " red" : "");
    }

    GSearch.setOnLoadCallback(OnLoad);
    //]]>
    </script>
  </head>
  <body style="font-family: Arial, sans-serif; font-size: small;">
    <p>Perform a local search on the map below:</p>
    <div style="width: 500px;">
      <div style="margin-bottom: 5px;">

        <div>
          <input type="text" id="queryInput" value="pizza" style="width: 250px;"/>
          <input type="button" value="Find" onclick="doSearch()"/>
        </div>
      </div>
      <div style="position: absolute; left: 540px;">
        <div id="searchwell"></div>
      </div>
      <div id="map" style="height: 350px; border: 1px solid #979797;"></div>

    </div>

  </body>
</html>

