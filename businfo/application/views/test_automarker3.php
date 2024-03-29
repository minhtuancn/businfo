<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>Google Maps</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=ABQIAAAAPDUET0Qt7p2VcSk6JNU1sBSM5jMcmVqUpI7aqV44cW1cEECiThQYkcZUPRJn9vy_TWxWvuLoOfSFBw" type="text/javascript"></script>
    <script type="text/javascript">
    /*********************************************************************\
    *                                                                     *
    * epolys.js                                          by Mike Williams *
    *                                                                     *
    * A Google Maps API Extension                                         *
    *                                                                     *
    * Adds various Methods to GPolygon and GPolyline                      *
    *                                                                     *
    * .Contains(latlng) returns true is the poly contains the specified   *
    *                   GLatLng                                           *
    *                                                                     *
    * .Area()           returns the approximate area of a poly that is    *
    *                   not self-intersecting                             *
    *                                                                     *
    * .Distance()       returns the length of the poly path               *
    *                                                                     *
    * .Bounds()         returns a GLatLngBounds that bounds the poly      *
    *                                                                     *
    * .GetPointAtDistance() returns a GLatLng at the specified distance   *
    *                   along the path.                                   *
    *                   The distance is specified in metres               *
    *                   Reurns null if the path is shorter than that      *
    *                                                                     *
    * .GetPointsAtDistance() returns an array of GLatLngs at the          *
    *                   specified interval along the path.                *
    *                   The distance is specified in metres               *
    *                                                                     *
    * .GetIndexAtDistance() returns the vertex number at the specified    *
    *                   distance along the path.                          *
    *                   The distance is specified in metres               *
    *                   Reurns null if the path is shorter than that      *
    *                                                                     *
    * .Bearing(v1?,v2?) returns the bearing between two vertices          *
    *                   if v1 is null, returns bearing from first to last *
    *                   if v2 is null, returns bearing from v1 to next    *
    *                                                                     *
    *                                                                     *
    ***********************************************************************
    *                                                                     *
    *   This Javascript is provided by Mike Williams                      *
    *   Community Church Javascript Team                                  *
    *   http://www.bisphamchurch.org.uk/                                  *
    *   http://econym.org.uk/gmap/                                        *
    *                                                                     *
    *   This work is licenced under a Creative Commons Licence            *
    *   http://creativecommons.org/licenses/by/2.0/uk/                    *
    *                                                                     *
    ***********************************************************************
    *                                                                     *
    * Version 1.1       6-Jun-2007                                        *
    * Version 1.2       1-Jul-2007 - fix: Bounds was omitting vertex zero *
    *                                add: Bearing                         *
    * Version 1.3       28-Nov-2008  add: GetPointsAtDistance()           *
    * Version 1.4       12-Jan-2009  fix: GetPointsAtDistance()           *
    *                                                                     *
    \*********************************************************************/


    // === A method for testing if a point is inside a polygon
    // === Returns true if poly contains point
    // === Algorithm shamelessly stolen from http://alienryderflex.com/polygon/ 
    GPolygon.prototype.Contains = function(point) {
      var j=0;
      var oddNodes = false;
      var x = point.lng();
      var y = point.lat();
      for (var i=0; i < this.getVertexCount(); i++) {
        j++;
        if (j == this.getVertexCount()) {j = 0;}
        if (((this.getVertex(i).lat() < y) && (this.getVertex(j).lat() >= y))
        || ((this.getVertex(j).lat() < y) && (this.getVertex(i).lat() >= y))) {
          if ( this.getVertex(i).lng() + (y - this.getVertex(i).lat())
          /  (this.getVertex(j).lat()-this.getVertex(i).lat())
          *  (this.getVertex(j).lng() - this.getVertex(i).lng())<x ) {
            oddNodes = !oddNodes
          }
        }
      }
      return oddNodes;
    }

    // === A method which returns the approximate area of a non-intersecting polygon in square metres ===
    // === It doesn't fully account for spechical geometry, so will be inaccurate for large polygons ===
    // === The polygon must not intersect itself ===
    GPolygon.prototype.Area = function() {
      var a = 0;
      var j = 0;
      var b = this.Bounds();
      var x0 = b.getSouthWest().lng();
      var y0 = b.getSouthWest().lat();
      for (var i=0; i < this.getVertexCount(); i++) {
        j++;
        if (j == this.getVertexCount()) {j = 0;}
        var x1 = this.getVertex(i).distanceFrom(new GLatLng(this.getVertex(i).lat(),x0));
        var x2 = this.getVertex(j).distanceFrom(new GLatLng(this.getVertex(j).lat(),x0));
        var y1 = this.getVertex(i).distanceFrom(new GLatLng(y0,this.getVertex(i).lng()));
        var y2 = this.getVertex(j).distanceFrom(new GLatLng(y0,this.getVertex(j).lng()));
        a += x1*y2 - x2*y1;
      }
      return Math.abs(a * 0.5);
    }

    // === A method which returns the length of a path in metres ===
    GPolygon.prototype.Distance = function() {
      var dist = 0;
      for (var i=1; i < this.getVertexCount(); i++) {
        dist += this.getVertex(i).distanceFrom(this.getVertex(i-1));
      }
      return dist;
    }

    // === A method which returns the bounds as a GLatLngBounds ===
    GPolygon.prototype.Bounds = function() {
      var bounds = new GLatLngBounds();
      for (var i=0; i < this.getVertexCount(); i++) {
        bounds.extend(this.getVertex(i));
      }
      return bounds;
    }

    // === A method which returns a GLatLng of a point a given distance along the path ===
    // === Returns null if the path is shorter than the specified distance ===
    GPolygon.prototype.GetPointAtDistance = function(metres) {
      // some awkward special cases
      if (metres == 0) return this.getVertex(0);
      if (metres < 0) return null;
      var dist=0;
      var olddist=0;
      for (var i=1; (i < this.getVertexCount() && dist < metres); i++) {
        olddist = dist;
        dist += this.getVertex(i).distanceFrom(this.getVertex(i-1));
      }
      if (dist < metres) {return null;}
      var p1= this.getVertex(i-2);
      var p2= this.getVertex(i-1);
      var m = (metres-olddist)/(dist-olddist);
      return new GLatLng( p1.lat() + (p2.lat()-p1.lat())*m, p1.lng() + (p2.lng()-p1.lng())*m);
    }

    // === A method which returns an array of GLatLngs of points a given interval along the path ===
    GPolygon.prototype.GetPointsAtDistance = function(metres) {
      var next = metres;
      var points = [];
      // some awkward special cases
      if (metres <= 0) return points;
      var dist=0;
      var olddist=0;
      for (var i=1; (i < this.getVertexCount()); i++) {
        olddist = dist;
        dist += this.getVertex(i).distanceFrom(this.getVertex(i-1));
        while (dist > next) {
          var p1= this.getVertex(i-1);
          var p2= this.getVertex(i);
          var m = (next-olddist)/(dist-olddist);
          points.push(new GLatLng( p1.lat() + (p2.lat()-p1.lat())*m, p1.lng() + (p2.lng()-p1.lng())*m));
          next += metres;    
        }
      }
      return points;
    }

    // === A method which returns the Vertex number at a given distance along the path ===
    // === Returns null if the path is shorter than the specified distance ===
    GPolygon.prototype.GetIndexAtDistance = function(metres) {
      // some awkward special cases
      if (metres == 0) return this.getVertex(0);
      if (metres < 0) return null;
      var dist=0;
      var olddist=0;
      for (var i=1; (i < this.getVertexCount() && dist < metres); i++) {
        olddist = dist;
        dist += this.getVertex(i).distanceFrom(this.getVertex(i-1));
      }
      if (dist < metres) {return null;}
      return i;
    }

    // === A function which returns the bearing between two vertices in decgrees from 0 to 360===
    // === If v1 is null, it returns the bearing between the first and last vertex ===
    // === If v1 is present but v2 is null, returns the bearing from v1 to the next vertex ===
    // === If either vertex is out of range, returns void ===
    GPolygon.prototype.Bearing = function(v1,v2) {
      if (v1 == null) {
        v1 = 0;
        v2 = this.getVertexCount()-1;
      } else if (v2 ==  null) {
        v2 = v1+1;
      }
      if ((v1 < 0) || (v1 >= this.getVertexCount()) || (v2 < 0) || (v2 >= this.getVertexCount())) {
        return;
      }
      var from = this.getVertex(v1);
      var to = this.getVertex(v2);
      if (from.equals(to)) {
        return 0;
      }
      var lat1 = from.latRadians();
      var lon1 = from.lngRadians();
      var lat2 = to.latRadians();
      var lon2 = to.lngRadians();
      var angle = - Math.atan2( Math.sin( lon1 - lon2 ) * Math.cos( lat2 ), Math.cos( lat1 ) * Math.sin( lat2 ) - Math.sin( lat1 ) * Math.cos( lat2 ) * Math.cos( lon1 - lon2 ) );
      if ( angle < 0.0 ) angle  += Math.PI * 2.0;
      angle = angle * 180.0 / Math.PI;
      return parseFloat(angle.toFixed(1));
    }




    // === Copy all the above functions to GPolyline ===
    GPolyline.prototype.Contains             = GPolygon.prototype.Contains;
    GPolyline.prototype.Area                 = GPolygon.prototype.Area;
    GPolyline.prototype.Distance             = GPolygon.prototype.Distance;
    GPolyline.prototype.Bounds               = GPolygon.prototype.Bounds;
    GPolyline.prototype.GetPointAtDistance   = GPolygon.prototype.GetPointAtDistance;
    GPolyline.prototype.GetPointsAtDistance  = GPolygon.prototype.GetPointsAtDistance;
    GPolyline.prototype.GetIndexAtDistance   = GPolygon.prototype.GetIndexAtDistance;
    GPolyline.prototype.Bearing              = GPolygon.prototype.Bearing;






        
    
    </script>
  </head>
  <body onunload="GUnload()">

    
    <div id="controls">
     <form onsubmit="start();return false" action="#">
      Enter start and end addresses.<br />
      <input type="text" size="80" maxlength="200" id="startpoint" value="Briarcrest Rd, 90046" /><br />
      <input type="text" size="80" maxlength="200" id="endpoint" value="Hollywood Hills Rd, 90046@34.11327,-118.39089" /><br />
      <input type="submit" value="Start"  />
     </form>
    </div>

    <div id="map" style="width: 700px; height: 500px"></div>
    <div id="step">&nbsp;</div>
    <div id="distance">Miles: 0.00</div>

    <script type="text/javascript">
    //<![CDATA[
    if (GBrowserIsCompatible()) {
 
      var map = new GMap2(document.getElementById("map"));
      map.addControl(new GMapTypeControl());
      map.setCenter(new GLatLng(0,0),2);
      var dirn = new GDirections();
      var step = 5; // metres
      var tick = 100; // milliseconds
      var poly;
      var eol;
      var car = new GIcon();
          car.image="caricon.png"
          car.iconSize=new GSize(32,18);
          car.iconAnchor=new GPoint(16,9);
      var marker;
      var k=0;
      var stepnum=0;
      var speed = "";   

      function animate(d) {
        if (d>eol) {
          document.getElementById("step").innerHTML = "<b>Trip completed<\/b>";
          document.getElementById("distance").innerHTML =  "Miles: "+(d/1609.344).toFixed(2);
          return;
        }
        var p = poly.GetPointAtDistance(d);
        if (k++>=180/step) {
          map.panTo(p);
          k=0;
        }
        marker.setPoint(p);
        document.getElementById("distance").innerHTML =  "Miles: "+(d/1609.344).toFixed(2)+speed;
        if (stepnum+1 < dirn.getRoute(0).getNumSteps()) {
          if (dirn.getRoute(0).getStep(stepnum).getPolylineIndex() < poly.GetIndexAtDistance(d)) {
            stepnum++;
            var steptext = dirn.getRoute(0).getStep(stepnum).getDescriptionHtml();
            document.getElementById("step").innerHTML = "<b>Next:<\/b> "+steptext;
            var stepdist = dirn.getRoute(0).getStep(stepnum-1).getDistance().meters;
            var steptime = dirn.getRoute(0).getStep(stepnum-1).getDuration().seconds;
            var stepspeed = ((stepdist/steptime) * 2.24).toFixed(0);
            step = stepspeed/2.5;
            speed = "<br>Current speed: " + stepspeed +" mph";
          }
        } else {
          if (dirn.getRoute(0).getStep(stepnum).getPolylineIndex() < poly.GetIndexAtDistance(d)) {
            document.getElementById("step").innerHTML = "<b>Next: Arrive at your destination<\/b>";
          }
        }
        setTimeout("animate("+(d+step)+")", tick);
      }

      GEvent.addListener(dirn,"load", function() {
        document.getElementById("controls").style.display="none";
        poly=dirn.getPolyline();
        eol=poly.Distance();
        //map.setCenter(poly.getVertex(0),17);
        map.addOverlay(new GMarker(poly.getVertex(0),G_START_ICON));
        map.addOverlay(new GMarker(poly.getVertex(poly.getVertexCount()-1),G_END_ICON));
        marker = new GMarker(poly.getVertex(0),{icon:car});
        map.addOverlay(marker);
        var steptext = dirn.getRoute(0).getStep(stepnum).getDescriptionHtml();
        document.getElementById("step").innerHTML = steptext;
        setTimeout("animate(0)",2000);  // Allow time for the initial map display
      });

      GEvent.addListener(dirn,"error", function() {
        alert("Location(s) not recognised. Code: "+dirn.getStatus().code);
      });

      function start() {
        var startpoint = document.getElementById("startpoint").value;
        var endpoint = document.getElementById("endpoint").value;
        dirn.loadFromWaypoints([startpoint,endpoint],{getPolyline:true,getSteps:true});
      }

    }

    // This Javascript is based on code provided by the
    // Community Church Javascript Team
    // http://www.bisphamchurch.org.uk/   
    // http://econym.org.uk/gmap/

    //]]>
    </script>
  </body>

</html>



