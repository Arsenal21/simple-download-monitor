var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.serverSideRender,
	SelectControl = wp.components.SelectControl,
	TextControl = wp.components.TextControl,	
	ToggleControl = wp.components.ToggleControl,
	InspectorControls = wp.blockEditor.InspectorControls;

registerBlockType('simple-download-monitor/download-item', {
    title: sdmBlockDownloadItemStr.title,
    icon: 'download',
    category: 'common',

	edit: function (props) {
		return [
			el(ServerSideRender, {
				block: 'simple-download-monitor/download-item',
				attributes: props.attributes,
			}),
			el(InspectorControls, {}, el('div', {className: "sdm-download-block-ic-wrapper"}, [
					el(SelectControl, {
						label: sdmBlockDownloadItemStr.download,
						help: sdmBlockDownloadItemStr.downloadHelp,
						value: props.attributes.itemId,
						options: sdmDownloadBlockItems,
						onChange: (value) => {
							props.setAttributes({itemId: value});
						},
					}),
					el(SelectControl, {
						label: sdmBlockDownloadItemStr.fancy,
						help: sdmBlockDownloadItemStr.fancyHelp,
						value: props.attributes.fancyId,
						options: sdmDownloadBlockFancy,
						onChange: (value) => {
							props.setAttributes({fancyId: value});
						},
					}),
					el(SelectControl, {
						label: sdmBlockDownloadItemStr.color,
						help: sdmBlockDownloadItemStr.colorHelp,
						value: props.attributes.color,
						options: sdmDownloadBlockColor,
						onChange: (value) => {
							props.setAttributes({color: value});
						},
					}),
					el(TextControl, {
						label: sdmBlockDownloadItemStr.buttonText,
						value: props.attributes.buttonText,
						help: sdmBlockDownloadItemStr.buttonTextHelp,
						onChange: (value) => {
							props.setAttributes({buttonText: value});
						},
					}),
					el(ToggleControl, {
						label: sdmBlockDownloadItemStr.newWindow,
						checked: props.attributes.newWindow,
						onChange: (state) => {
							props.setAttributes({newWindow: state});
						},
					})
				])
			),
		];
	},

	save: function () {
		return null;
	},
});