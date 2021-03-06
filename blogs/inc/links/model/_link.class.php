<?php
/**
 * This file implements the Link class, which manages extra links on items.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _link.class.php 7261 2014-08-27 05:55:04Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Item Link
 *
 * @package evocore
 */
class Link extends DataObject
{
	var $ltype_ID = 0;
	var $file_ID = 0;
	var $position;
	var $order;
	/**
	 * @var Link owner object
	 */
	var $LinkOwner;
	/**
	 * @access protected Use {@link get_File()}
	 */
	var $File;


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function Link( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_links', 'link_', 'link_ID',
													'datecreated', 'datemodified', 'creator_user_ID', 'lastedit_user_ID' );

		$this->delete_cascades = array(
				array( 'table'=>'T_links__vote', 'fk'=>'lvot_link_ID', 'msg'=>T_('%d votes') ),
			);

		if( $db_row != NULL )
		{
			$this->ID       = $db_row->link_ID;
			$this->ltype_ID = $db_row->link_ltype_ID;

			// source of link:
			if( $db_row->link_itm_ID != NULL )
			{ // Item
				$this->LinkOwner = & get_link_owner( 'item', $db_row->link_itm_ID );
			}
			elseif( $db_row->link_cmt_ID != NULL )
			{ // Comment
				$this->LinkOwner = & get_link_owner( 'comment', $db_row->link_cmt_ID );
			}
			else
			{ // User
				$this->LinkOwner = & get_link_owner( 'user', $db_row->link_usr_ID );
			}

			$this->file_ID = $db_row->link_file_ID;

			// TODO: dh> deprecated, check where it's used, and fix it.
			$this->File = & $this->get_File();

			$this->position = $db_row->link_position;
			$this->order = $db_row->link_order;
		}
		else
		{	// New object:

		}
	}


	/**
	 * Get (@link LinkOwner) of the link
	 * 
	 * @return LinkOwner
	 */
	function & get_LinkOwner()
	{
		return $this->LinkOwner;
	}


	/**
	 * Get {@link File} of the link.
	 *
	 * @return File
	 */
	function & get_File()
	{
		if( ! isset($this->File) )
		{
			if( isset($GLOBALS['files_Module']) )
			{
				$FileCache = & get_FileCache();
				// fp> do not halt on error. For some reason (ahem bug) a file can disappear and if we fail here then we won't be
				// able to delete the link
				$this->File = & $FileCache->get_by_ID( $this->file_ID, false, false );
			}
			else
			{
				$this->File = NULL;
			}
		}
		return $this->File;
	}


	/**
	 * Return type of target for this Link:
	 *
	 * @todo incomplete
	 */
	function target_type()
	{
 		if( !is_null($this->File) )
		{
			return 'file';
		}

		return 'unkown';
	}


	/**
	 * Get a complete tag (IMG or A HREF) pointing to the file of this link.
	 *
	 * @param array Params
	 * @return string the file tag if the file exists, empty string otherwise
	 */
	function get_tag( $params = array() )
	{
		$File = & $this->get_File();
		if( !$File )
		{ // No file
			return '';
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before_image'        => '<div class="image_block">',
				'before_image_legend' => '<div class="image_legend">', // can be NULL
				'after_image_legend'  => '</div>',
				'after_image'         => '</div>',
				'image_size'          => 'original',
				'image_link_to'       => 'original',
				'image_link_title'    => '',	// can be text or #title# or #desc#
				'image_link_rel'      => '',
				'image_class'         => '',
				'image_align'         => '',
				'image_alt'           => '',
				'image_desc'          => '#',
				'image_size_x'        => 1, // Use '2' to build 2x sized thumbnail that can be used for Retina display
			), $params );

		return $File->get_tag( $params['before_image'],
				$params['before_image_legend'],
				$params['after_image_legend'],
				$params['after_image'],
				$params['image_size'],
				$params['image_link_to'],
				$params['image_link_title'],
				$params['image_link_rel'],
				$params['image_class'],
				$params['image_align'],
				$params['image_alt'],
				$params['image_desc'],
				'link_'.$this->ID,
				$params['image_size_x'] );
	}


	/**
	 * Get the link file preview thumbnail.
	 *
	 * @return string HTML to display
	 */
	function get_preview_thumb()
	{
		$File = & $this->get_File();
		if( !$File )
		{ // No file
			return '';
		}

		return $File->get_preview_thumb( 'fulltype', array(
				'init' => true,
				'lightbox_rel' => 'lightbox[o'.$this->LinkOwner->get_ID().']',  // Mark images from the same onwer
				'link_id' => 'link_'.$this->ID  // Use 'link_' prefix to enable voting ( Note: Voting is enable only for links )
			)
		);
	}


	/**
	 * Get an url to download file
	 *
	 * @param array Params
	 * @return string|boolean URL or FALSE when Link object is broken
	 */
	function get_download_url( $params = array() )
	{
		$params = array_merge( array(
				'glue' => '&amp;', // Glue between url params
				'type' => 'page', // 'page' - url of the download page, 'action' - url to force download
			), $params );

		if( ! ( $File = & $this->get_File() ) ||
		    ! ( $LinkOwner = & $this->get_LinkOwner() ) )
		{ // Broken Link
			return false;
		}

		if( $LinkOwner->type == 'item' && $LinkOwner->Item )
		{ // Use specific url for Item to download
			switch( $params['type'] )
			{
				case 'action':
					// Get URL to froce download a file
					if( $File->get_ext() == 'zip' )
					{ // Provide direct url to ZIP files
					  // NOTE: The same hardcoded place is in the file "htsrv/download.php", lines 56-60
						return $File->get_url();
					}
					else
					{ // For other files use url through special file that forces a download action
						return get_samedomain_htsrv_url().'download.php?link_ID='.$this->ID;
					}

				case 'page':
				default:
					// Get URL to display a page with info about file and item
					return url_add_param( $LinkOwner->Item->get_permanent_url( '', '', $params['glue'] ), 'download='.$this->ID, $params['glue'] );
			}
		}
		else
		{ // Use standard url for all other types
			return $File->get_view_url( false );
		}
	}
}

?>