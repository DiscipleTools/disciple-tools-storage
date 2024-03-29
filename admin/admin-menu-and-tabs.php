<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_Media_Menu
 */
class Disciple_Tools_Media_Menu {

    public $token = 'disciple_tools_media';
    public $page_title = 'Media';

    private static $_instance = null;

    /**
     * Disciple_Tools_Media_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_Media_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return Disciple_Tools_Media_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        $this->page_title = __( 'Media', 'disciple-tools-media' );
    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        $this->page_title = __( 'Media', 'disciple-tools-media' );

        add_submenu_page( 'dt_extensions', $this->page_title, $this->page_title, 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple.Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        if ( isset( $_GET['tab'] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
        } else {
            $tab = 'connections';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2>DISCIPLE TOOLS : <?php echo esc_html( $this->page_title ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <!--<a href="<?php echo esc_attr( $link ) . 'general' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'general' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">General</a>-->
                <a href="<?php echo esc_attr( $link ) . 'connections' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'connections' ) ? 'nav-tab-active' : '' ); ?>">Connections</a>
            </h2>

            <?php
            // Ensure required PHP version can be detected.
            if ( version_compare( phpversion(), '8.1', '<' ) ) {
                ?>
                <br>
                <span class="notice notice-error" style="display: inline-block; padding-top: 10px; padding-bottom: 10px; width: 97%;">
                    <?php echo esc_html( 'Disciple.Tools Media Plugin requires PHP version 8.1 or greater. Your current version is: ' . phpversion() . ' Please upgrade PHP.' );?>
                </span>
                <?php
            } else {
                switch ( $tab ) {
                    /*case 'general':
                        require( 'general-tab.php' );
                        $object = new Disciple_Tools_Media_Tab_General();
                        $object->content();
                        break;*/
                    case 'connections':
                        require( 'connections-tab.php' );
                        $object = new Disciple_Tools_Media_Tab_Connections();
                        $object->content();
                        break;
                    default:
                        break;
                }
            }
            ?>
        </div><!-- End wrap -->
        <?php
    }
}
Disciple_Tools_Media_Menu::instance();

