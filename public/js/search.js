
(function($){
  function setIf(params,n,v){ if(v && (''+v).length) params.set(n,v); }
  function submitFilters(){
    var p = new URLSearchParams();
    setIf(p,'q',$('#keg-q').val());
    setIf(p,'event_category',$('#keg-ecat').val());
    setIf(p,'event_city',$('#keg-ecity').val());
    setIf(p,'event_mode',$('#keg-mode').val());
    setIf(p,'date_preset',$('#keg-date-preset').val());
    setIf(p,'date_from',$('#keg-date-from').val());
    setIf(p,'date_to',$('#keg-date-to').val());
    window.location = window.location.pathname + (p.toString() ? '?' + p.toString() : '') + '#events';
  }
  $(document).on('submit','#keg-filters',function(e){ e.preventDefault(); submitFilters(); });
  $(document).on('click','#keg-clear',function(){ window.location = window.location.pathname + '#events'; });

  // Date tabs
  $(document).on('click','.keg-date-tabs .keg-tab',function(){
    var v=$(this).data('preset');
    $('#keg-date-preset').val(v);
    $('.keg-date-tabs .keg-tab').removeClass('active'); $(this).addClass('active');
    if(v==='custom'){ $('#keg-date-picker-wrap').slideDown(150); } else { $('#keg-date-picker-wrap').slideUp(150); $('#keg-date-from,#keg-date-to').val(''); }
  });
  $(function(){ if($('#keg-date-preset').val()==='custom'){ $('#keg-date-picker-wrap').show(); } else { $('#keg-date-picker-wrap').hide(); } });

  // Geolocation
  function geoStatus(t){ $('#keg-geo-status').text(t); }
  function reverseGeocode(lat,lon){
    var url='https://nominatim.openstreetmap.org/reverse?format=json&accept-language=en&zoom=10&lat='+lat+'&lon='+lon;
    return fetch(url,{headers:{'Accept':'application/json'}}).then(function(r){ return r.json(); });
  }
  function useLocation(){
    if(!navigator.geolocation){ geoStatus('Geolocation not supported.'); return; }
    geoStatus('Requesting your location…');
    navigator.geolocation.getCurrentPosition(function(pos){
      var lat=pos.coords.latitude, lon=pos.coords.longitude;
      geoStatus('Finding your city…');
      reverseGeocode(lat,lon).then(function(d){
        var city=d.address&&(d.address.city||d.address.town||d.address.village||d.address.state);
        if(city){ $('#keg-ecity').val(city); geoStatus('Using location: '+city); submitFilters(); }
        else { geoStatus('Could not detect a nearby city.'); }
      }).catch(function(){ geoStatus('Could not resolve your city.'); });
    }, function(err){
      geoStatus(err && err.code===1 ? 'Permission denied. Allow location for this site.' : 'Unable to get location.');
    }, {enableHighAccuracy:false, timeout:8000, maximumAge:600000});
  }
  $(document).on('click','#keg-use-location',useLocation);
})(jQuery);
