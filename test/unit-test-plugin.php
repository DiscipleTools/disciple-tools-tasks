<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'disciple-tools-tasks/disciple-tools-tasks.php' );

        $this->assertContains(
            'disciple-tools-tasks/disciple-tools-tasks.php',
            get_option( 'active_plugins' )
        );
    }
}
