<?php
/** CF7 product quote requests, with private persistence for shop managers. */
defined( 'ABSPATH' ) || exit;

add_action( 'init', static function() {
 $caps = array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts' ), 'manage_woocommerce' );
 $caps['create_posts'] = 'do_not_allow';
 register_post_type( 'nl_quote', array( 'labels' => array( 'name' => __( 'Αιτήματα προσφοράς', 'nerds-toolkit' ), 'singular_name' => __( 'Αίτημα προσφοράς', 'nerds-toolkit' ) ), 'public' => false, 'show_ui' => class_exists( 'WooCommerce' ), 'show_in_menu' => 'woocommerce', 'show_in_rest' => false, 'rewrite' => false, 'query_var' => false, 'capabilities' => $caps, 'map_meta_cap' => false, 'supports' => array( 'title', 'editor' ) ) );
} );

function nerds_lab_quote_form( WC_Product $product ): void {
 $id = (int) get_option( 'nerds_lab_quote_cf7_id' );
 if ( ! function_exists( 'wpcf7_contact_form' ) || ! $id || 'publish' !== get_post_status( $id ) || ! wpcf7_contact_form( $id ) ) { return; }
 echo '<section class="nl-product-inquiry nl-quote-cf7" aria-label="' . esc_attr__( 'Ζητήστε προσφορά', 'nerds-toolkit' ) . '">';
 echo do_shortcode( '[contact-form-7 id="' . $id . '"]' );
 echo '</section>';
}

function nerds_lab_is_quote_cf7( $form ): bool {
 return $form && (int) $form->id() === (int) get_option( 'nerds_lab_quote_cf7_id' );
}

add_filter( 'wpcf7_form_hidden_fields', static function( $fields ) {
 if ( ! nerds_lab_is_quote_cf7( WPCF7_ContactForm::get_current() ) || ! function_exists( 'is_product' ) || ! is_product() ) { return $fields; }
 $id = get_queried_object_id();
 $fields['product_id'] = $id;
 $fields['quote_nonce'] = wp_create_nonce( 'nerds_lab_quote_' . $id );
 return $fields;
} );

// Canonical product details for CF7 mail tags cannot be replaced by a visitor's input.
add_filter( 'wpcf7_posted_data', static function( $data ) {
 if ( ! nerds_lab_is_quote_cf7( WPCF7_ContactForm::get_current() ) ) { return $data; }
 $id = is_scalar( $data['product_id'] ?? null ) ? absint( $data['product_id'] ) : 0;
 $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false;
 $data['product_name'] = $product ? $product->get_name() : '';
 $data['product_sku'] = $product ? $product->get_sku() : '';
 $data['product_url'] = $product ? $product->get_permalink() : '';
 return $data;
} );

add_action( 'wpcf7_before_send_mail', static function( $form, &$abort, $submission ) {
 if ( ! nerds_lab_is_quote_cf7( $form ) ) { return; }
 $status = nerds_lab_create_quote( $submission->get_posted_data(), (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
 if ( 'sent' === $status ) { return; }
 $abort = true;
 $messages = array(
  'expired' => __( 'Ανανεώστε τη σελίδα και δοκιμάστε ξανά.', 'nerds-toolkit' ),
  'limited' => __( 'Έχετε ήδη στείλει αίτημα πρόσφατα. Δοκιμάστε ξανά αργότερα.', 'nerds-toolkit' ),
  'invalid' => __( 'Ελέγξτε το email σας και δοκιμάστε ξανά.', 'nerds-toolkit' ),
 );
 $submission->set_response( $messages[$status] ?? __( 'Το αίτημα δεν αποθηκεύτηκε. Δοκιμάστε ξανά.', 'nerds-toolkit' ) );
}, 10, 3 );

// Local previews exercise CF7 and persistence without contacting real recipients.
add_filter( 'wpcf7_skip_mail', static fn( $skip, $form ) => ( 'local' === wp_get_environment_type() && nerds_lab_is_quote_cf7( $form ) ) ? true : $skip, 10, 2 );

/** Shared validation/persistence boundary used by the real POST handler and integration tests. */
function nerds_lab_create_quote( array $input, string $address ): string {
 $id = absint( $input['product_id'] ?? 0 );
 $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false;
 if ( ! nerds_lab_catalog_enabled() || ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() || get_post_field( 'post_password', $id ) ) { return 'failed'; }
 if ( empty( $input['quote_nonce'] ) || ! is_string( $input['quote_nonce'] ) || ! wp_verify_nonce( $input['quote_nonce'], 'nerds_lab_quote_' . $id ) ) { return 'expired'; }
 $email = isset( $input['email'] ) && is_string( $input['email'] ) ? trim( $input['email'] ) : '';
 if ( strlen( $email ) > 254 || ! is_email( $email ) || ! empty( $input['website'] ) ) { return 'invalid'; }
 $email_key = 'nl_quote_email_' . hash_hmac( 'sha256', strtolower( $email ), wp_salt() );
 $address_key = 'nl_quote_ip_' . hash_hmac( 'sha256', $address, wp_salt() );
 $count = (int) get_transient( $address_key );
 if ( get_transient( $email_key ) || $count >= 10 ) { return 'limited'; }
 $request = wp_insert_post( array( 'post_type' => 'nl_quote', 'post_status' => 'private', 'post_title' => $product->get_name(), 'post_content' => sprintf( "Email: %s\n\nΠροϊόν: %s\nΚωδικός: %s\n%s", $email, $product->get_name(), $product->get_sku(), $product->get_permalink() ), 'meta_input' => array( '_nl_quote_email' => $email, '_nl_quote_product_id' => $id ) ), true );
 if ( is_wp_error( $request ) || ! $request ) { return 'failed'; }
 set_transient( $email_key, 1, MINUTE_IN_SECONDS );
 set_transient( $address_key, $count + 1, 10 * MINUTE_IN_SECONDS );
 return 'sent';
}

add_filter( 'manage_nl_quote_posts_columns', static fn( $columns ) => array_merge( $columns, array( 'nl_email' => 'Email' ) ) );
add_action( 'manage_nl_quote_posts_custom_column', static function( $column, $id ) {
 if ( 'nl_email' === $column ) { echo esc_html( get_post_meta( $id, '_nl_quote_email', true ) ); }
}, 10, 2 );
