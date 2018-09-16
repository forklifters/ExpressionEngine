<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

require_once PATH_ADDONS.'grid/ft.grid.php';

/**
 * Grid Images Fieldtype
 */
class Grid_images_ft extends Grid_ft {

	public $info = [
		'name'		=> 'Grid Images',
		'version'	=> '1.0.0'
	];

	public $settings_form_field_name = 'grid_images';

	public function display_field($data)
	{
		$grid_markup = parent::display_field($data);

		$upload_destinations = $this->getUploadDestinations();
		$allowed_directory = $this->get_setting('allowed_directories', 'all');
		$uploading_to_lang = lang('grid_images_choose_directory');

		if (isset($upload_destinations[$allowed_directory]))
		{
			$uploading_to_lang = sprintf(
				lang('grid_images_uploading_to'),
				$upload_destinations[$allowed_directory]
			);
		}
		else
		{
			$allowed_directory = 'all';
		}

		ee()->cp->add_js_script([
			'file' => 'fields/grid/grid_images',
		]);

		return ee('View')->make('grid:grid_images')->render([
			'grid_markup'         => $grid_markup,
			'upload_destinations' => $upload_destinations,
			'allowed_directory'   => $allowed_directory,
			'lang' => [
				'grid_images_choose_directory' => lang('grid_images_choose_directory'),
				'grid_images_choose_existing' => lang('grid_images_choose_existing'),
				'grid_images_drop_files' => lang('grid_images_drop_files'),
				'grid_images_setup' => lang('grid_images_setup'),
				'grid_images_uploading_to' => $uploading_to_lang,
				'grid_images_upload_new' => lang('grid_images_upload_new'),
			]
		]);
	}

	public function display_settings($data)
	{
		$directory_choices = ['all' => lang('all')] + $this->getUploadDestinations();

		$vars = $this->getSettingsVars();
		$vars['group'] = $this->settings_form_field_name;

		$settings = [
			'field_options_grid_images' => [
				'label' => 'field_options',
				'group' => $vars['group'],
				'settings' => [
					[
						'title' => 'grid_min_rows',
						'desc' => 'grid_min_rows_desc',
						'fields' => [
							'grid_min_rows' => [
								'type' => 'text',
								'value' => isset($data['grid_min_rows']) ? $data['grid_min_rows'] : 0
							]
						]
					],
					[
						'title' => 'grid_max_rows',
						'desc' => 'grid_max_rows_desc',
						'fields' => [
							'grid_max_rows' => [
								'type' => 'text',
								'value' => isset($data['grid_max_rows']) ? $data['grid_max_rows'] : ''
							]
						]
					],
					[
						'title' => 'grid_allow_reorder',
						'fields' => [
							'allow_reorder' => [
								'type' => 'yes_no',
								'value' => isset($data['allow_reorder']) ? $data['allow_reorder'] : 'y'
							]
						]
					],
					[
						'title' => 'file_ft_content_type',
						'desc' => 'file_ft_content_type_desc',
						'fields' => [
							'field_content_type' => [
								'type' => 'radio',
								'choices' => [
									'all' => lang('all'),
									'image' => lang('file_ft_images_only')
								],
								'value' => isset($data['field_content_type']) ? $data['field_content_type'] : 'image'
							]
						]
					],
					[
						'title' => 'file_ft_allowed_dirs',
						'desc' => 'file_ft_allowed_dirs_desc',
						'fields' => [
							'allowed_directories' => [
								'type' => 'radio',
								'choices' => $directory_choices,
								'value' => isset($data['allowed_directories']) ? $data['allowed_directories'] : 'all',
								'no_results' => [
									'text' => sprintf(lang('no_found'), lang('file_ft_upload_directories')),
									'link_text' => 'add_new',
									'link_href' => ee('CP/URL')->make('files/uploads/create')
								]
							]
						]
					]
				]
			],
			'field_options_grid_images_fields' => [
				'label' => 'grid_images_setup',
				'group' => $vars['group'],
				'settings' => [$vars['grid_alert'], ee('View')->make('grid:settings')->render($vars)]
			]
		];

		$this->loadGridSettingsAssets();

		$settings_json = '{ minColumns: 0, fieldName: "grid_images" }';

		ee()->javascript->output('EE.grid_settings($(".fields-grid-setup[data-group=grid_images]"), '.$settings_json.');');
		ee()->javascript->output('FieldManager.on("fieldModalDisplay", function(modal) {
			EE.grid_settings($(".fields-grid-setup[data-group=grid_images]", modal), '.$settings_json.');
		});');

		return $settings;
	}

	private function getUploadDestinations()
	{
		if (($upload_destinations = ee()->session->cache(__CLASS__, 'upload_destinations', FALSE)) === FALSE)
		{
			$upload_destinations = ee('Model')->get('UploadDestination')
				->fields('id', 'name')
				->filter('site_id', ee()->config->item('site_id'))
				->filter('module_id', 0)
				->order('name', 'asc')
				->all()
				->getDictionary('id', 'name');

			ee()->session->set_cache(__CLASS__, 'upload_destinations', $upload_destinations);
		}

		return $upload_destinations;
	}

	/**
	 * Override parent to insert/hide our phantom File column
	 */
	public function getColumnsForSettingsView()
	{
		$columns = parent::getColumnsForSettingsView();

		if ($this->id())
		{
			foreach ($columns as &$column)
			{
				$column['col_hidden'] = TRUE;
				break;
			}
		}
		else
		{
			array_unshift($columns, [
				'col_id' => 'new_0',
				'col_type' => 'file',
				'col_label' => 'File',
				'col_name' => 'file',
				'col_instructions' => '',
				'col_required' => 'n',
				'col_search' => 'n',
				'col_width' => '',
				'col_settings' => [
					'field_content_type'  => 'image',
					'allowed_directories' => 'all'
				],
				'col_hidden' => TRUE
			]);
		}

		return $columns;
	}

	public function save_settings($data)
	{
		$settings = parent::save_settings($data);

		$settings['field_content_type'] = $data['field_content_type'];
		$settings['allowed_directories'] = $data['allowed_directories'];

		return $settings;
	}

	/**
	 * Override parent apply Grid Images upload preference settings to phantom file column
	 */
	public function post_save_settings($data)
	{
		if (isset($_POST[$this->settings_form_field_name]))
		{
			foreach ($_POST[$this->settings_form_field_name]['cols'] as $col_field => &$column)
			{
				if ($column['col_name'] == 'file')
				{
					$column['col_settings'] = [
						'field_content_type'  => ee('Request')->post('field_content_type'),
						'allowed_directories' => ee('Request')->post('allowed_directories')
					];
				}
			}
		}

		parent::post_save_settings($data);
	}
}

// EOF
