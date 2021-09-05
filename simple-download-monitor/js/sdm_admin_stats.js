var sdm = [];
sdm.datechart = false;
sdm.geochart = false;
sdm.activeTab = sdmAdminStats.activeTab;
sdm.apiKey = sdmAdminStats.apiKey;

jQuery('#sdm_date_buttons button').click(function (e) {
	jQuery('#sdm_choose_date').find('input[name="sdm_stats_start_date"]').val(jQuery(this).attr('data-start-date'));
	jQuery('#sdm_choose_date').find('input[name="sdm_stats_end_date"]').val(jQuery(this).attr('data-end-date'));
});

function sdm_init_chart(tab) {
	if (!sdm.datechart && tab === 'datechart') {
		sdm.datechart = true;
		google.charts.load('current', { 'packages': ['corechart'] });
		google.charts.setOnLoadCallback(sdm_drawDateChart);
	} else if (!sdm.geochart && tab === 'geochart') {
		sdm.geochart = true;
		var chartOpts = {};
		chartOpts.packages = ['geochart'];
		if (sdm.apiKey) {
			chartOpts.mapsApiKey = sdm.apiKey;
		} else {
			//show API Key warning
			jQuery('#sdm-api-key-warning').fadeIn('slow');
		}
		google.charts.load('current', chartOpts);
		google.charts.setOnLoadCallback(sdm_drawGeoChart);
	}
}
function sdm_drawDateChart() {
	var sdm_dateData = new google.visualization.DataTable();
	sdm_dateData.addColumn('string', sdmAdminStats.str.date);
	sdm_dateData.addColumn('number', sdmAdminStats.str.numberOfDownloads);
	sdm_dateData.addRows(sdmAdminStats.dByDate);

	var sdm_dateChart = new google.visualization.AreaChart(document.getElementById('downloads_chart'));
	sdm_dateChart.draw(sdm_dateData, {
		width: 'auto', height: 300, title: sdmAdminStats.str.downloadsByDate, colors: ['#3366CC', '#9AA2B4', '#FFE1C9'],
		hAxis: { title: sdmAdminStats.str.date, titleTextStyle: { color: 'black' } },
		vAxis: { title: sdmAdminStats.str.downloads, titleTextStyle: { color: 'black' } },
		legend: 'top'
	});
}
function sdm_drawGeoChart() {

	var sdm_countryData = google.visualization.arrayToDataTable(sdmAdminStats.dByCountry);

	var sdm_countryOptions = { colorAxis: { colors: ['#ddf', '#00f'] } };

	var sdm_countryChart = new google.visualization.GeoChart(document.getElementById('country_chart'));

	sdm_countryChart.draw(sdm_countryData, sdm_countryOptions);

}
jQuery(function () {
	sdm_init_chart(sdm.activeTab);
	jQuery('div.sdm-tabs a').click(function (e) {
		e.preventDefault();
		var tab = jQuery(this).attr('data-tab-name');
		jQuery('div.sdm-tabs').find('a').removeClass('nav-tab-active');
		jQuery(this).addClass('nav-tab-active');
		jQuery('div.sdm-tabs-content-wrapper').find('div.sdm-tab').hide();
		jQuery('div.sdm-tabs-content-wrapper').find('div[data-tab-name="' + tab + '"]').fadeIn('fast');
		sdm_init_chart(tab);
		jQuery('#sdm_choose_date').find('input[name="sdm_active_tab"]').val(tab);
	});
	jQuery('.datepicker').datepicker({
		dateFormat: 'yy-mm-dd'
	});
});