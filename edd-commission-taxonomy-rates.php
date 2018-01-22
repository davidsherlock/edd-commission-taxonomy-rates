<?php
/**
 * Plugin Name:     Easy Digital Downloads - Commission Taxonomy Rates
 * Plugin URI:      https://sellcomet.com/downloads/commission-taxonomy-rates
 * Description:     Set commission rates on a per-category or tag basis.
 * Version:         1.0.1
 * Author:          Sell Comet
 * Author URI:      https://sellcomet.com
 * Text Domain:     edd-commission-taxonomy-rates
 * Domain Path:     languages
 *
 * @package         EDD\Commission_Taxonomy_Rates
 * @author          Sell Comet
 * @copyright       Copyright (c) Sell Comet
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Commission_Taxonomy_Rates' ) ) {

    /**
     * Main EDD_Commission_Taxonomy_Rates class
     *
     * @since       1.0.0
     */
    class EDD_Commission_Taxonomy_Rates {

        /**
         * @var         EDD_Commission_Taxonomy_Rates $instance The one true EDD_Commission_Taxonomy_Rates
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_Commission_Taxonomy_Rates
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Commission_Taxonomy_Rates();
                self::$instance->setup_constants();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_COMMISSION_TAXONOMY_RATES_VER', '1.0.1' );

            // Plugin path
            define( 'EDD_COMMISSION_TAXONOMY_RATES_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_COMMISSION_TAXONOMY_RATES_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {

            if ( is_admin() ) {

                // Register commission extension settings
                add_filter( 'eddc_settings', array( $this, 'settings' ), 1 );

                /**
                * Download Categories
                */

                // Add "Rate" taxonomy header
                add_filter( 'manage_edit-download_category_columns', array( $this, 'add_field_columns' ), 10, 1 );

                // Add "Rate" taxonomy contents
                add_filter( 'manage_download_category_custom_column', array( $this, 'add_field_column_contents' ), 10, 3 );

                // Add "Commission Rate" text field to add taxonomy page
                add_action( 'download_category_add_form_fields', array( $this, 'add_meta_fields' ), 10, 2 );

                // Add "Commission Rate" text field to edit taxonomy page
                add_action( 'download_category_edit_form_fields', array( $this, 'edit_meta_fields' ), 10, 2 );

                // Save "Commission Rate" text field for newly created taxonomies
                add_action( 'created_download_category', array( $this, 'save_taxonomy_meta' ), 10, 2 );

                // Save "Commission Rate" text field for edited taxonomies
                add_action( 'edited_download_category', array( $this, 'save_taxonomy_meta' ), 10, 2 );

                /**
                * Download Tags
                */

                // Add "Rate" taxonomy header
                add_filter( 'manage_edit-download_tag_columns', array( $this, 'add_field_columns' ), 10, 1 );

                // Add "Rate" taxonomy contents
                add_filter( 'manage_download_tag_custom_column', array( $this, 'add_field_column_contents' ), 10, 3 );

                // Add "Commission Rate" text field to add taxonomy page
                add_action( 'download_tag_add_form_fields', array( $this, 'add_meta_fields' ), 10, 2 );

                // Add "Commission Rate" text field to edit taxonomy page
                add_action( 'download_tag_edit_form_fields', array( $this, 'edit_meta_fields' ), 10, 2 );

                // Save "Commission Rate" text field for newly created taxonomies
                add_action( 'created_download_tag', array( $this, 'save_taxonomy_meta' ), 10, 2 );

                // Save "Commission Rate" text field for edited taxonomies
                add_action( 'edited_download_tag', array( $this, 'save_taxonomy_meta' ), 10, 2 );

            }

        // Filter the eddc_get_recipient_rate function to check and set the taxonomy rate
        add_filter( 'eddc_get_recipient_rate', array( $this, 'get_recipient_taxonomy_rate' ), 10, 3 );

        }


        /**
         * Add "Rate" column header to taxonomy page
         *
         * @access      public
         * @since       1.0.0
         * @param       array $columns The taxonomy column headers
         * @return      array $columns The taxonomy column headers with our addition
         */
        public function add_field_columns( $columns ) {
            $columns['commission_rate'] = __( 'Rate', 'edd-commission-taxonomy-rates' );

            return $columns;
        }


        /**
         * Add "Rate" column contents to taxonomy page
         *
         * @access      public
         * @since       1.0.0
         * @param       string $content The taxonomy content
         * @param       string $column_name The taxonomy column name
         * @param       integer $term_id The taxonomy term ID
         * @return      array $content The taxonomy content with our addition
         */
        public function add_field_column_contents( $content, $column_name, $term_id ) {
            switch( $column_name ) {
                case 'commission_rate' :
                    $meta = get_term_meta( $term_id, 'edd_commission_taxonomy_rate', true );
                    $content = ( ! empty ( $meta ) ) ? $meta : '—';
                    break;
            }

            return $content;
        }


        /**
         * Add "Commission Rate" text input field to add taxonomy page
         *
         * @access      public
         * @since       1.0.0
         * @param       string $taxonomy The taxonomy category
         * @return      void
         */
        public function add_meta_fields( $taxonomy ) {
            ?>
            <div class="form-field term-rate-wrap">
            	<label for="edd_commission_taxonomy_rate"><?php _e( 'Commission Rate', 'edd-commission-taxonomy-rates' ); ?></label>
            	<input name="edd_commission_taxonomy_rate" id="edd_commission_taxonomy_rate" type="text" value="" size="40" aria-required="true" />
            	<p><?php _e('The taxonomy commission rate. This can be overwritten on a per-product basis. 10 = 10%'); ?></p>
            </div>
            <?php
        }


        /**
         * Add "Commission Rate" text input field to edit taxonomy page
         *
         * @access      public
         * @since       1.0.0
         * @param       string $term The term object containing the ID
         * @param       string $taxonomy The taxonomy category
         * @return      void
         */
        public function edit_meta_fields( $term, $taxonomy ) {
            $rate = get_term_meta( $term->term_id, 'edd_commission_taxonomy_rate', true );

            ?>
            <tr class="form-field term-rate-wrap">
            	<th scope="row"><label for="edd_commission_taxonomy_rate"><?php _e( 'Commission Rate', 'edd-commission-taxonomy-rates' ); ?></label></th>
            	<td><input name="edd_commission_taxonomy_rate" id="edd_commission_taxonomy_rate" type="text" value="<?php if ( isset( $rate ) ) echo esc_attr( $rate ); ?>" size="40" aria-required="true" />
            	<p class="description"><?php _e('The taxonomy commission rate. This can be overwritten on a per-product basis. 10 = 10%'); ?></p></td>
            </tr>
            <?php
        }


        /**
         * Sanitize and save "Commission Rate" text field for newly created and edited taxonomies
         *
         * @access      public
         * @since       1.0.0
         * @param       string $term The term object containing the ID
         * @param       string $taxonomy The taxonomy category
         * @return      void
         */
        public function save_taxonomy_meta( $term_id, $tag_id ) {
            if ( current_user_can( 'manage_shop_settings' ) ) {
                if ( isset( $_POST['edd_commission_taxonomy_rate'] ) ) {

                  $rate = sanitize_text_field( $_POST['edd_commission_taxonomy_rate'] );
                  $rate = $rate < 0 || ! is_numeric( $rate ) ? '' : $rate;

                  update_term_meta( $term_id, 'edd_commission_taxonomy_rate', $rate );
                }
            }
        }


        /**
         * Get the taxonomy commission rate based on the download ID
         *
         * @access      public
         * @since       1.0.0
         * @param       integer $download_id The download ID
         * @param       string $taxonomy The taxonomy category to check
         * @param       boolean $highest True for highest value, false for lowest
         * @return      void
         */
        public function get_taxonomy_rate( $download_id = 0, $taxonomy = 'download_category', $highest = true ) {
            $ret = null;

            $post_categories = get_the_terms( $download_id, $taxonomy );

            if ( ! empty( $post_categories ) && ! is_wp_error( $post_categories ) ) {
              $categories = wp_list_pluck( $post_categories, 'term_id' );
            }

            if ( ! empty ( $categories ) ) {
                $rates = array();

                foreach( $categories as $category ) {
                    $meta = get_term_meta( $category, 'edd_commission_taxonomy_rate', true );

                    if ( empty( $meta ) ) {
                        continue;
                    }

                    $rates[] = $meta;
                }

                if ( ! empty( $rates ) ) {
                    $ret = ( $highest ) ? max( $rates ) : min( $rates );
                }
            }

            return apply_filters( 'edd_get_taxonomy_commission_rate', $ret, $download_id );
        }


        /**
         * Retrieves the commission rate for a product, Taxonomies (tags and categories) and user
         *
         * The rate flow priority is Download, Tags, Categories, User then Global
         *
         * If $download_id is empty, the taxonomy rate from the download tags and categories is retrieved.
         * If taxonomies do not contain any rates, the user account rate is retrieved.
         * If no default rate is set on the user account, the global default is used.
         *
         * This function requires very strict typecasting to ensure the proper rates are used at all times.
         *
         * 0 is a permitted rate so we cannot use empty(). We always use NULL to check for non-existent values.
         *
         * @access      public
         * @since       1.0.0
         * @param       $download_id INT The ID of the download product to retrieve the commission rate for
         * @param       $user_id INT The user ID to retrieve commission rate for
         * @return      $rate INT|FLOAT The commission rate
         */
        public function get_recipient_taxonomy_rate( $rate, $download_id, $user_id ) {
            $rate = null;

            // Get commissions taxonomy tate preference
            $rate_preference = ( edd_get_option( 'edd_commissions_rate_preference', 'highest' ) == 'lowest' ) ? false : true;

            // Check for a rate specified on a specific product
            if ( ! empty( $download_id ) ) {
                $settings   = get_post_meta( $download_id, '_edd_commission_settings', true );

                    if ( ! empty( $settings ) && is_array( $settings ) ) {
                        $rates      = isset( $settings['amount'] ) ? array_map( 'trim', explode( ',', $settings['amount'] ) ) : array();
                        $recipients = array_map( 'trim', explode( ',', $settings['user_id'] ) );
                        $rate_key   = array_search( $user_id, $recipients );

                    if ( isset( $rates[ $rate_key ] ) ) {
                        $rate = $rates[ $rate_key ];
                    }
                }
            }

            // Check for download tag taxonomy commission rate
            if ( null === $rate || '' === $rate ) {
                $rate = $this->get_taxonomy_rate( $download_id, 'download_tag', $rate_preference );

                if ( null === $rate || '' === $rate ) {
                    $rate = null;
                }
            }

            // Check for download category taxonomy commission rate
            if ( null === $rate || '' === $rate ) {
                $rate = $this->get_taxonomy_rate( $download_id, 'download_category', $rate_preference );

                if ( null === $rate || '' === $rate ) {
                    $rate = null;
                }
            }

            // Check for a user specific global rate
            if ( ! empty( $user_id ) && ( null === $rate || '' === $rate ) ) {
                $rate = get_user_meta( $user_id, 'eddc_user_rate', true );

                if ( '' === $rate ) {
                    $rate = null;
                }
            }

            // Check for an overall global rate
            if ( null === $rate && eddc_get_default_rate() ) {
                $rate = eddc_get_default_rate();
            }

            // Set rate to 0 if no rate was found
            if ( null === $rate || '' === $rate ) {
                $rate = 0;
            }

            return apply_filters( 'edd_get_recipient_taxonomy_rate', (float) $rate, $download_id, $user_id );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_TAXONOMY_RATES_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_plugin_name_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-commission-taxonomy-rates' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-commission-taxonomy-rates', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-commission-taxonomy-rates/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-commission-thresholds/ folder
                load_textdomain( 'edd-commission-taxonomy-rates', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-commission-thresholds/languages/ folder
                load_textdomain( 'edd-commission-taxonomy-rates', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-commission-taxonomy-rates', false, $lang_dir );
            }
        }


        /**
         * Add new site-wide settings under "Downloads" > "Extensions" > "Commissions" for commissions threshold.
         *
         * @access    public
         * @since     1.0.0
         * @param     array $commission_settings The array of settings for the Commissions settings page.
         * @return    array $commission_settings The merged array of settings for the Commissions settings page.
         */
        public function settings( $commission_settings ) {
            $new_settings = array(
                array(
                    'id'      => 'edd_commission_taxonomy_rates_header',
                    'name'    => '<strong>' . __( 'Taxonomy Settings', 'edd-commission-thresholds' ) . '</strong>',
                    'desc'    => '',
                    'type'    => 'header',
                    'size'    => 'regular',
                ),
                array(
            		'id'      => 'edd_commissions_rate_preference',
            		'name'    => __( 'Rate Preference', 'edd-commission-taxonomy-rates' ),
            		'desc'    => __( 'This option determines whether or not to favour the highest or lowest taxonomy commission rate.', 'edd-commission-taxonomy-rates' ),
            		'type'    => 'radio',
            		'std'     => 'highest',
            		'options' => array(
            			'highest' => __( 'Highest, favour the taxonomy with the highest commission rate', 'edd-commission-taxonomy-rates' ),
            			'lowest'  => __( 'Lowest, favour the taxonomy with the lowest commission rate', 'edd-commission-taxonomy-rates' ),
            		),
            		'tooltip_title' => __( 'Taxonomy Rate Preference', 'edd-commission-taxonomy-rates' ),
            		'tooltip_desc'  => __( 'Downloads can be assigned different taxonomies, such as categories and tags. If a download is assigned multiple taxonomies, this setting gives you the option to favor either the highest or the lowest commission rate.', 'edd-commission-taxonomy-rates' ),
            	),
            );

            return array_merge( $commission_settings, $new_settings );
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_Commission_Taxonomy_Rates
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_Commission_Taxonomy_Rates The one true EDD_Commission_Taxonomy_Rates
 */
function EDD_Commission_Taxonomy_Rates_load() {
    if ( ! class_exists( 'Easy_Digital_Downloads' ) || ! class_exists( 'EDDC' ) ) {
        if ( ! class_exists( 'EDD_Extension_Activation' ) || ! class_exists( 'EDD_Commissions_Activation' ) ) {
            require_once 'includes/class-activation.php';
        }

        // Easy Digital Downloads activation
        if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
            $edd_activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
            $edd_activation = $edd_activation->run();
        }

        // Commissions activation
		if ( ! class_exists( 'EDDC' ) ) {
			$edd_commissions_activation = new EDD_Commissions_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
			$edd_commissions_activation = $edd_commissions_activation->run();
		}

    } else {

      return EDD_Commission_Taxonomy_Rates::instance();
    }
}
add_action( 'plugins_loaded', 'EDD_Commission_Taxonomy_Rates_load' );
