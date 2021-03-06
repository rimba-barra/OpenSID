<link rel="stylesheet" href="<?= base_url()?>assets/css/peta.css">
<link rel="stylesheet" href="<?= base_url()?>assets/css/leaflet-measure-path.css" />

<script>
(function()
{
  var infoWindow;
  window.onload = function()
  {
		<?php if (!empty($desa['lat']) AND !empty($desa['lng'])): ?>
			var posisi = [<?=$desa['lat'].",".$desa['lng']?>];
			var zoom = <?=$desa['zoom'] ?: 10?>;
		<?php elseif (!empty($desa['path'])): ?>
			var wilayah_desa = <?=$desa['path']?>;
			var posisi = wilayah_desa[0][0];
			var zoom = <?=$desa['zoom'] ?: 10?>;
		<?php else: ?>
			var posisi = [-1.0546279422758742,116.71875000000001];
			var zoom   = 10;
		<?php endif; ?>

		//Inisialisasi tampilan peta
    var mymap = L.map('map').setView(posisi, zoom);

    mymap.fitBounds(<?=$desa['path']?>);

    //1. Menampilkan overlayLayers Peta Semua Wilayah
    var marker_desa = [];
    var marker_dusun = [];
    var marker_rw = [];
    var marker_rt = [];

    //OVERLAY WILAYAH DESA
    <?php if (!empty($desa['path'])): ?>
      set_marker_desa_content(marker_desa, <?=json_encode($desa)?>, "<?=ucwords($this->setting->sebutan_desa).' '.$desa['nama_desa']?>", "<?= favico_desa()?>", '#isi_popup');
    <?php endif; ?>

    //OVERLAY WILAYAH DUSUN
    <?php if (!empty($dusun_gis)): ?>
      set_marker_content(marker_dusun, '<?=addslashes(json_encode($dusun_gis))?>', '#FFFF00', '<?=ucwords($this->setting->sebutan_dusun)?>', 'dusun', '#isi_popup_dusun_');
    <?php endif; ?>

    //OVERLAY WILAYAH RW
    <?php if (!empty($rw_gis)): ?>
      set_marker_content(marker_rw, '<?=addslashes(json_encode($rw_gis))?>', '#8888dd', 'RW', 'rw', '#isi_popup_rw_');
    <?php endif; ?>

    //OVERLAY WILAYAH RT
    <?php if (!empty($rt_gis)): ?>
      set_marker_content(marker_rt, '<?=addslashes(json_encode($rt_gis))?>', '#008000', 'RT', 'rt', '#isi_popup_rt_');
    <?php endif; ?>

    //Menampilkan overlayLayers Peta Semua Wilayah
    var overlayLayers = overlayWil(marker_desa, marker_dusun, marker_rw, marker_rt);

    //Menampilkan BaseLayers Peta
    var baseLayers = getBaseLayers(mymap, '<?=$this->setting->google_key?>');

    //Geolocation IP Route/GPS
  	geoLocation(mymap);

    //Menambahkan zoom scale ke peta
    L.control.scale().addTo(mymap);

    var mylayer = L.featureGroup();
    var layerControl = {
      "Peta Sebaran Covid19": mylayer, // opsi untuk show/hide Peta Sebaran covid19 dari geojson dibawah
    }

    //loading Peta Covid - data geoJSON dari BNPB- https://bnpb-inacovid19.hub.arcgis.com/datasets/data-harian-kasus-per-provinsi-covid-19-indonesia
    $.getJSON("https://opendata.arcgis.com/datasets/0c0f4558f1e548b68a1c82112744bad3_0.geojson",function(data){
    	var datalayer = L.geoJson(data ,{
    		onEachFeature: function (feature, layer) {
    			var custom_icon = L.icon({"iconSize": 32, "iconUrl": "<?= base_url()?>assets/images/gis/point/covid.png"});
    			layer.setIcon(custom_icon);

    			var popup_0 = L.popup({"maxWidth": "100%"});

    			var html_a = $('<div id="html_a" style="width: 100.0%; height: 100.0%;">'
          + '<h4><b>' + feature.properties.Provinsi + '</b></h4>'
          + '<table><tr>'
          + '<th style="color:red">Positif&nbsp;&nbsp;</th>'
          + '<th style="color:green">Sembuh&nbsp;&nbsp;</th>'
          + '<th style="color:black">Meninggal&nbsp;&nbsp;</th>'
          + '</tr><tr>'
          + '<td><center><b style="color:red">' + feature.properties.Kasus_Posi + '</b></center></td>'
          + '<td><center><b style="color:green">' + feature.properties.Kasus_Semb + '</b></center></td>'
          + '<td><center><b>' + feature.properties.Kasus_Meni + '</b></center></td>'
          + '</tr></table></div>')[0];

    			popup_0.setContent(html_a);

    			layer.bindPopup(popup_0, {'className' : 'covid_pop'});
    			layer.bindTooltip(feature.properties.Provinsi, {sticky: true, direction: 'top'});
    		},
    	});
      mylayer.addLayer(datalayer);
    });

    mymap.on('layeradd layerremove', function () {
      var bounds = new L.LatLngBounds();
      mymap.eachLayer(function (layer) {
        if(mymap.hasLayer(mylayer)) {
          $('#covid_status').show();
          $('#covid_status_local').show();
        } else {
          $('#covid_status').hide();
          $('#covid_status_local').hide();
        }
        if (layer instanceof L.FeatureGroup) {
          bounds.extend(layer.getBounds());
        }
      });
      if (bounds.isValid()) {
        mymap.fitBounds(bounds);
      } else {
        mymap.fitBounds(<?=$desa['path']?>);
      }
    });

    var mainlayer = L.control.layers(baseLayers, overlayLayers, {position: 'topleft', collapsed: true}).addTo(mymap);
    var covidlayer = L.control.layers('', layerControl, {position: 'topleft', collapsed: false}).addTo(mymap);

		$('#isi_popup_dusun').remove();
		$('#isi_popup_rw').remove();
		$('#isi_popup_rt').remove();
    $('#isi_popup').remove();
    $('#covid_status').hide();
    $('#covid_status_local').hide();

  }; //EOF window.onload

})();
</script>
<div class="content-wrapper">
  <form id="mainform_map" name="mainform_map" action="" method="post">
    <div class="row">
      <div class="col-md-12">
        <div id="map">
          <div class="leaflet-top leaflet-left">
            <?php $this->load->view("gis/content_desa_web.php", array('desa' => $desa, 'list_lap' => $list_lap, 'wilayah' => ucwords($this->setting->sebutan_desa.' '.$desa['nama_desa']))) ?>
            <?php $this->load->view("gis/content_dusun_web.php", array('dusun_gis' => $dusun_gis, 'list_lap' => $list_lap, 'wilayah' => ucwords($this->setting->sebutan_dusun.' '))) ?>
            <?php $this->load->view("gis/content_rw_web.php", array('rw_gis' => $rw_gis, 'list_lap' => $list_lap, 'wilayah' => ucwords($this->setting->sebutan_dusun.' '))) ?>
            <?php $this->load->view("gis/content_rt_web.php", array('rt_gis' => $rt_gis, 'list_lap' => $list_lap, 'wilayah' => ucwords($this->setting->sebutan_dusun.' '))) ?>
            <div id="covid_status">
              <?php $this->load->view("gis/covid_peta.php") ?>
            </div>
          </div>
          <div class="leaflet-top leaflet-right">
            <div id="covid_status_local">
              <?php $this->load->view("gis/covid_peta_local.php") ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<div  class="modal fade" id="modalKecil" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
  <div class="modal-dialog modal-sm">
    <div class='modal-content'>
      <div class='modal-header'>
        <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
        <h4 class='modal-title' id='myModalLabel'></h4>
      </div>
      <div class="fetched-data"></div>
    </div>
  </div>
</div>

<div  class="modal fade" id="modalSedang" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
  <div class="modal-dialog">
    <div class='modal-content'>
      <div class='modal-header'>
        <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
        <h4 class='modal-title' id='myModalLabel'></h4>
      </div>
      <div class="fetched-data"></div>
    </div>
  </div>
</div>

<div  class="modal fade" id="modalBesar" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
  <div class="modal-dialog modal-lg">
    <div class='modal-content'>
      <div class='modal-header'>
        <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
        <h4 class='modal-title' id='myModalLabel'><i class='fa fa-exclamation-triangle text-red'></i></h4>
      </div>
      <div class="fetched-data"></div>
    </div>
  </div>
</div>

<script src="<?= base_url()?>assets/js/peta.js"></script>
<script src="<?= base_url()?>assets/js/turf.min.js"></script>
<script src="<?= base_url()?>assets/js/leaflet-providers.js"></script>
<script src="<?= base_url()?>assets/js/L.Control.Locate.min.js"></script>
<script src="<?= base_url()?>assets/js/leaflet-measure-path.js"></script>
