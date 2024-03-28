<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'disciple-tools-media/disciple-tools-media.php' );

        $this->assertContains(
            'disciple-tools-media/disciple-tools-media.php',
            get_option( 'active_plugins' )
        );
    }
}
