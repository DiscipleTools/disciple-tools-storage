<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'disciple-tools-storage/disciple-tools-storage.php' );

        $this->assertContains(
            'disciple-tools-storage/disciple-tools-storage.php',
            get_option( 'active_plugins' )
        );
    }
}
