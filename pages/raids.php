<?php
require_once './vendor/autoload.php';
require_once './config.php';
require_once './includes/GeofenceService.php';
require_once './includes/utils.php';
require_once './static/data/pokedex.php';
require_once './static/data/movesets.php';

$geofenceSrvc = new GeofenceService();
$mobile = $config['ui']['table']['forceRaidCards'] || (!$config['ui']['table']['forceRaidCards'] && is_mobile());

$filters = "
<div class='container'>
  <div class='row'>
    <div class='input-group mb-3'>
      <div class='input-group-prepend'>
        <label class='input-group-text' for='search-input'>Pokemon</label>
      </div>
      <input type='text' id='search-input' class='form-control input-lg' onkeyup='filter_raids($mobile)' placeholder='Search by name..' title='Type in a name'>
    </div>
    <div class='input-group mb-3'>
      <div class='input-group-prepend'>
        <label class='input-group-text' for='filter-gym'>Gym</label>
      </div>
      <input type='text' id='filter-gym' class='form-control input-lg' onkeyup='filter_raids($mobile)' placeholder='Search by gym..' title='Type in a gym name'>
    </div>
    <div class='input-group mb-3'>
      <div class='input-group-prepend'>
        <label class='input-group-text' for='filter-city'>City</label>
      </div>
      <select id='filter-city' class='custom-select' onchange='filter_raids($mobile)'>
        <option disabled selected>Select</option>
        <option value='all'>All</option>
        <option value='" . $config['ui']['unknownValue'] . "'>" . $config['ui']['unknownValue'] . "</option>";
        $count = count($geofenceSrvc->geofences);
        for ($i = 0; $i < $count; $i++) {
            $geofence = $geofenceSrvc->geofences[$i];
            $filters .= "<option value='".$geofence->name."'>".$geofence->name."</option>";
        }
        $filters .= "
      </select>
    </div>
    <div class='input-group mb-3'>
      <div class='input-group-prepend'>
        <label class='input-group-text' for='filter-level'>Raid Level</label>
      </div>
      <select id='filter-level' class='custom-select' onchange='filter_raids($mobile)'>
        <option disabled selected>Select</option>
        <option value='all'>All</option>
        <option value='1'>1</option>
        <option value='2'>2</option>
        <option value='3'>3</option>
        <option value='4'>4</option>
        <option value='5'>5</option>
      </select>
    </div>
    <div class='input-group mb-3'>
      <div class='input-group-prepend'>
        <label class='input-group-text' for='filter-team'>Team</label>
      </div>
      <select id='filter-team' class='custom-select' onchange='filter_raids($mobile)'>
        <option disabled selected>Select</option>
        <option value='all'>All</option>
        <option value='Neutral'>Neutral</option>
        <option value='Mystic'>Mystic</option>
        <option value='Valor'>Valor</option>
        <option value='Instinct'>Instinct</option>
      </select>
    </div>
    <div class='input-group mb-3'>
      <div class='input-group-prepend'>
        <label class='input-group-text' for='search-input'>Ex-Eligible</label>
      </div>
      <select id='filter-ex' class='custom-select' onchange='filter_raids($mobile)'>
        <option disabled selected>Select</option>
        <option value='all'>All</option>
        <option value='yes'>Yes</option>
        <option value='no'>No</option>
      </select>
    </div>
  </div>
</div>
";

$modal = "
<h2 class='page-header text-center'>Ongoing raid battles</h2>
<div class='btn-group btn-group-sm float-right'>
  <button type='button' class='btn btn-dark' data-toggle='modal' data-target='#filtersModal'>
    <i class='fa fa-fw fa-filter' aria-hidden='true'></i>
  </button>
  <button type='button' class='btn btn-dark' data-toggle='modal' data-target='#columnsModal'>
    <i class='fa fa-fw fa-columns' aria-hidden='true'></i>
  </button>
</div>
<p>&nbsp;</p>
<div class='modal fade' id='filtersModal' tabindex='-1' role='dialog' aria-labelledby='filtersModalLabel' aria-hidden='true'>
  <div class='modal-dialog' role='document'>
    <div class='modal-content'>
      <div class='modal-header'>
        <h5 class='modal-title' id='filtersModalLabel'>Raid Filters</h5>
        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
          <span aria-hidden='true'>&times;</span>
        </button>
      </div>
      <div class='modal-body'>" . $filters . "</div>
      <div class='modal-footer'>
        <button type='button' class='btn btn-danger' id='reset-filters'>Reset Filters</button>
        <button type='button' class='btn btn-primary' data-dismiss='modal'>Close</button>
      </div>
    </div>
  </div>
</div>
<div class='modal fade' id='columnsModal' tabIndex='-1' role='dialog' aria-labelledby='columnsModalLabel' aria-hidden='true'>
  <div class='modal-dialog' role='document'>
    <div class='modal-content'>
      <div class='modal-header'>
        <h5 class='modal-title' id='columnsModalLabel'>Show Columns</h5>
        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
          <span aria-hidden='true'>&times;</span>
        </button>
      </div>    
      <div class='modal-body'>
        <div id='chkColumns'>
          <p><input type='checkbox' name='starts'/>&nbsp;Raid Starts</p>
          <p><input type='checkbox' name='ends'/>&nbsp;Raid Ends</p>
          <p><input type='checkbox' name='level'/>&nbsp;Raid Level</p>
          <p><input type='checkbox' name='boss'/>&nbsp;Raid Boss</p>
          <p><input type='checkbox' name='moveset'/>&nbsp;Moveset</p>
          <p><input type='checkbox' name='city'/>&nbsp;City</p>
          <p><input type='checkbox' name='team'/>&nbsp;Team</p>
          <p><input type='checkbox' name='ex'/>&nbsp;Ex-Eligible</p>
          <p><input type='checkbox' name='updated'/>&nbsp;Updated</p>
        </div>
      </div>
      <div class='modal-footer'>
        <button type='button' class='btn btn-primary' data-dismiss='modal'>Close</button>
      </div>
    </div>
  </div>
</div>
";

echo "<div id='table-refresh'>";
include_once("./includes/data_fetcher.php");
echo "</div>";
?>

<script type="text/javascript">
var refresh_rate = <?=$config['ui']['table']['refreshRateS']?>;
var refresher = setInterval(filter_raids, refresh_rate * 1000);
setTimeout(function() { clearInterval(refresher); }, 1800000);

$(document).on("click", ".delete", function(){
  $(this).parents("tr").remove();
});

var checkbox = $("#chkColumns input:checkbox"); 
var tbl = $("#gym-table");
var tblHead = $("#gym-table th");
checkbox.prop('checked', true); 
checkbox.click(function () {
  var colToHide = tblHead.filter("." + $(this).attr("name"));
  var index = $(colToHide).index();
  tbl.find('tr :nth-child(' + (index + 1) + ')').toggle();
});

if (get("raids-search-input") !== false) {
  $('#search-input').val(get("raids-search-input"));
}
if (get("raids-filter-gym") !== false) {
  $('#filter-gym').val(get("raids-filter-gym"));
}
if (get("raids-filter-city") !== false) {
  $('#filter-city').val(get("raids-filter-city"));
}
if (get("raids-filter-level") !== false) {
  $('#filter-level').val(get("raids-filter-level"));
}
if (get("raids-filter-team") !== false) {
  $('#filter-team').val(get("raids-filter-team"));
}
if (get("raids-filter-ex") !== false) {
  $('#filter-ex').val(get("raids-filter-ex"));
}

var isMobile = <?=$config['ui']['table']['forceRaidCards'] || (!$config['ui']['table']['forceRaidCards'] && is_mobile())?>;
filter_raids(isMobile);

$('#reset-filters').on('click', function() {
  if (confirm("Are you sure you want to reset the raid filters?")) {
    $('#search-input').val('');
    $('#filter-gym').val('All');
    $('#filter-city').val('All');
    $('#filter-level').val('All');
    $('#filter-team').val('All');
    $('#filter-ex').val('All');
    filter_raids(isMobile);
  }
});
</script>