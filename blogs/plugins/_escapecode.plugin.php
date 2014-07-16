<?php
/**
 * This file implements the Escape code plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class escapecode_plugin extends Plugin
{
	var $code = 'escape_code';
	var $name = 'Escape code';
	var $priority = 8;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '5.0.0';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Escapes html entities in code tags');
		$this->long_desc = T_('Escapes tags and entities in &lt;code&gt; tags');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array(
				'default_post_rendering'    => 'stealth',
				'default_comment_rendering' => 'stealth'
			) );
		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Filters out the custom tag that would not validate, PLUS escapes the actual code.
	 *
	 * @param mixed $params
	 */
	function FilterItemContents( & $params )
	{
		$content = & $params['content'];
		$content = $this->escape_code( $content );

		return true;
	}


	/**
	 * Event handler: Called before at the beginning, if a comment form gets sent (and received).
	 */
	function CommentFormSent( & $params )
	{
		$ItemCache = & get_ItemCache();
		$comment_Item = & $ItemCache->get_by_ID( $params['comment_item_ID'], false );
		if( !$comment_Item )
		{ // Incorrect item
			return false;
		}

		$item_Blog = & $comment_Item->get_Blog();
		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $item_Blog );
		if( $item_Blog->get_setting( 'allow_html_comment' ) && $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{ // Do escape html entities only when html is allowed for content and plugin is enabled
			$content = & $params['comment'];
			$content = $this->escape_code( $content );
		}
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		/* Initialize this function only in order to detect this plugin as renderer */
		return true;
	}


	/**
	 * Escape html entities inside <code> tag
	 *
	 * @param string Content
	 * @return string Escaped content
	 */
	function escape_code( $content )
	{
		if( strpos( $content, '[codeblock' ) !== false || strpos( $content, '<codeblock' ) !== false )
		{ // Do escape the html entities in code blocks:
			$content = preg_replace_callback( '#([<\[]codeblock[^>\]]*[>\]])([\s\S]+?)([<\[]/codeblock[>\]])#is', array( $this, 'escape_code_callback' ), $content );
		}

		if( strpos( $content, '[codespan' ) !== false || strpos( $content, '<codespan' ) !== false )
		{ // Do escape the html entities in code spans:
			$content = preg_replace_callback( '#([<\[]codespan[>\]])([\s\S]+?)([<\[]/codespan[>\]])#is', array( $this, 'escape_code_callback' ), $content );
		}

		if( strpos( $content, '<code' ) !== false )
		{ // At least one tag <code> exists in the content, Do escape the html entities:
			$content = preg_replace_callback( '#(<code[^>]*>)([\s\S]+?)(</code>)#is', array( $this, 'escape_code_callback' ), $content );
		}

		if( strpos( $content, '```' ) !== false )
		{ // String of codeblock from markdown, Do escape the html entities:
			$content = preg_replace_callback( '#(```)([\s\S]+?)(```)#is', array( $this, 'escape_code_callback' ), $content );
		}

		return $content;
	}


	/**
	 * Escape html entities inside <code> tag
	 *
	 * @param string Code content
	 * @return string Escaped code content
	 */
	function escape_code_callback( $code_content )
	{
		// Start tag
		$escaped_content = $code_content[1];

		// Escape two chars to escape html tags inside <code>
		$escaped_content .= str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $code_content[2] );

		// End tag
		$escaped_content .= $code_content[3];

		return $escaped_content;
	}
}

?>