<?php
/**
 * @package CCTM_OutputFilter
 * 
 * Shows all available Output Filters by their name.  This shouldn't show up in any dropdown lists
 * as a viable default output filter!
 */

class CCTM_help extends CCTM_OutputFilter {

	/**
	 * Don't show this filter in any dropdown menus for a Default Output Filter
	 */
	public $show_in_menus = false;
	
	/**
	 * Apply the filter.
	 *
	 * @param 	mixed 	input
	 * @param	mixed	optional arguments
	 * @return mixed
	 */
	public function filter($input, $options=null) {

		$output = '
		<style>
			div.cctm_code {
				border: solid 1px blue;
				font-size: 1.3 em; 
			 	color: blue; 
				margin: 10px; 
				padding:10px; 
				background: #FFFFB3		
			}
			#cctm_help {
				border:1px solid black;
				background-color: lightyellow; 
				width: 90%; 
				margin: 10px; 
				padding: 10px;
			}
			h2.cctm_h2 {
				font-size:25px; 
				line-height:32px;
				margin-bottom: 10px;
			}
			h3.cctm_h3 {
				font-size:18px; 
				line-height:25px;
				margin-top: 20px;
			}
		</style>
		<div id="cctm_help">
			<h2 class="cctm_h2">'.__('Output Filter Help', CCTM_TXTDOMAIN) . '</h2>
			<p>' . __('This help page was generated by <a href="http://code.google.com/p/wordpress-custom-content-type-manager/wiki/OutputFilters">Custom Content Type Manager</a> to demonstrate the use of the available Output Filters. For samples of template files, see the CCTM menus in the WordPress manager.', CCTM_TXTDOMAIN).'</p>';

		$filters = CCTM::get_available_helper_classes('filters',true);
//		print_r($filters); exit;
		foreach ($filters as $filter => $file) {
			//if( CCTM::include_output_filter_class($filter) ) {
			if($Obj = CCTM::load_object($filter,'filters')) {
/*
				$filter_name = CCTM::FILTER_PREFIX . $filter;
				$Obj = new $filter_name();
*/
				
				$output .= sprintf('<h3 class="cctm_h3">%s (%s)</h3>
					<p>%s -- [<a href="%s" target="_new">%s</a>]</p>
					<strong>%s</strong>:<br/>
					<div class="cctm_code">
					%s
					</div>
					'
					, $Obj->get_name()
					, $filter					
					, $Obj->get_description()
					, $Obj->get_url()
					, __('More info', CCTM_TXTDOMAIN)					
					, __('Example', CCTM_TXTDOMAIN)
					, highlight_string($Obj->get_example('myfield',$filter), true)
				);
			}
			else {
				$output .= sprintf( __('File not found for %s output filter: %s', CCTM_TXTDOMAIN) 
					, '<strong>'.$filter.'</strong>'
					, $file
				) . '<br/>';
			}
		}

		return $output . '</div>';
	}


	/**
	 * @return string	a description of what the filter is and does.
	 */
	public function get_description() {
		return __('The <em>help</em> filter will display all available Output Filters as an aid to the developer or designer.', CCTM_TXTDOMAIN);
	}


	/**
	 * Show the user how to use the filter inside a template file.
	 *
	 * @return string 	a code sample 
	 */
	public function get_example($fieldname='any_field',$fieldtype,$is_repeatable=false) {
		return "<?php print_custom_field('$fieldname:help'); ?>";
	}


	/**
	 * @return string	the human-readable name of the filter.
	 */
	public function get_name() {
		return __('Help', CCTM_TXTDOMAIN);
	}

	/**
	 * @return string	the URL where the user can read more about the filter
	 */
	public function get_url() {
		return __('http://code.google.com/p/wordpress-custom-content-type-manager/wiki/help_OutputFilter', CCTM_TXTDOMAIN);
	}
		
}
/*EOF*/