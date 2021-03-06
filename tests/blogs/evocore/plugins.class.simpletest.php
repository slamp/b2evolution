<?php
/**
 * Tests for the {@link Plugins_admin} class
 * @todo dh> Rename this to plugins_admin.simpletest.php, once we have SVN or there are
 *       specific tests for Plugins.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

load_class('plugins/_plugin.class.php', 'Plugin');


/**
 * @package tests
 */
class PluginsTestCase extends EvoDbUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Plugins class test' );
	}


	function setUp()
	{
		parent::setUp();

		ob_start();
		$this->create_current_tables(); // we need current tables for Plugins to work
		ob_end_clean();

		$this->Plugins = new Plugins_admin();
	}


	function testUninstall()
	{
		$this->assertTrue( $this->Plugins->uninstall( 1 ) );

		$this->assertFalse( $this->Plugins->get_by_ID( 1 ) );
	}


	/**
	 * Test dependencies.
	 */
	function test_dependencies()
	{
		// Should return string, because simpletest_b_plugin is not installed
		$a_Plugin = & $this->Plugins->install( 'simpletests_a_plugin', 'enabled', __FILE__ );
		$this->assertIsA( $a_Plugin, 'string' );

		$b_Plugin = & $this->Plugins->install( 'simpletests_b_plugin', 'enabled', __FILE__ );
		$this->assertIsA( $b_Plugin, 'Plugin' );

		$a_Plugin = & $this->Plugins->install( 'simpletests_a_plugin', 'enabled', __FILE__ );
		$this->assertIsA( $a_Plugin, 'Plugin' );

		// The B plugin should now NOT be able to get disabled
		$dep_msgs = $this->Plugins->validate_dependencies( $b_Plugin, 'disable' );
		$this->assertEqual( array_keys($dep_msgs), array('error') );
		$this->assertEqual( count($dep_msgs['error']), 1 );

		$this->assertTrue( $this->Plugins->uninstall( $a_Plugin->ID ) );

		// The B plugin should now be able to get disabled
		$dep_msgs = $this->Plugins->validate_dependencies( $b_Plugin, 'disable' );
		$this->assertEqual( $dep_msgs, array() );
	}


	/**
	 * Test discovery of plugin events from a plugin file.
	 *
	 * @see Plugins_admin::get_registered_events()
	 */
	function test_get_registered_events()
	{
		$c_Plugin = & $this->Plugins->register( 'simpletests_c_plugin', 0, -1, NULL, dirname( __FILE__ ).'/__simpletests_c.plugin.php' );
		$this->assertEqual( $this->Plugins->get_registered_events( $c_Plugin ), array(
			'AdminBeginPayload',
			'AdminEndHtmlHead',
		) );
	}


	/**
	 * Test dependencies.
	 */
	function test_dependencies_api()
	{
		global $app_version;

		Mock::generatePartial( 'Plugin', 'PluginTestVersion', array('GetDependencies') );

		// Only major version given (not fulfilled)
		$test_Plugin = new PluginTestVersion();
		$test_Plugin->setReturnValue( 'GetDependencies', array( 'requires' => array( 'app_min' => '1000' ) ) );
		$dep_msgs = $this->Plugins->validate_dependencies( $test_Plugin, 'enable' );
		$this->assertEqual( array_keys($dep_msgs), array('error') );
		$this->assertEqual( count($dep_msgs['error']), 1 );

		// Current version given (fulfilled)
		$test_Plugin = new PluginTestVersion();
		$test_Plugin->setReturnValue( 'GetDependencies', array( 'requires' => array( 'app_min' => $app_version ) ) );
		$dep_msgs = $this->Plugins->validate_dependencies( $test_Plugin, 'enable' );
		$this->assertEqual( array_keys($dep_msgs), array() );

		// Only major version given (fulfilled)
		$test_Plugin = new PluginTestVersion();
		$test_Plugin->setReturnValue( 'GetDependencies', array( 'requires' => array( 'app_min' => '0' ) ) );
		$dep_msgs = $this->Plugins->validate_dependencies( $test_Plugin, 'enable' );
		$this->assertEqual( array_keys($dep_msgs), array() );


		// Obsolete "api_min" (fulfilled)
		$test_Plugin = new PluginTestVersion();
		$test_Plugin->setReturnValue( 'GetDependencies', array( 'requires' => array( 'api_min' => array(1, 1)) ) );
		$dep_msgs = $this->Plugins->validate_dependencies( $test_Plugin, 'enable' );
		$this->assertEqual( array_keys($dep_msgs), array() );

	}


	/**
	 *
	 */
	function test_get_next()
	{
		$this->Plugins = new Plugins_admin_no_DB();

		$a = & $this->Plugins->register('simpletests_b_plugin', 0, -1, null, __FILE__);
		$b = & $this->Plugins->register('simpletests_b_plugin', 0, -1, null, __FILE__);
		$this->assertReference( $this->Plugins->get_next(), $a );
		$this->assertReference( $this->Plugins->get_next(), $b );
		$this->assertFalse( $this->Plugins->get_next() );

		$this->Plugins->restart();
		$this->assertReference( $this->Plugins->get_next(), $a );
		$this->Plugins->restart();
		$this->assertReference( $this->Plugins->get_next(), $a );
		$this->Plugins->unregister($b, true);
		$this->assertFalse( $this->Plugins->get_next() );

	}

}


// TEST plugin classes

/**
 * This is a test plugin, used in the tests.
 *
 * It depends on {@link simpletests_b_plugin}.
 */
class simpletests_a_plugin extends Plugin
{
	function GetDependencies()
	{
		return array(
				'requires' => array(
					'plugins' => array('simpletests_b_plugin')
				)
			);

	}
}


/**
 * This is a test plugin, used in the tests
 */
class simpletests_b_plugin extends Plugin
{
	function GetDependencies()
	{
		return array();
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new PluginsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}

?>
