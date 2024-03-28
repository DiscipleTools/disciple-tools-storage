<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Media_Tab_General
 */
class Disciple_Tools_Media_Tab_General {
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        $token = Disciple_Tools_Media_Menu::instance()->token;
        $this->process_form_fields( $token );

        $my_plugin_option = get_option( $token . '_my_plugin_option' );
        ?>
        <form method="post">
            <?php wp_nonce_field( 'dt_admin_form', 'dt_admin_form_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Settings</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        My Plugin Option
                    </td>
                    <td>
                        <input type="text" name="my-plugin-option" placeholder="" value="<?php echo esc_attr( $my_plugin_option ) ?>">
                    </td>
                </tr>
                <tr>
                    <td>
                        <button class="button">Save</button>
                    </td>
                    <td></td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <?php
    }

    public function process_form_fields( $token ){
        if ( isset( $_POST['dt_admin_form_nonce'] ) &&
            wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_admin_form_nonce'] ) ), 'dt_admin_form' ) ) {

            $post_vars = dt_recursive_sanitize_array( $_POST );

            if ( isset( $post_vars['my-plugin-option'] ) ) {
                update_option( $token . '_my_plugin_option', $post_vars['my-plugin-option'] );
            }
        }
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}
