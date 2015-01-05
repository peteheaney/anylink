<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AnyLink Fieldtype File
 *
 * @category    Fieldtype
 * @author      Pete Heaney
 */

class Anylink_ft extends EE_Fieldtype {

		var $info = array(
				'name'      => 'AnyLink',
				'version'   => '1.1',
				'author'		=> 'Pete Heaney'
		);

		/**
		 * Constructor
		 */
		function __construct()
		{
			parent::__construct();
			$this->EE = get_instance();

			$this->site = $this->EE->config->item('site_id');
		}

		public function accepts_content_type($name)
		{
			return ($name == 'channel' || $name == 'grid');
		}

		/**
		 * Include CSS
		 */
		private function _include_css()
		{
			$this->EE->cp->add_to_head('<style type="text/css">
				.al-field{ display:none; }
				.al-field.show{ display:inline; }
				.al-field, .al-options{ margin-right:5px; }
				.al-options, .al-page{ padding:4px; border:1px solid #8195A0; border-radius:3px; color:#5F6C74; min-width:200px; }
				.al-options:focus, .al-page:focus { padding:3px; border:2px solid #8195A0; }
				span.al-page{ padding:4px; border:0; }
			</style>');

			$this->cache['css'] = TRUE;
		}

		/**
		 * Include JS
		 */
		private function _include_js()
		{
			$this->EE->cp->add_to_foot("<script type='text/javascript'>
					$(function(){
						$(document).on('change', '.al-options', function() {
							$(this).siblings('.al-field').hide();
							$(this).siblings('.al-field.al-'+this.value).show();
						});
					});
				</script>");
		}

		/**
		 * Checks if the Pages module is installed
		 */
		private function _pages_mod_installed()
		{
			$query = get_instance()->db->get_where('modules', 'module_name = "Pages"');
			return $query->num_rows() ? TRUE : FALSE;
		}

		/**
		 * Get pages data
		 */
		private function _get_pages()
		{

			$query = ee()->db
									 ->where('site_id', $this->site )
									 ->limit(1)
									 ->get('sites');

			$data = array();

			if($query->num_rows() > 0)
			{
				$row = $query->row();
					$data = array(
					'site_pages' => $row->site_pages
				);
			}

			$pages = unserialize(base64_decode($data['site_pages']));
			return $pages;
		}

		/**
		 * Generate the publish page UI
		 */
		private function _the_form($name, $data)
		{
			$this->EE->lang->loadfile('anylink');

			if(is_array($data))
			{
				$field_data = $data;
			}
			else
			{
				$field_data = unserialize(base64_decode($data));
			}

			$this->EE->load->helper('form');
			$this->_include_css();
			$this->_include_js();

			$pages_mod_installed = $this->_pages_mod_installed();

			$options = array(
				'' => '',
				'url' => lang('url_option'),
				'email' => lang('email_option')
			);

			$options_field_data = 'id="'.$name.'_type" class="al-options"';

			$url_field_data = array(
				'name' => $name."[url]",
				'style' => 'width:20%',
				'id' => $name.'_url',
				'placeholder' => 'http://www.example.com'
			);
			$url_field_data['class'] = $field_data['type'] === 'url' ? 'al-url al-field show' : 'al-url al-field';

			$email_field_data = array(
				'name' => $name."[email]",
				'class' => 'al-email al-field',
				'style' => 'width:20%',
				'id' => $name.'_email',
				'placeholder' => 'info@example.com'
			);
			$email_field_data['class'] = $field_data['type'] === 'email' ? 'al-email al-field show' : 'al-email al-field';

			if($pages_mod_installed)
			{
				$options['page'] = lang('page_option');
				if($field_data['type'] === 'page')
				{
					$page_field_data = 'id="'.$name.'_url" class="al-page al-field show"';
				}
				else
				{
					$page_field_data = 'id="'.$name.'_url" class="al-page al-field"';
				}

				$blank = array('' => lang('select_page'));
				$pages_arrt = $this->_get_pages();
				$pages_arr = (isset($pages_arrt[$this->site]['uris'])) ? $pages_arrt[$this->site]['uris'] : array();
				$have_pages = count($pages_arr) ? TRUE : FALSE;

				if($have_pages)
				{
					natsort($pages_arr);
					$pages = $blank + $pages_arr;
				}
			}

			$form = '';
			$form .= '<div class="al-fields-wrapper">';
			$form .= form_dropdown($name."[type]", $options, $field_data['type'], $options_field_data);
			$form .= form_input($url_field_data, $field_data['url']);
			$form .= form_input($email_field_data, $field_data['email']);
			if($pages_mod_installed)
			{
				if($have_pages)
				{
					$form .=form_dropdown($name."[page]", $pages, $field_data['page'], $page_field_data);
				}
				else
				{
					$form .= '<span class="al-page al-field">' . lang('no_pages') . '</span>';
				}
			}
			$form .= '</div>';

			return $form;
		}

		/**
		 * Display Field on Publish Page
		 *
		 * @access  public
		 * @param   existing data
		 * @return  field html
		 *
		 */
		function display_field($field_data)
		{
				return $this->_the_form($this->field_name, $field_data);
		}

		/**
     * Display Cell
     */
    function display_cell($cell_data)
    {
        return $this->_the_form($this->cell_name, $cell_data);
    }

		/**
		 * Display Field within Grid on Publish Page
		 *
		 * @access  public
		 * @param   existing data
		 * @return  field html
		 *
		 */
		function grid_display_field($field_data)
		{
				return $this->_the_form($this->field_name, $field_data);
		}

		/**
     * Display for Low Variables
     */
		public function display_var_field($field_data)
    {
        return $this->_the_form($this->field_name, $field_data);
    }

		/**
		 * Preprocess data on frontend
		 *
		 * @access  public
		 * @param   field data
		 * @return  prepped data
		 *
		 */
		function pre_process($data)
		{
			$field_data = unserialize(base64_decode($data));

			if($field_data['type'] === 'url')
			{
				return $field_data['url'];
			}
			elseif($field_data['type'] === 'page')
			{
				$pagest = $this->_get_pages();
				$pages = $pagest[$this->site]['uris'];
				$site_url = rtrim($this->EE->config->item('site_url'), '/');
				return $site_url . $pages[$field_data['page']];
			}
			elseif($field_data['type'] === 'email')
			{
				return 'mailto:' . $field_data['email'];
			}
			else
			{
				return '';
			}
		}

		/**
		 * Replace tag
		 *
		 * @access  public
		 * @param   field data
		 * @param   field parameters
		 * @param   data between tag pairs
		 * @return  replacement text
		 *
		 */
		function replace_tag($data, $params = array(), $tagdata = FALSE)
		{
			return $data;
		}

		/**
		 * Save Data
		 *
		 * @access  public
		 * @param   submitted field data
		 * @return  string to save
		 *
		 */
		function save($current_data)
		{
			if($current_data['type'] !== '')
			{
				$data['type'] = $current_data['type'];
				$data['url'] = $data['type'] === 'url' ? $current_data['url'] : '';
				$data['page'] = $data['type'] === 'page' ? $current_data['page'] : '';
				$data['email'] = $data['type'] === 'email' ? $current_data['email'] : '';
				return base64_encode(serialize($data));
			}
			else
			{
				return '';
			}
		}

		public function save_var_field($field_data)
		{
			return $this->save($field_data);
		}
		function display_var_tag($data, $params, $tagdata)
		{
	      return $this->pre_process($data);
		}

		/**
     * Save Cell
     */
    function save_cell($data)
    {
        return $this->save($data);
    }

		/**
		 * Validate field input
		 *
		 * @access  public
		 * @param   submitted field data
		 * @return  TRUE or an error message
		 *
		 */
		function validate($data)
		{
			$this->EE->lang->loadfile('anylink');

			if($data['type'] === '' && $this->settings['field_required'] === 'y')
			{
				return lang('missing_link_link');
			}
			elseif($data['type'] === 'page')
			{
				if($data['page'] === '')
				{
					return lang('select_page_error');
				}
				else
				{
					return TRUE;
				}
			}
			elseif($data['type'] === 'email')
			{
				$this->EE->load->helper('email');
				if(valid_email($data['email']))
				{
					return TRUE;
				}
				else
				{
					return lang('email_error');
				}
			}
			else
			{
				return TRUE;
			}
		}

		/**
		 * Validate Matrix field input
		 *
		 * @access  public
		 * @param   submitted field data
		 * @return  TRUE or an error message
		 *
		 */
		function validate_cell($data)
		{
			$this->EE->lang->loadfile('anylink');

			if($data['type'] === '' && $this->settings['col_required'] === 'y')
			{
				return lang('missing_link_link');
			}
			elseif($data['type'] === 'page')
			{
				if($data['page'] === '')
				{
					return lang('select_page_error');
				}
				else
				{
					return TRUE;
				}
			}
			elseif($data['type'] === 'email')
			{
				$this->EE->load->helper('email');
				if(valid_email($data['email']))
				{
					return TRUE;
				}
				else
				{
					return lang('email_error');
				}
			}
			else
			{
				return TRUE;
			}
		}

}

/* End of file ft.anylink.php */
/* Location: /system/expressionengine/third_party/anylink/ft.anylink.php */
