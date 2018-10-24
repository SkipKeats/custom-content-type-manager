<?php
/**
 * CCTM Pagination Configuration file
 *
 * PHP 7.2+
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Pagination_Configuration
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.6.0
 */

/**
 * CCTM Pagination Configuration Class
 *
 * Pagination: a library for generating links to pages of results, allowing you
 * to retrieve a limited number of records from the database with each query.
 *
 * NOTE: Accurate pagination requires that you count the number of available records.
 *
 * Example of Generated Links:
 *
 * << First < Prev.  1 2 3 4 5  Next >  Last >>
 *
 * Keys define which parameter to look for in the URL.
 * number_of_pagination_links_displayed controls whether you have something like
 * <<prev 1 2 3 next>> or 1 2 3 4 5 6 ...
 *
 * Templates used for formatting are assembled in the following manner:
 *
 * e.g. if the current page is 3:
 *
 *   <<First <<Prev 1 2 3 Next>> Last>>
 *   \_____/ \____/ ^ ^ ^ \____/ \____/
 *      |       |   | | |    |      +----- lastTpl
 *      |       |   | | |    +------------ nextTpl
 *      |       |   | | +----------------- currentPageTpl
 *      |       |   +-+------------------- pageTpl
 *      |       +------------------------- prevTpl
 *      +--------------------------------- firstTpl
 *
 * \_________________________________________________/
 *                    |
 *                    +-------------------- outerTpl
 *
 *
 * Make sure you've filtered any GET values before using this library!
 * -------------------------------------------------------------------
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Form_Element
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */
class CCTM_Pagination_Configuration {

	/**
	 * Default Number of Results per Page
	 *
	 * 1 or greater: This only kicks in if the $limit_key is not
	 * specified explicitly.
	 *
	 * @var integer $default_results_per_page The default is ten.
	 */
	public $default_results_per_page = 10;

	/**
	 * Number of Displayed Pagination Links
	 *
	 * 3 or greater: TODO -- it should be considered valid to have only "prev" and "next" links.
	 *
	 * @var integer $number_of_pagination_links_displayed The number that will show.
	 */
	public $number_of_pagination_links_displayed = 10;

	/**
	 * Next or Previous Jump Size
	 *
	 * 1 or greater. How many pages do <<prev or next>> jump?  Usually this is 1.
	 *
	 * @var integer $next_prev_jump_size Jump size.
	 */
	public $next_prev_jump_size = 1;

	/**
	 * Offset
	 *
	 * Name of URL parameter used to denote an integer offset, e.g. &offset=10
	 *
	 * @var string $offset_key Offset size.
	 */
	public $offset_key = 'offset';

	/**
	 * Extra Default
	 *
	 * Useful for enabling pagination within a frame.
	 *
	 * @var string $default_extra 'target="_self"'.
	 */
	public $default_extra = '';

	/**
	 * Default Base URL
	 *
	 * The pagination links modify the base URL for a given page; if the page requiring
	 * pagination is a CGI-style URL (e.g. www.domain.com/page.php?page_id=123 ), then
	 * you should set the base url accordingly so that the pagination parameters will be
	 * appended correctly (e.g. www.domain.com/page.php?page_id=123&offset=10 ).
	 *
	 * @var string $default_base_url The pagination URL.
	 */
	public $default_base_url = '?';

	/*
	 * Available Placeholders:
	 *
	 * Local to each Tpl:
	 * [+page_number+]      -- (int) The number of the current page.
	 * [+offset+]           -- (int) the offset number (0 based), e.g. 30 displays records starting at #30
	 *
	 * Global (available to all Tpls):
	 * [+base_url+]         -- (string) URL used to construct the links. Make sure you've filtered any GET values!
	 * offset               -- (string) references the $offset_key above.
	 * [+page_count+]       -- (int) total number of pages available.
	 * [+record_count+]     -- (int) total number of records.
	 * [+offset_last+]      -- (int) offest + records per page.
	 * [+records_per_page+] -- (int) the number of records showing per page.
	 * [+current_page+]     -- (int) current page number.
	 * [+first_record+]     -- (int) 1st visble record, useful for messages e.g. "displaying records 11-20"
	 * [+last_record+]      -- (int) last record visible.
	 * [+extra+]            -- (string) additional info that appears within the anchor tag, e.g. 'target="_self"'
	 *
	 * NOTE: A PHP bug prohibits: $this->$x['y']; access from within magic functions.
	 */

	/**
	 * Active Group
	 *
	 * Which group to use: this specifies a node in the $tpls array.
	 *
	 * @var string $active_group Determines which group to use.
	 */
	public $active_group = 'media';

	/**
	 * TPLS
	 *
	 * Creates the TPLS array
	 *
	 * @var array $tpls is an array.
	 */
	public $tpls = array(
		'default' => array(
			'firstTpl'       => '<a href="[+base_url+]&offset=[+offset+]" [+extra+]>&laquo; First</a> &nbsp;',
			'lastTpl'        => '&nbsp;<a href="[+base_url+]&offset=[+offset+]" [+extra+]>Last &raquo;</a>',
			'prevTpl'        => '<a href="[+base_url+]&offset=[+offset+]" [+extra+]>&lsaquo; Prev.</a>&nbsp;',
			'nextTpl'        => '&nbsp;<a href="[+base_url+]&offset=[+offset+]" [+extra+]>Next &rsaquo;</a>',
			'currentPageTpl' => '&nbsp;<span>[+page_number+]</span>&nbsp;',
			'pageTpl'        => '&nbsp;<a href="[+base_url+]&offset=[+offset+]" [+extra+]>[+page_number+]</a>&nbsp;',
			'outerTpl'       => '<div id="pagination">[+content+]<br/>
									Page [+current_page+] of [+page_count+]<br/>
									Displaying records [+first_record+] thru [+last_record+] of [+record_count+]
								</div>',
		),

		// ------------------------------------------------------------------------------!
		'media'   => array(
			'firstTpl'       => '<span class="linklike" onclick="javascript:change_page(1);">&laquo; First</span> &nbsp;',
			'lastTpl'        => '&nbsp;<span class="linklike" onclick="javascript:change_page([+page_number+]);" >Last &raquo;</span>',
			'prevTpl'        => '<span class="linklike" onclick="javascript:change_page([+page_number+]);">&lsaquo; Prev.</span>&nbsp;',
			'nextTpl'        => '&nbsp;<span class="linklike" onclick="javascript:change_page([+page_number+]);">Next &rsaquo;</span>',
			'currentPageTpl' => '&nbsp;<span class="post_selector_pagination_active_page">[+page_number+]</span>&nbsp;',
			'pageTpl'        => '&nbsp;<span class="linklike" title="[+page_number+]" onclick="javascript:change_page([+page_number+]);">[+page_number+]</span>&nbsp;',
			'outerTpl'       => '<div id="pagination">[+content+]<br/>
									Page [+current_page+] of [+page_count+]<br/>
								</div>',
		),

		// -----------------------------------------------------------------------------!
		'blue'    => array(
			'firstTpl'       => '',
			'lastTpl'        => '',
			'prevTpl'        => '<a href="[+base_url+]&offset=[+offset+]" [+extra+]>&laquo; Prev.</a>&nbsp;',
			'nextTpl'        => '&nbsp;<a href="[+base_url+]&offset=[+offset+]" [+extra+]>Next &raquo;</a>',
			'currentPageTpl' => '&nbsp;<span>[+page_number+]</span>&nbsp;',
			'pageTpl'        => '&nbsp;<a href="[+base_url+]&offset=[+offset+]" [+extra+]>[+page_number+]</a>&nbsp;',
			'outerTpl'       => '
			<style type="text/css">
				#pagination {
					color: #000;
					font-weight: bold;
					text-decoration: none;
					width: 280px;
					float: left;
					margin: 10px;
				}
				
				#pagination a {
					font-weight: bold;
					color: #4a78ec;
					margin: 0 5px;
					text-decoration: none;
					float: left;
				}
				
				#pagination ul {
					list-style-type: none;
					padding: 0px;
					margin: 0px;
					width: 65px;
					float: left;
				}
				
				#pagination ul a {
					font-weight: bold;
					color: #000;
					margin: 0 5px;
					text-decoration: underline;
					float: left;
				}
				
				#pagination span {
					font-weight: bold;
					color: #4a78ec;
					margin: 0 5px;
					text-decoration: underline;
					float:left;
					display: block;
				}
				
				#pagination p {
					text-align: center;
					color: #000;
					font-weight: normal;
				}
			</style>
			<div id="pagination">[+content+]</div>',
		),

	);

	/**
	 * Default
	 *
	 * Default array.
	 *
	 * @var array $default Default.
	 */
	public $default = array( 'firstTpl' => 'nothing' ); // What is this?

	// TODO:
	// "Scroll-lock" on/off.
	// ON: Current page appears in the middle of the links e.g. 1 2 (3) 4 5
	// OFF: (3) 4 5 6 7.
	// ------------------------------------------------------------------------------!
	// $_GET parameters.
	// ------------------------------------------------------------------------------!

	/**
	 * Limit Key
	 *
	 * Limit.
	 *
	 * @var string $limit_key Limit.
	 */
	public $limit_key = 'l';

	/**
	 * Order Key
	 *
	 * Order.
	 *
	 * @var string $order_key Order.
	 */
	public $order_key = 'ord';

	/**
	 * Directory Key
	 *
	 * Directory
	 *
	 * @var string $dir_key Directory.
	 */
	public $dir_key = 'dir';
}

/* EOF */
