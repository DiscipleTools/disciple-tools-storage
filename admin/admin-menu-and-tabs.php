<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_Storage_Menu
 */
class Disciple_Tools_Storage_Menu {

    public $token = 'disciple_tools_storage';
    public $page_title = 'Storage';

    private static $_instance = null;

    /**
     * Disciple_Tools_Storage_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_Storage_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return Disciple_Tools_Storage_Menu instance
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

        $this->page_title = __( 'Storage', 'disciple-tools-storage' );
    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        $this->page_title = __( 'Storage', 'disciple-tools-storage' );

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
            <div style="background-color: rgba(142,195,81,0.2); border-radius: 5px; padding: 1em 2em; margin: 1em 0">
              <div style="display: flex; grid-gap: 1em">
                <div style="display: flex; align-items: center">
                  <img style="width: 2em; filter: invert(52%) sepia(77%) saturate(383%) hue-rotate(73deg) brightness(98%) contrast(83%);"
                       src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'exclamation-circle.svg' ); ?>" alt="Exclamation Circle"/>
                </div>
                <div style="display: flex; align-items: center">
                  <p>
                    Need Help? Check out the Storage plugin's <a href="https://disciple.tools/docs/storage/" target="_blank">documentation</a> and <a href="https://community.disciple.tools/category/17/d-t-storage" target="_blank">forum page</a>.
                  </p>
                </div>
              </div>
            </div>

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
                    <?php echo esc_html( 'Disciple.Tools Storage Plugin requires PHP version 8.1 or greater. Your current version is: ' . phpversion() . ' Please upgrade PHP.' );?>
                </span>
                <?php
            } else {
                switch ( $tab ) {
                    /*case 'general':
                        require( 'general-tab.php' );
                        $object = new Disciple_Tools_Storage_Tab_General();
                        $object->content();
                        break;*/
                    case 'connections':
                        require( 'connections-tab.php' );
                        $object = new Disciple_Tools_Storage_Tab_Connections();
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
Disciple_Tools_Storage_Menu::instance();

