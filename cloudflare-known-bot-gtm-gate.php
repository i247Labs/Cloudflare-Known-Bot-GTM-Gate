<?php
/**
 * Plugin Name: i247Labs Cloudflare Known Bot GTM Gate
 * Description: Suppresses GTM4WP container output for Cloudflare-known bots.
 * Version: 1.0.0
 * Author: i247Labs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Detect Cloudflare-known bot requests.
 *
 * The custom header must be removed and overwritten
 * by Cloudflare before reaching WordPress.
 */
function i247labs_is_cloudflare_known_bot_request() {
    return isset( $_SERVER['HTTP_X_I247LABS_CF_KNOWN_BOT'] )
        && '1' === (string) $_SERVER['HTTP_X_I247LABS_CF_KNOWN_BOT'];
}

/**
 * Prevent caching of bot-specific responses.
 */
function i247labs_bot_tracking_gate_send_headers() {
    if ( ! i247labs_is_cloudflare_known_bot_request() ) {
        return;
    }

    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }

    nocache_headers();

    header(
        'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0',
        true
    );
}

add_action(
    'send_headers',
    'i247labs_bot_tracking_gate_send_headers',
    0
);

/**
 * Suppress GTM4WP container output for Cloudflare-known bots.
 */
function i247labs_bot_tracking_gate_disable_gtm( $output ) {
    if ( i247labs_is_cloudflare_known_bot_request() ) {
        return false;
    }

    return $output;
}

add_filter(
    'gtm4wp_output_container',
    'i247labs_bot_tracking_gate_disable_gtm',
    PHP_INT_MAX
);
