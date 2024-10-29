<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'ACR_Settings' ) ) {

    class ACR_Settings extends WC_Settings_Page {

        private static $_instance;

        /** @var array Contains the default email tags, title and content. */
        public $acrEmailDefault;

        /** @var array Contains statuses considered to be abandoned. */
        public $acrOrderStatuses;

        /** @var array Contains statuses considered to be completed. */
        public $acrOrderStatusesConsideredCompleted;

        /**
         * Instance.
         *
         * @return Advanced_Cart_Recovery
         * @since 1.0.0
         */
        public static function getInstance() {

            if( !self::$_instance instanceof self )
                self::$_instance = new self;

            return self::$_instance;

        }

        /**
         * Constructor.
         */
        public function __construct() {

            $this->id    = 'acr_settings';
            $this->label = __( 'Cart Recovery', 'advanced-cart-recovery' );

            // Advanced Cart Recovery tab
            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 30 );
            add_action( 'woocommerce_settings_' . $this->id, array( $this, 'acrOutput' ) );
            add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'acrSave' ) );
            add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
            add_filter( 'woocommerce_get_sections_' . $this->id, array( $this, 'acrGetSections' ) );

            add_action( 'woocommerce_admin_field_acr_help_resources' , array( $this , 'acrRenderHelpResources' ) );
            add_action( 'woocommerce_admin_field_acr_email_schedules' , array( $this, 'acrRenderACREmailSchedules' ) );
            add_action( 'woocommerce_admin_field_acr_blacklist_emails' , array( $this, 'acrRenderACRBlacklistEmails' ) );

            // Show upsell graphics
            if( apply_filters( 'acr_show_upsells', true ) )
                add_action( 'woocommerce_admin_field_acr_upsell' , array( $this , 'acrRenderUpsellGraphics' ) );

            add_action( 'woocommerce_admin_field_acr_button', array( $this, 'acrRenderButton' ) );

            $acrEmails = ACR_Emails::getInstance();
            $this->acrEmailDefault = $acrEmails->acrDefaultTemplate;

            $this->acrOrderStatuses = apply_filters( 'acr_general_statuses',
                                        array(
                                            'wc-pending'    => __( 'Pending Payment', 'advanced-cart-recovery' ),
                                            'wc-cancelled'  => __( 'Cancelled', 'advanced-cart-recovery' ),
                                            'wc-on-hold'    => __( 'On Hold', 'advanced-cart-recovery' ),
                                            'wc-failed'     => __( 'Failed', 'advanced-cart-recovery' )
                                        )
                                    );

            $this->acrOrderStatusesConsideredCompleted = apply_filters( 'acr_general_statuses_considered_completed',
                                        array(
                                            'wc-completed'   => __( 'Completed', 'advanced-cart-recovery' ),
                                            'wc-processing'  => __( 'Processing', 'advanced-cart-recovery' )
                                        )
                                    );

            // Email Schedules
            add_action( 'woocommerce_admin_field_acr_email_wrap_wc_header_footer_field', array( $this, 'acrRenderWrapWooHeaderFooter' ) );
            add_action( 'woocommerce_admin_field_acr_content_wysiwyg', array( $this, 'acrRenderEmailContentWYSIWYG' ) );
            add_action( 'woocommerce_admin_field_acr_schedule_buttons', array( $this, 'acrRenderSchedulesButtons' ) );

            do_action( 'acr_settings_constructor' );

        }

        /**
         * Get sections.
         *
         * @return array
         * @since 1.0.0
         */

        public function acrGetSections() {

            $sections = array(
                ''                                 =>  __( 'General', 'advanced-cart-recovery' ),
                'acr_settings_email_schedules'     =>  __( 'Email Schedules', 'advanced-cart-recovery' ),
                'acr_blacklist_emails_section'     =>  __( 'Blacklist Emails', 'advanced-cart-recovery' ),
                'acr_settings_help_section'        =>  __( 'Help', 'advanced-cart-recovery' ),
            );

            return apply_filters( 'acr_get_sections_' . $this->id, $sections );

        }

        /**
         * Output the settings.
         *
         * @since 1.0.0
         */
        public function acrOutput() {

            global $current_section;

            $settings = $this->acrGetSettings( $current_section );
            WC_Admin_Settings::output_fields( $settings );

        }

        /**
         * Save settings.
         *
         * @since 1.0.0
         */
        public function acrSave() {

            global $current_section;

            $settings = $this->acrGetSettings( $current_section );
            WC_Admin_Settings::save_fields( $settings );

        }

        /**
         * Get settings array.
         *
         * @param string $current_section
         *
         * @return string
         * @since 1.0.0
         */
        public function acrGetSettings( $current_section = '' ) {

            if ( $current_section == 'acr_settings_help_section' ) {

                // Help Section
                $settings = apply_filters( 'acr_settings_help_section_settings', $this->acrGetHelpSectionSettings() );

                if( ! isset( $_GET[ 'debug' ] ) || ( isset( $_GET[ 'debug' ] ) && $_GET[ 'debug' ] != true ) ) { ?>

                    <style type="text/css">
                        .acr_manual_run_abandoned_cart_checker,
                        .acr_manual_run_email,
                        .acr_manual_run_cancelled_cart_checker,
                        .acr_manual_run_clear_all_emails,
                        .acr_manual_run_clear_all_abandoned_carts {
                            display: none !important;
                        }
                    </style><?php

                }

            } else if ( $current_section == 'acr_settings_email_schedules' ) {

                $settings = apply_filters( 'acr_settings_email_schedules_settings', $this->acrGetEmailSchedulesSectionSettings() );

            } else if ( $current_section == 'acr_blacklist_emails_section' ) {

                $settings = apply_filters( 'acr_settings_blacklist_emails_section_settings', $this->acrGetBlacklistEmailsSectionSettings() );

            } else {

                // General Section
                $settings = apply_filters( 'acr_settings_general_section_settings', $this->acrGetGeneralSectionSettings() );

            }

            return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );

        }

        /*
         |--------------------------------------------------------------------------------------------------------------
         | Section Settings
         |--------------------------------------------------------------------------------------------------------------
         */

        /**
         * Get general section settings.
         *
         * @return array
         * @since 1.0.0
         */
        private function acrGetGeneralSectionSettings(){

            $abandonedTime      = get_option( 'acr_general_cart_abandoned_time' );
            $abandonedTime      = ! empty( $abandonedTime ) ? $abandonedTime : 0 ;
            $abandonedStatus    = get_option( 'acr_general_status_considered_abandoned' );
            $setStatus          = '';

            foreach ( $abandonedStatus as $key => $status ) {
                $status = ucwords( str_replace( '-', ' ', substr( $status, 3 ) ) );
                $setStatus .= '<b>' . $status . '</b> or ';
            }
            $setStatus = substr( $setStatus, 0, -4 );

            $acrGeneralSettings = array(

                        array(
                            'title' =>  __( 'General Options', 'advanced-cart-recovery' ),
                            'type'  =>  'title',
                            'id'    =>  'acr_general_main_title'
                        ),

                        array(
                            'type'  =>  'acr_upsell',
                            'id'    =>  'acr_upsell',
                        ),

                        array(
                            'type'  =>  'sectionend',
                            'id'    =>  'acr_general_upsell_end'
                        ),

                        array(
                            'type'  =>  'title',
                            'desc'  =>  sprintf( __( '<b>Current Settings:</b> After <b>%1$s</b> hours if an Order is in [ %2$s ] it is considered abandoned.', 'advanced-cart-recovery' ), $abandonedTime, $setStatus ),
                            'id'    =>  'acr_general_current_settings'
                        ),

                        array(
                            'title'     =>  __( 'Cart Abandoned Cut-Off Time', 'advanced-cart-recovery' ),
                            'type'      =>  'number',
                            'desc'      =>  __( 'Hour(s) after which an order considered abandoned.', 'advanced-cart-recovery' ),
                            'desc_tip'  =>  __( 'Default is 6 Hours.', 'advanced-cart-recovery' ),
                            'id'        =>  'acr_general_cart_abandoned_time',
                            'css'       =>  'width:60px;',
                            'custom_attributes' => array(
                                'min'  => 0,
                                'step' => 1
                            )
                        ),

                        array(
                            'title'     =>  __( 'Abandoned Order Status', 'advanced-cart-recovery' ),
                            'type'      =>  'multiselect',
                            'desc'      =>  __( 'If an order ends up in one of the selected statuses and the customer doesn\'t make an order before the cut-off time, the order will be considered as abandoned.', 'advanced-cart-recovery' ),
                            'desc_tip'  =>  __( 'The list of order statuses that will be considered as abandoned carts. Default is Pending and Cancelled.' , 'advanced-cart-recovery' ),
                            'id'        =>  'acr_general_status_considered_abandoned',
                            'css'       =>  'width:350px;',
                            'options'   =>  $this->acrOrderStatuses
                        ),

                        array(
                            'title'     =>  __( 'Complete Order Status', 'advanced-cart-recovery' ),
                            'type'      =>  'multiselect',
                            'desc'      =>  __( 'If an order ends up in one of the selected statuses and the customer successfully makes an order, it should cancel all abandoned carts for that email address.', 'advanced-cart-recovery' ),
                            'desc_tip'  =>  __( 'The list of order statuses that will be considered as complete orders. Default is Completed.' , 'advanced-cart-recovery' ),
                            'id'        =>  'acr_general_status_considered_completed',
                            'css'       =>  'width:350px;',
                            'options'   =>  $this->acrOrderStatusesConsideredCompleted
                        ),

                        array(
                            'title'     => __( 'Cart Abandoned Forever Time', 'advanced-cart-recovery' ),
                            'desc'      => __( 'Number of days after the final email was sent that "Not Recovered" carts will be considered abandoned forever.', 'advanced-cart-recovery' ),
                            'id'        => 'acr_general_time_considered_cancelled',
                            'type'      => 'text',
                            'default'   => '7',
                            'desc_tip'  => __( 'Default is 7 Days.', 'advanced-cart-recovery' ),
                        ),

                        array(
                            'title'     => __( 'Allow Recovery With Different Email', 'advanced-cart-recovery' ),
                            'type'      => 'checkbox',
                            'desc'      => __( 'Counts customers that recover the cart and use a different email during checkout.', 'advanced-cart-recovery' ),
                            'desc_tip'  => __( 'Abandoned carts normally can only be recovered with the same email but, when enabled, this setting will allow people to checkout using a different email and the corresponding abandoned cart record will be changed to recovered.', 'advanced-cart-recovery' ),
                            'id'        => 'acr_general_allow_recovery_with_different_email',
                        ),

                        array(
                            'type'  =>  'sectionend',
                            'id'    =>  'acr_general_sectionend'
                        )
                    );

            return apply_filters( 'acr_general_settings', $acrGeneralSettings );

        }

        /**
         * Email schedule settings
         *
         * @return array
         * @since 1.0.0
         */
        public function acrGetEmailSchedulesSectionSettings(){

            $acrEmailSchedulesSettings = array(

                array(
                    'title' =>  __( 'Email Schedules', 'advanced-cart-recovery' ),
                    'type'  =>  'title',
                    'desc'  =>  '',
                    'id'    =>  'acr_email_schedules_main_title'
                ),

                array(
                    'name'  =>  '',
                    'type'  =>  'acr_email_schedules',
                    'desc'  =>  '',
                    'id'    =>  'acr_email_schedules',
                ),

                array(
                    'type'  =>  'sectionend',
                    'id'    =>  'acr_email_schedules_sectionend'
                )

            );

            return apply_filters( 'acr_email_schedules_settings', $acrEmailSchedulesSettings );
        }

        /**
         * Get blacklist section settings.
         *
         * @return array
         * @since 1.0.0
         */
        private function acrGetBlacklistEmailsSectionSettings(){

            $acrBlackListSettings = array(

                array(
                    'title' =>  __( 'Blacklist Options', 'advanced-cart-recovery' ),
                    'type'  =>  'title',
                    'desc'  =>  __( 'A list of all email addressed that have opted out of abandoned cart communication.', 'advanced-cart-recovery' ),
                    'id'    =>  'acr_help_main_title'
                ),

                array(
                    'name'  =>  '',
                    'type'  =>  'acr_blacklist_emails',
                    'desc'  =>  __( 'Enter the customer or email address you want to add in the list.', 'advanced-cart-recovery' ),
                    'id'    =>  'acr_blacklist_emails',
                ),

                array(
                    'type'  =>  'sectionend',
                    'id'    =>  'acr_blacklist_sectionend'
                )

            );

            return apply_filters( 'acr_blacklist_settings', $acrBlackListSettings );

        }

        /**
         * Get help section settings.
         *
         * @return array
         * @since 1.0.0
         */
        private function acrGetHelpSectionSettings(){

            $acrHelpSettings = array(

                array(
                    'title' =>  __( 'Help Options', 'advanced-cart-recovery' ),
                    'type'  =>  'title',
                    'desc'  =>  '',
                    'id'    =>  'acr_help_main_title'
                ),

                array(
                    'title' => '',
                    'type'  => 'acr_help_resources',
                    'desc'  => '',
                    'id'    => 'acr_help_resources'
                ),

                array(
                    'title' =>  __( 'Run Abandoned Cart', 'advanced-cart-recovery' ),
                    'type'  =>  'acr_button',
                    'desc'  =>  sprintf( __( 'This will run the %1$s hook.', 'advanced-cart-recovery' ), ACR_ABANDONED_CART_CRON ),
                    'id'    =>  'acr_manual_run_abandoned_cart_checker',
                    'class' =>  'button button-primary'
                ),

                array(
                    'title' =>  __( 'Run Email Sender', 'advanced-cart-recovery' ),
                    'type'  =>  'acr_button',
                    'desc'  =>  sprintf( __( 'This will send all scheduled cron emails under %1$s hook.', 'advanced-cart-recovery' ), ACR_EMAIL_SENDER_CRON ),
                    'id'    =>  'acr_manual_run_email',
                    'class' =>  'button button-primary'
                ),

                array(
                    'title' =>  __( 'Run Abandoned Forever', 'advanced-cart-recovery' ),
                    'type'  =>  'acr_button',
                    'desc'  =>  sprintf( __( 'This will make all not-recovered carts into cancelled under %1$s hook. <br/>
                                    ( note: %2$s hook is created after an email has succesfully sent to the customer and refreshes the time everytime there are multiple emails sent. <br/>In case the cart object is not turned to cancelld it maybe because no email has sent out yet. )', 'advanced-cart-recovery' ), ACR_CANCELLED_CART_CRON, ACR_CANCELLED_CART_CRON ),
                    'id'    =>  'acr_manual_run_cancelled_cart_checker',
                    'class' =>  'button button-primary'
                ),

                array(
                    'title' =>  __( 'Clear All Emails', 'advanced-cart-recovery' ),
                    'type'  =>  'acr_button',
                    'desc'  =>  __( 'This will remove all scheduled cron emails.', 'advanced-cart-recovery' ),
                    'id'    =>  'acr_manual_run_clear_all_emails',
                    'class' =>  'button button-primary'
                ),

                array(
                    'title' =>  __( 'Clear All Abandoned Carts', 'advanced-cart-recovery' ),
                    'type'  =>  'acr_button',
                    'desc'  =>  __( 'This will delete all not recovered carts. ( note: cancelled and recovered ones stay put )', 'advanced-cart-recovery' ),
                    'id'    =>  'acr_manual_run_clear_all_abandoned_carts',
                    'class' =>  'button button-primary'
                ),

                array(
                    'title' =>  __( 'Clean up plugin options on un-installation', 'advanced-cart-recovery' ),
                    'type'  =>  'checkbox',
                    'desc'  =>  __( 'If checked, removes all plugin options when this plugin is uninstalled. Note: Also affect premium version.', 'advanced-cart-recovery' ),
                    'id'    =>  'acr_clean_plugin_options',
                ),

                array(
                    'type'  =>  'sectionend',
                    'id'    =>  'acr_cron_sectionend'
                )

            );

            return apply_filters( 'acr_help_settings', $acrHelpSettings );

        }

        /*
         |--------------------------------------------------------------------------------------------------------------
         | Settings
         |--------------------------------------------------------------------------------------------------------------
         */

        /**
         * Render knowledge base link
         *
         * @param $data
         *
         * @since 1.0.0
         */
        public function acrRenderHelpResources( $data ) {

            do_action( 'acr_settings_before_help_contents', $data ); ?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for=""><?php _e( 'Knowledge Base' , 'advanced-cart-recovery' ); ?></label>
                </th>
                <td class="forminp forminp-<?php echo sanitize_title( $data[ 'type' ] ); ?>">
                    <?php echo sprintf( __( 'Looking for documentation? Please see our growing <a href="%1$s" target="_blank">Knowledge Base</a>' , 'advanced-cart-recovery' ) , "https://marketingsuiteplugin.com/knowledge-base/advanced-cart-recovery/?utm_source=ACR&utm_medium=Settings%20Help&utm_campaign=ACR" ); ?>
                </td>
            </tr><?php

            do_action( 'acr_settings_after_help_contents', $data );

        }

        /**
         * Render Blacklisted Emails
         *
         * @param $data
         *
         * @since 1.0.0
         */
        public function acrRenderACRBlacklistEmails( $data ){

            $acrBlacklistedEmails = get_option( ACR_BLACKLIST_EMAILS_OPTION );
            if ( !is_array( $acrBlacklistedEmails ) )
                $acrBlacklistedEmails = array();

            $woocommerce_data = ACR_Functions::get_woocommerce_data();

            do_action( 'acr_settings_before_blacklist_emails_row', $data ); ?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <div class="blacklist-controls">
                        <div class="field-container text-field-container">

                            <label for="acr_email_field"><?php _e( 'Manually Unsubscribe Email Address', 'advanced-cart-recovery' ); ?></label>
                            <span class=""></span>
                            <div class="unsubscribe-type-selector">
                                <label>
                                    <input type="radio" name="unsubscribe_type_field" value="customer" checked>
                                    <?php _e( 'Search customer' , 'advanced-cart-recovery' ); ?>
                                </label>
                                <label>
                                    <input type="radio" name="unsubscribe_type_field" value="email">
                                    <?php _e( 'Enter email manually' , 'advanced-cart-recovery' ); ?>
                                </label>
                            </div>
                            <div class="select2-field">
                                <?php if ( version_compare( $woocommerce_data[ 'Version' ] , '2.7.0' , '>=' ) || $woocommerce_data[ 'Version' ] === '2.7.0-RC1' ) : ?>
                                    <select class="wc-customer-search" id="acr_customer_field" data-placeholder="<?php _e( 'Search customer' , 'advanced-cart-recovery' ); ?>">
                                    </select>
                                <?php else : ?>
                                    <input type="hidden" class="wc-customer-search" id="acr_customer_field" data-placeholder="<?php _e( 'Search customer' , 'advanced-cart-recovery' ); ?>" data-selected="" value="" data-allow_clear="true" />
                                <?php endif; ?>
                            </div>
                            <div class="manual-email-field" style="display: none;">
                                <input type="text" id="acr_email_field" placeholder="<?php _e( 'Enter email address' , 'advanced-cart-recovery' ); ?>" />
                            </div>
                            <p class="desc"><?php echo $data[ 'desc' ]; ?></p>
                        </div>
                    </div>
                    <div class="button-controls add-mode">

                        <input type="button" id="acr-add-email" class="button button-primary" value="<?php _e( 'Add', 'advanced-cart-recovery' ); ?>"/>
                        <span class="spinner"></span>

                        <div style="clear: both; float: none; display: block;"></div>

                    </div>

                    <table id="acr-blacklist-emails-table" class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th><?php _e( 'Email', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Date Added', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Reason', 'advanced-cart-recovery' ); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th><?php _e( 'Email', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Date Added', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Reason', 'advanced-cart-recovery' ); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>

                        <tbody><?php

                        if ( $acrBlacklistedEmails ) {

                            $itemNumber =   0;

                            foreach( $acrBlacklistedEmails as $email => $reason ) {

                                $itemNumber++;
                                extract( $reason );

                                if ( $itemNumber % 2 == 0 ) { // even  ?>
                                    <tr class="even">
                                <?php } else { // odd ?>
                                    <tr class="odd alternate">
                                <?php } ?>

                                    <td class="meta hidden"></td>
                                    <td class="acr_row_email"><?php echo $email; ?></td>
                                    <td class="acr_row_date"><?php echo isset( $date ) ? date( 'Y-m-d h:i:s A', $date ) : ''; ?></td>
                                    <td class="acr_row_reason"><?php echo ucfirst( $reason ); ?></td>
                                    <td class="controls">
                                        <a class="delete dashicons dashicons-no"></a>
                                    </td>

                                </tr>
                            <?php
                            }

                        } else { ?>
                            <tr class="no-items">
                                <td class="colspanchange" colspan="7"><?php _e( 'No emails Found', 'advanced-cart-recovery' ); ?></td>
                            </tr>
                        <?php } ?>

                        </tbody>

                    </table>

                </th>
            </tr>

            <style>
                p.submit {
                    display: none !important;
                }
            </style><?php

            do_action( 'acr_settings_after_blacklist_emails_row', $data );

        }

        /**
         * Render button
         *
         * @param $data
         *
         * @since 1.0.0
         */
        public function acrRenderButton( $data ){

            // Change type accordingly
            $type = $data[ 'type' ];
            if( $type == 'acr_button')
                $type = 'button';

            // Description handling
            $description = '';
            $tip = '';

            if ( ! empty( $data[ 'desc_tip' ] ) ) {
                $tip = $data[ 'desc_tip' ];
            }

            if ( ! empty( $data[ 'desc' ] ) ) {
                $description = $data[ 'desc' ];
            }

            ob_start();

            $id = $data[ 'id' ];
            if( $id == 'acr_manual_run_abandoned_cart_checker' )
                $hookName = ACR_ABANDONED_CART_CRON;
            elseif( $id == 'acr_manual_run_email' )
                $hookName = ACR_EMAIL_SENDER_CRON;
            elseif( $id == 'acr_manual_run_cancelled_cart_checker' )
                $hookName = ACR_CANCELLED_CART_CRON;

            do_action( 'acr_settings_before_render_button_row', $data ); ?>

                <tr valign="top" class="<?php echo $id; ?>">
                    <th scope="row" class="titledesc">
                        <label for="<?php echo esc_attr( $data[ 'id' ] ); ?>"><?php echo esc_html( $data[ 'title' ] ); ?></label>
                        <span class="description"><?php echo $tip; ?></span>
                    </th><?php

                    if( isset( $hookName ) ) { ?>

                        <td class="forminp forminp-<?php echo sanitize_title( $data[ 'type' ] ); ?>">
                            <?php echo "<a class='button button-secondary' href='" . wp_nonce_url( 'admin.php?page=wc-settings&tab=acr_settings&amp;section=acr_settings_help_section&amp;action=acr-manual-cron&amp;hook-name=' . $hookName . '&debug=true', 'acr-manual-' . $hookName ) . "'>" . __( 'Run Now', 'advanced-cart-recovery' ) . "</a>"; ?>
                            <span class="description"><?php echo $description; ?></span>
                        </td><?php

                    }else{ ?>

                        <td class="forminp forminp-<?php echo sanitize_title( $data[ 'type' ] ); ?>">
                            <?php echo "<a class='button button-secondary' href='" . wp_nonce_url( 'admin.php?page=wc-settings&tab=acr_settings&amp;section=acr_settings_help_section&amp;action=' . $id . '&debug=true', 'acr-manual-' . $id ) . "'>" . __( 'Run Now', 'advanced-cart-recovery' ) . "</a>"; ?>
                            <span class="description"><?php echo $description; ?></span>
                        </td><?php

                    } ?>

                </tr><?php

            do_action( 'acr_settings_after_render_button_row', $data );

            echo ob_get_clean();

        }

        /**
         * Render the manage screen where they can add, view, update and delete email schedules
         *
         * @param array $data
         *
         * @since 1.0.0
         * @since 1.2.0 Added hooks on view and add form to able to manipulate later for ACRP.
         */
        public function acrRenderACREmailSchedules( $data ){

            $acrFunctions = new ACR_Functions;

            $acrEmailSchedules = get_option( ACR_EMAIL_SCHEDULES_OPTION );
            if ( ! is_array( $acrEmailSchedules ) )
                $acrEmailSchedules = array();

            $tableClass = is_plugin_active( 'advanced-cart-recovery-premium/advanced-cart-recovery-premium.bootstrap.php' ) ? 'acrp-enabled' : '';

            do_action( 'acr_settings_before_email_schedules', $data ); ?>

            <tr valign="top">
                <td scope="row" class="titledesc"><?php

                    $acrEmailSchedulesForm = array(

                        array(
                            'title'     =>  __( 'Subject', 'advanced-cart-recovery' ),
                            'type'      =>  'text',
                            'desc'      =>  '',
                            'desc_tip'  =>  __( 'The subject line of the email sent to the customer.', 'advanced-cart-recovery' ),
                            'id'        =>  'acr_email_subject_field'
                        ),

                        array(
                            'title'     =>  __( 'Days After Abandoned', 'advanced-cart-recovery' ),
                            'type'      =>  'number',
                            'desc'      =>  '',
                            'desc_tip'  => __( 'The number of days this email should send after an Order has been considered abandoned. Only one email is allowed per day.', 'advanced-cart-recovery' ),
                            'id'        =>  'acr_email_days_after_order_abandoned_field',
                            'css'       =>  'width:60px;',
                                        'custom_attributes' => array(
                                            'min'   => 0,
                                            'step'  => 1
                                        )
                        ),

                        array(
                            'title'     =>  __( 'Wrap emails with WooCommerce email header and footer?', 'advanced-cart-recovery' ),
                            'type'      =>  'acr_email_wrap_wc_header_footer_field',
                            'desc'      =>  __( 'If enabled, the emails will be wrapped with WooCommerce email header and footer.', 'advanced-cart-recovery' ),
                            'desc_tip'  =>  __( 'Wraps the email in the store\'s WooCommerce header and footer template for better conformity with your other store emails.', 'advanced-cart-recovery' ),
                            'id'        =>  'acr_email_wrap_wc_header_footer_field'
                        ),

                        array(
                            'title'     =>  __( 'Heading Text', 'advanced-cart-recovery' ),
                            'type'      =>  'text',
                            'desc'      =>  '',
                            'desc_tip'  => __( 'Used in the WooCommerce header as a heading text inside the email.', 'advanced-cart-recovery' ),
                            'id'        =>  'acr_email_heading_text',
                            'css'       =>  'width: 65%;'
                        ),

                        array(
                            'title'     =>  '',
                            'type'      =>  'acr_content_wysiwyg',
                            'desc'      =>  '',
                            'desc_tip'  =>  __( 'This is the body of the email. Use the template tags in your template here and they will be filled with the real values when the email is sent.', 'advanced-cart-recovery' ),
                            'id'        =>  'acr_content_wysiwyg'
                        ),

                        array(
                            'title'     =>  '',
                            'type'      =>  'acr_schedule_buttons',
                            'desc'      =>  '',
                            'id'        =>  'acr_schedule_buttons'
                        ),

                    );

                    $acrEmailSchedulesForm = apply_filters( 'acr_email_schedules_controls', $acrEmailSchedulesForm, $data ); ?>

                    <!-- Add Data -->
                    <div class="acr-email-schedules-controls" id="acr-email-schedules-controls" style="display:none;">
                        <div class="form-container">
                            <table>
                                <?php echo WC_Admin_Settings::output_fields( $acrEmailSchedulesForm ); ?>
                            </table>
                        </div>
                    </div>

                    <!-- View Data -->
                    <div id="acr-view-data">
                        <dl><?php
                            $acrViewData[] =    '<dt class="acr_email_subject_field">' .
                                                    '<label for="acr_email_subject_field"><b>'. __( 'Subject', 'advanced-cart-recovery' ) . '</b></label>' .
                                                '</dt>' .
                                                '<dd class="acr_email_subject_value">&nbsp;</dd>';
                            $acrViewData[] =    '<dt class="acr_email_days_after_order_abandoned">' .
                                                    '<label for="acr_email_days_after_order_abandoned"><b>' . __( 'Days After Abandoned', 'advanced-cart-recovery' ) .'</b></label>' .
                                                '</dt>' .
                                                '<dd class="acr_email_days_after_order_abandoned_value">&nbsp;</dd>';

                            $acrViewData[] =    '<dt class="acr_email_wrap_wc_header_footer_field">' .
                                                    '<label for="acr_email_wrap_wc_header_footer_field"><b>' . __( 'Wrap emails with WooCommerce header & Footer', 'advanced-cart-recovery' ) . '</b></label>' .
                                                '</dt>' .
                                                '<dd class="acr_email_wrap_wc_header_footer_value">&nbsp;</dd>';
                            $acrViewData[] =    '<dt class="acr_email_heading_text_field">' .
                                                    '<label for="acr_email_heading_text_field"><b>' . __( 'Heading Text', 'advanced-cart-recovery' ) . '</b></label>' .
                                                '</dt>' .
                                                '<dd class="acr_email_heading_text_value">&nbsp;</dd>';
                            $acrViewData[] =    '<dt class="acr_email_content_field"><b>' . __( 'Content', 'advanced-cart-recovery' ) . '</b></dt>' .
                                                '<dd class="acr_email_content_value">&nbsp;</dd>';

                            $acrViewData = apply_filters( 'acr_view_schedule_form', $acrViewData, $data );
                            echo implode( '', $acrViewData ); ?>

                        </dl>
                    </div>

                    <?php do_action( 'acr_before_email_schedules_table', $data, $acrEmailSchedules ); ?>

                    <table id="acr-email-schedules-table" class="wp-list-table widefat <?php echo $tableClass; ?>">
                        <thead>
                            <tr>
                                <th><?php _e( 'Subject', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Wrap with WC header & Footer', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Days After Abandoned', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Content', 'advanced-cart-recovery' ); ?></th>
                                <th class="controls"><?php _e( 'Actions', 'advanced-cart-recovery' ); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th><?php _e( 'Subject', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Wrap with WC header & Footer', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Days After Abandoned', 'advanced-cart-recovery' ); ?></th>
                                <th><?php _e( 'Content', 'advanced-cart-recovery' ); ?></th>
                                <th class="controls"><?php _e( 'Actions', 'advanced-cart-recovery' ); ?></th>
                            </tr>
                        </tfoot>
                        <tbody><?php

                            if ( $acrEmailSchedules ) {

                                $itemNumber = 0;

                                foreach( $acrEmailSchedules as $key => $val ) {
                                    $itemNumber++;

                                    $acrOnlyInitial = apply_filters( 'acr_only_initial_template', $key === 'initial' ? true : false );

                                    if( $acrOnlyInitial ){

                                        if ( $itemNumber % 2 == 0 ) { // even  ?>
                                            <tr class="acr-email-id-<?=$key ?> even"><?php
                                        } else { // odd ?>
                                            <tr class="acr-email-id-<?=$key ?> odd alternate"><?php
                                        } ?>

                                            <td class="acr-subject"><?= ! empty( $val[ 'subject' ] ) ? wc_clean( $val[ 'subject' ] ) : ''; ?></td>
                                            <td class="acr-wrap-wc-header-footer">
                                                <?= ! empty( $val[ 'wrap' ] ) ? ucfirst( $val[ 'wrap' ] ) : ''; ?>
                                            </td>
                                            <td class="acr-days-after-abandoned">
                                                <?= ! empty( $val[ 'days_after_abandoned' ] ) ? $val[ 'days_after_abandoned' ] : ''; ?>
                                                <?= ! empty( $val[ 'days_after_abandoned' ] ) && $val[ 'days_after_abandoned' ] > 1 ? 'Days' : 'Day'; ?>
                                            </td>
                                            <td class="acr-content"><?php
                                                $content = $acrFunctions->acrContentExcerpt( wc_clean( $val[ 'content' ] ), 10 );
                                                echo ! empty( $content ) ? $content : ''; ?>
                                            </td>
                                            <td class="controls">
                                                <input type="hidden" value="<?=$key ?>" class="key">
                                                <a class="view dashicons dashicons-search" title="<?php _e( 'Preview', 'advanced-cart-recovery' ); ?>"></a>
                                                <a href="#acr-email-schedules-controls" class="edit dashicons dashicons-edit" title="<?php _e( 'Edit', 'advanced-cart-recovery' ); ?>"></a>

                                                <?php do_action( 'acr_email_schedules_actions', $key ); ?>

                                            </td>

                                        </tr><?php

                                    }
                                }

                                do_action( 'acr_after_email_schedule_list', $data, $acrEmailSchedules );

                            } else { ?>
                                <tr class="no-items">
                                    <td class="colspanchange" colspan="7"><?php _e( 'No emails Found' , 'advanced-cart-recovery' ); ?></td>
                                </tr><?php
                            } ?>
                        </tbody>
                    </table>

                    <?php do_action( 'acr_after_email_schedules_table', $data, $acrEmailSchedules ); ?>

                </td>
            </tr>

            <style>
                p.submit {
                    display: none !important;
                }
            </style><?php

            do_action( 'acr_settings_after_email_schedules', $data, $this->acrEmailDefault );
        }

        /**
         * Render upsell graphic.
         *
         * @param array $data
         *
         * @since 1.0.1
         */
        public function acrRenderUpsellGraphics( $data ){ ?>

            <tr valign="top">
                <th scope="row" class="titledesc" colspan="2">
                    <a target="_blank" href="https://marketingsuiteplugin.com/product/advanced-cart-recovery/?utm_source=ACR&utm_medium=Settings%20Banner&utm_campaign=ACR">
                        <img style="outline: none;" src="<?php echo ACR_IMAGES_URL . 'general-upsells.png'; ?>" alt="<?php _e( 'Advanced Cart Recovery Premium' , 'advanced-cart-recovery' ); ?>"/>
                    </a>
                </th>
            </tr><?php

        }

        /**
         * Render Wrap WooCommerce Header and Footer option.
         *
         * @param array $data
         *
         * @since 1.3.0
         */
        public function acrRenderWrapWooHeaderFooter( $data ){ ?>

            <tr valign="top" class="">
                <th scope="row" class="titledesc">
                    <b><?php echo $data[ 'title' ]; ?></b>
                    <?php echo wc_help_tip( $data[ 'desc_tip' ], true ); ?>
                </th>
                <td class="forminp forminp-checkbox">
                    <label for="<?php echo $data[ 'id' ]; ?>">
                        <input name="<?php echo $data[ 'id' ]; ?>" id="<?php echo $data[ 'id' ]; ?>" type="checkbox" class="" value="1"> <?php echo $data[ 'desc' ]; ?>
                    </label>
                </td>
            </tr><?php

        }

        /**
         * Render email content WYSIWYG.
         *
         * @param array $data
         *
         * @since 1.2.0
         */
        public function acrRenderEmailContentWYSIWYG( $data ){ ?>

            <tr>
                <td>
                    <b><?php _e( 'Content', 'advanced-cart-recovery' ); ?></b>
                    <?php echo wc_help_tip( $data[ 'desc_tip' ], true ); ?>
                </td>
                <td>
                    <span class="description" style="display: table; margin: 10px 0px;"><?php _e( 'You can use these template tags: ', 'advanced-cart-recovery' );
                        $tags = "";
                        foreach ( $this->acrEmailDefault[ 'tags' ] as $tag => $desc ) {
                            $tags .= '<b>' . $tag . '</b>, ';
                        }
                        echo rtrim( $tags, ', ' ); ?>

                    </span><?php

                        do_action( 'acr_settings_before_email_content_wysiwyg', $data, $this->acrEmailDefault );

                        $settings = array(
                                            'textarea_rows' => 20,
                                            'wpautop'       => true,
                                            'tinymce'       => array(
                                                'height' => 400
                                            )
                                        );

                        wp_editor( '', 'acr_email_content_field', $settings ); ?>
                </td>
            </tr><?php

        }

        /**
         * Render schedules buttons.
         *
         * @param array $data
         *
         * @since 1.2.0
         */
        public function acrRenderSchedulesButtons( $data ){ ?>

            <tr>
                <td colspan='2'>
                    <input type="hidden" id="acr_email_schedule_id_field">
                    <div class="acr-button-controls"><?php

                        $acrButons[] = '<input type="button" id="acr-add-email-schedule" class="add button button-primary" value="' . __( 'Add', 'advanced-cart-recovery' ) . '"/>';
                        $acrButons[] = '<input type="button" id="acr-update-email-schedule" class="edit button button-primary" value="' . __( 'Update', 'advanced-cart-recovery' ) . '" style="display:none;"/>';
                        $acrButons[] = '<input type="button" id="acr-cancel-email-schedule" class="cancel button button-primary" value="' . __(  'Cancel', 'advanced-cart-recovery' ) . '"/>';

                        $acrButons = apply_filters( 'acr_schedule_form_buttons', $acrButons, $data );
                        echo implode( '', $acrButons ); ?>

                        <span class="spinner"></span>

                        <div style="clear: both; float: none; display: block;"></div>

                    </div>
                </td>
            </tr><?php

        }
    }
}

return new ACR_Settings();
