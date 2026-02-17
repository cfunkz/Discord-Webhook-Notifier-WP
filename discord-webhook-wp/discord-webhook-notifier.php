<?php
/**
 * Plugin Name:  Discord Webhook Notifier
 * Description:  Send WordPress post notifications to Discord channels via webhooks.
 * Version:      2.0.1
 * Author:       cFunkz
 * License:      GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DWN_VERSION',    '2.0.1' );
define( 'DWN_DIR',        plugin_dir_path( __FILE__ ) );
define( 'DWN_URL',        plugin_dir_url( __FILE__ ) );
define( 'DWN_OPTION_KEY', 'dwn_webhooks' );

register_activation_hook( __FILE__, function () {
    if ( false === get_option( DWN_OPTION_KEY ) ) {
        update_option( DWN_OPTION_KEY, [] );
    }
} );

// ═══════════════════════════════════════════════════════════════════════════
//  ADMIN
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'admin_menu', function () {
    add_menu_page(
        'Discord Webhooks', 'Discord', 'manage_options',
        'discord-webhook-notifier', 'dwn_admin_page',
        'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 127.14 96.36">'
            . '<path fill="#fff" d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83'
            . 'A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09'
            . 'C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25'
            . 'a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0'
            . 'c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1'
            . 'A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07z'
            . 'M42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69z'
            . 'M84.69,65.69C78.41,65.69,73.25,60,73.25,53s5-12.74,11.44-12.74S96.23,46,96.12,53,91.08,65.69,84.69,65.69z"/>'
            . '</svg>'
        ),
        30
    );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( strpos( $hook, 'discord-webhook-notifier' ) === false ) return;
    wp_enqueue_style(  'dwn-admin', DWN_URL . 'admin/admin.css', [], DWN_VERSION );
    wp_enqueue_script( 'dwn-admin', DWN_URL . 'admin/admin.js', [ 'jquery' ], DWN_VERSION, true );
    wp_localize_script( 'dwn-admin', 'DWN', [
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'dwn_nonce' ),
        'site_name' => get_bloginfo( 'name' ),
    ] );
} );

function dwn_admin_page() {
    $webhooks   = get_option( DWN_OPTION_KEY, [] );
    $categories = get_categories( [ 'hide_empty' => false ] );
    $tags       = get_tags( [ 'hide_empty' => false ] );
    $authors    = get_users( [ 'who' => 'authors' ] );
    include DWN_DIR . 'admin/admin-page.php';
}

// ═══════════════════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Read a boolean display setting.
 * All settings are stored as the string '1' or '0'.
 * Falls back to $default when the key doesn't exist (handles webhooks
 * created before a setting was introduced).
 */
function dwn_bool( array $hook, string $key, bool $default = true ): bool {
    if ( ! array_key_exists( $key, $hook ) ) return $default;
    $v = $hook[ $key ];
    if ( is_bool( $v ) ) return $v;      // backwards-compat with old bool values
    return ( (string) $v === '1' );
}

// ═══════════════════════════════════════════════════════════════════════════
//  AJAX — save
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_dwn_save_webhook', function () {
    check_ajax_referer( 'dwn_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );

    $raw = isset( $_POST['webhook'] ) ? (array) $_POST['webhook'] : [];

    // Validate URL
    $url = esc_url_raw( trim( $raw['url'] ?? '' ) );
    if ( empty( $url ) ) wp_send_json_error( 'Webhook URL is required.' );
    if ( strpos( $url, 'discord.com/api/webhooks' ) === false &&
         strpos( $url, 'discordapp.com/api/webhooks' ) === false ) {
        wp_send_json_error( 'Must be a Discord webhook URL (discord.com/api/webhooks/...).' );
    }

    $id = sanitize_key( $raw['id'] ?? '' );
    if ( empty( $id ) ) $id = 'dwn_' . uniqid();

    // Helper: read a '1'/'0' checkbox value sent from JS
    $cb = fn( string $k, string $d = '0' ) => ( ( $raw[ $k ] ?? $d ) === '1' ) ? '1' : '0';

    $entry = [
        'id'              => $id,
        'name'            => sanitize_text_field( $raw['name'] ?? 'Unnamed Webhook' ),
        'url'             => $url,
        'username'        => wp_strip_all_tags( $raw['username'] ?? '' ),
        'avatar_url'      => esc_url_raw( trim( $raw['avatar_url'] ?? '' ) ),
        'message'         => sanitize_textarea_field( $raw['message'] ?? '' ),
        // Trigger settings
        'on_publish'      => $cb( 'on_publish', '1' ),
        'on_update'       => $cb( 'on_update',  '0' ),
        'enabled'         => $cb( 'enabled',    '1' ),
        // Embed display toggles
        'show_author'     => $cb( 'show_author',     '1' ),
        'show_excerpt'    => $cb( 'show_excerpt',    '1' ),
        'show_categories' => $cb( 'show_categories', '1' ),
        'show_tags'       => $cb( 'show_tags',       '1' ),
        // Image placement
        'image_position'  => in_array( $raw['image_position'] ?? 'large',
                                [ 'large', 'thumbnail', 'both', 'none' ] )
                             ? $raw['image_position'] : 'large',
        // Embed colors
        'color_new'       => sanitize_hex_color( $raw['color_new']    ?? '' ) ?: '#57F287',
        'color_update'    => sanitize_hex_color( $raw['color_update'] ?? '' ) ?: '#5865F2',
        // Post filters
        'filter_type'     => sanitize_key( $raw['filter_type'] ?? 'all' ),
        'categories'      => array_map( 'absint', (array)( $raw['categories'] ?? [] ) ),
        'tags'            => array_map( 'absint', (array)( $raw['tags'] ?? [] ) ),
        'authors'         => array_map( 'absint', (array)( $raw['authors'] ?? [] ) ),
    ];

    $webhooks = get_option( DWN_OPTION_KEY, [] );
    $found    = false;
    foreach ( $webhooks as &$hook ) {
        if ( $hook['id'] === $id ) { $hook = $entry; $found = true; break; }
    }
    unset( $hook );
    if ( ! $found ) $webhooks[] = $entry;

    update_option( DWN_OPTION_KEY, $webhooks );
    wp_send_json_success( [ 'id' => $id ] );
} );

// ═══════════════════════════════════════════════════════════════════════════
//  AJAX — delete
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_dwn_delete_webhook', function () {
    check_ajax_referer( 'dwn_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );

    $id = sanitize_key( $_POST['id'] ?? '' );
    if ( empty( $id ) ) wp_send_json_error( 'Missing ID.' );

    $webhooks = array_values( array_filter(
        get_option( DWN_OPTION_KEY, [] ),
        fn( $h ) => $h['id'] !== $id
    ) );
    update_option( DWN_OPTION_KEY, $webhooks );
    wp_send_json_success();
} );

// ═══════════════════════════════════════════════════════════════════════════
//  AJAX — toggle enabled (instant, no Save needed)
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_dwn_toggle_webhook', function () {
    check_ajax_referer( 'dwn_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );

    $id      = sanitize_key( $_POST['id'] ?? '' );
    $enabled = ( ( $_POST['enabled'] ?? '0' ) === '1' ) ? '1' : '0';

    $webhooks = get_option( DWN_OPTION_KEY, [] );
    foreach ( $webhooks as &$hook ) {
        if ( $hook['id'] === $id ) { $hook['enabled'] = $enabled; break; }
    }
    unset( $hook );
    update_option( DWN_OPTION_KEY, $webhooks );
    wp_send_json_success();
} );

// ═══════════════════════════════════════════════════════════════════════════
//  AJAX — test
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_dwn_test_webhook', function () {
    check_ajax_referer( 'dwn_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );

    $url = esc_url_raw( trim( $_POST['url'] ?? '' ) );
    if ( empty( $url ) ) wp_send_json_error( 'No URL.' );

    $site   = get_bloginfo( 'name' );
    $result = dwn_send( $url, [
        'username' => $site,
        'embeds'   => [ [
            'author'      => [
                'name'     => 'Discord Webhook Notifier — Test',
                'icon_url' => 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&s=64',
            ],
            'title'       => '🧪 Connection Successful!',
            'description' => "Your webhook is working correctly.\nPosts from **{$site}** will be sent here.",
            'color'       => 0x57F287,
            'fields'      => [
                [ 'name' => '📂  Categories', 'value' => 'Technology  ·  News',            'inline' => false ],
                [ 'name' => '🏷️  Tags',       'value' => '`wordpress`  `plugin`  `test`',  'inline' => false ],
            ],
            'image'  => [ 'url' => 'https://images.unsplash.com/photo-1614741118887-7a4ee193a5fa?w=800&q=80' ],
            'footer' => [ 'text' => '✨  New post on ' . $site ],
            'timestamp' => gmdate( 'c' ),
        ] ],
    ] );

    if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
    wp_send_json_success( 'Test sent!' );
} );

// ═══════════════════════════════════════════════════════════════════════════
//  POST TRIGGER — fires ONCE per publish/update, guaranteed
// ═══════════════════════════════════════════════════════════════════════════
//
//  WHY THIS APPROACH:
//  Gutenberg uses the REST API and makes two separate HTTP requests when saving
//  a post — one for the post data, one for taxonomies/meta. Each request is an
//  independent PHP process, so in-memory (static) deduplication resets between
//  them. Both requests trigger wp_after_insert_post independently.
//
//  THE SOLUTION — two layers of deduplication:
//
//  Layer 1 (in-process):  static $seen array — free, instant, catches any
//                         duplicate calls within the same PHP request.
//
//  Layer 2 (cross-process): WordPress transient with a 15-second window key
//                           derived from post_id + floor(time()/15).
//                           Both Gutenberg requests arrive within milliseconds
//                           so they share the same window bucket. The first
//                           process sets the transient and fires Discord.
//                           The second process finds it already set and exits.
//
//  WHY wp_after_insert_post (not transition_post_status or save_post):
//  This hook fires AFTER all post data, taxonomy terms, and meta are saved,
//  so categories/tags are always correct when we read them.
//
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_after_insert_post', 'dwn_handle_post_saved', 10, 4 );

function dwn_handle_post_saved( int $post_id, WP_Post $post, bool $update, ?WP_Post $post_before ): void {

    // ── Basic guards ─────────────────────────────────────────────────────────
    if ( $post->post_type   !== 'post'    ) return;   // only standard posts
    if ( $post->post_status !== 'publish' ) return;   // only published posts
    if ( wp_is_post_revision( $post_id )  ) return;   // skip revisions
    if ( wp_is_post_autosave( $post_id )  ) return;   // skip autosaves

    // ── Layer 1: in-process dedup (free, no DB) ───────────────────────────────
    static $seen = [];
    if ( isset( $seen[ $post_id ] ) ) return;
    $seen[ $post_id ] = true;

    // ── Layer 2: cross-process dedup via transient ────────────────────────────
    // 15-second window is wide enough for any real server; narrow enough that a
    // genuine second save minutes later gets a fresh window and fires correctly.
    $window   = (int) floor( time() / 15 );
    $lock_key = 'dwn_lock_' . $post_id . '_' . $window;

    // get_transient returns false when not set; anything truthy means already fired
    if ( false !== get_transient( $lock_key ) ) return;

    // Set the lock BEFORE dispatching (prevents race condition on fast servers)
    set_transient( $lock_key, 1, 60 );   // 60s TTL — auto-cleaned by WordPress

    // ── Determine new vs update ───────────────────────────────────────────────
    // $post_before is null for brand-new posts.
    // For posts that were previously in draft/pending, post_before->post_status !== 'publish'.
    $is_new = ( $post_before === null || $post_before->post_status !== 'publish' );

    // ── Fire each matching webhook ────────────────────────────────────────────
    $webhooks = get_option( DWN_OPTION_KEY, [] );

    foreach ( $webhooks as $hook ) {
        if ( ! dwn_bool( $hook, 'enabled',    true  ) ) continue;
        if (   $is_new  && ! dwn_bool( $hook, 'on_publish', true  ) ) continue;
        if ( ! $is_new  && ! dwn_bool( $hook, 'on_update',  false ) ) continue;
        if ( ! dwn_matches_filter( $post, $hook ) ) continue;

        dwn_dispatch( $post, $hook, $is_new );
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  FILTER CHECK
// ═══════════════════════════════════════════════════════════════════════════

function dwn_matches_filter( WP_Post $post, array $hook ): bool {
    $filter = $hook['filter_type'] ?? 'all';

    if ( $filter === 'all' ) return true;

    if ( $filter === 'categories' && ! empty( $hook['categories'] ) ) {
        return (bool) array_intersect(
            wp_get_post_categories( $post->ID ),
            $hook['categories']
        );
    }

    if ( $filter === 'tags' && ! empty( $hook['tags'] ) ) {
        return (bool) array_intersect(
            wp_get_post_tags( $post->ID, [ 'fields' => 'ids' ] ),
            $hook['tags']
        );
    }

    if ( $filter === 'authors' && ! empty( $hook['authors'] ) ) {
        return in_array( (int) $post->post_author, array_map( 'intval', $hook['authors'] ) );
    }

    return true;   // filter set but no items selected → send to all
}

// ═══════════════════════════════════════════════════════════════════════════
//  BUILD + SEND DISCORD EMBED
// ═══════════════════════════════════════════════════════════════════════════

function dwn_dispatch( WP_Post $post, array $hook, bool $is_new ): void {

    // ── Post data ─────────────────────────────────────────────────────────────
    $title   = get_the_title( $post->ID );
    $url     = get_permalink( $post->ID );
    $site    = get_bloginfo( 'name' );
    $cats    = wp_get_post_categories( $post->ID, [ 'fields' => 'names' ] );
    $tags    = wp_get_post_tags( $post->ID,       [ 'fields' => 'names' ] );

    // Author
    $author_id    = (int) $post->post_author;
    $author_name  = get_the_author_meta( 'display_name', $author_id );
    $author_url   = get_author_posts_url( $author_id );
    $author_email = get_the_author_meta( 'user_email', $author_id );
    $avatar_hash  = md5( strtolower( trim( $author_email ) ) );
    $avatar_url   = 'https://www.gravatar.com/avatar/' . $avatar_hash . '?s=128&d=identicon&r=g';

    // Excerpt — prefer the explicit excerpt field, fall back to post content
    $raw_excerpt = get_post_field( 'post_excerpt', $post->ID );
    $excerpt     = wp_trim_words(
        wp_strip_all_tags( $raw_excerpt ?: $post->post_content ),
        55, '…'
    );

    // Featured image — try largest size first, force HTTPS
    $feat_url = '';
    $thumb_id = (int) get_post_thumbnail_id( $post->ID );
    if ( $thumb_id > 0 ) {
        foreach ( [ 'full', 'large', 'medium_large', 'medium' ] as $size ) {
            $img = wp_get_attachment_image_src( $thumb_id, $size );
            if ( ! empty( $img[0] ) ) {
                $feat_url = preg_replace( '#^http://#i', 'https://', $img[0] );
                break;
            }
        }
        if ( ! $feat_url ) {
            $raw = wp_get_attachment_url( $thumb_id );
            if ( $raw ) $feat_url = preg_replace( '#^http://#i', 'https://', $raw );
        }
    }

    // ── Display settings (all default true except on_update) ─────────────────
    $show_author     = dwn_bool( $hook, 'show_author',     true );
    $show_excerpt    = dwn_bool( $hook, 'show_excerpt',    true );
    $show_categories = dwn_bool( $hook, 'show_categories', true );
    $show_tags       = dwn_bool( $hook, 'show_tags',       true );
    $image_position  = $hook['image_position'] ?? 'large';

    // ── Embed color (hex → Discord int) ───────────────────────────────────────
    $hex_raw = ltrim(
        $is_new ? ( $hook['color_new'] ?? '#57F287' ) : ( $hook['color_update'] ?? '#5865F2' ),
        '#'
    );
    $color = ( strlen( $hex_raw ) === 6 ) ? hexdec( $hex_raw ) : ( $is_new ? 0x57F287 : 0x5865F2 );

    // ── Custom message text (above the embed) ─────────────────────────────────
    $template_vars = [
        '{title}'   => $title,
        '{url}'     => $url,
        '{author}'  => $author_name,
        '{excerpt}' => $excerpt,
        '{site}'    => $site,
        '{cats}'    => implode( ', ', $cats ),
        '{tags}'    => implode( ', ', $tags ),
        '{date}'    => get_the_date( '', $post->ID ),
    ];
    $message = ! empty( $hook['message'] )
        ? str_replace( array_keys( $template_vars ), array_values( $template_vars ), $hook['message'] )
        : '';

    // ── Embed fields ──────────────────────────────────────────────────────────
    $fields = [];
    if ( $show_categories && ! empty( $cats ) ) {
        $fields[] = [
            'name'   => '📂  Categories',
            'value'  => implode( '  ·  ', array_slice( $cats, 0, 6 ) ),
            'inline' => false,
        ];
    }
    if ( $show_tags && ! empty( $tags ) ) {
        $fields[] = [
            'name'   => '🏷️  Tags',
            'value'  => implode( '  ', array_map( fn( $t ) => '`' . $t . '`', array_slice( $tags, 0, 8 ) ) ),
            'inline' => false,
        ];
    }

    // ── Assemble embed ────────────────────────────────────────────────────────
    $embed = [
        'title'     => $title,
        'url'       => $url,
        'color'     => $color,
        'fields'    => $fields,
        'footer'    => [
            'text'     => ( $is_new ? '✨  New post' : '✏️  Updated' ) . ' on ' . $site,
            'icon_url' => 'https://www.gravatar.com/avatar/' . $avatar_hash . '?s=32&d=identicon&r=g',
        ],
        'timestamp' => gmdate( 'c', strtotime( $post->post_date_gmt ) ),
    ];

    if ( $show_author ) {
        $embed['author'] = [
            'name'     => 'Written by ' . $author_name,
            'url'      => $author_url,
            'icon_url' => $avatar_url,
        ];
    }
    if ( $show_excerpt ) {
        $embed['description'] = $excerpt;
    }
    if ( $feat_url ) {
        if ( $image_position === 'large' ) {
            $embed['image'] = [ 'url' => $feat_url ];
        } elseif ( $image_position === 'thumbnail' ) {
            $embed['thumbnail'] = [ 'url' => $feat_url ];
        } elseif ( $image_position === 'both' ) {
            $embed['image']     = [ 'url' => $feat_url ];
            $embed['thumbnail'] = [ 'url' => $feat_url ];
        }
        // 'none' — intentionally omitted
    }

    // ── Send ──────────────────────────────────────────────────────────────────
    dwn_send( $hook['url'], [
        'username'   => ! empty( $hook['username'] ) ? trim( $hook['username'] ) : $site,
        'avatar_url' => $hook['avatar_url'] ?? '',
        'content'    => $message,
        'embeds'     => [ $embed ],
    ] );
}

// ═══════════════════════════════════════════════════════════════════════════
//  HTTP
// ═══════════════════════════════════════════════════════════════════════════

function dwn_send( string $url, array $payload ) {
    $response = wp_remote_post( $url, [
        'headers'     => [ 'Content-Type' => 'application/json' ],
        'body'        => wp_json_encode( $payload ),
        'timeout'     => 10,
        'data_format' => 'body',
    ] );

    if ( is_wp_error( $response ) ) return $response;

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error(
            'dwn_http',
            sprintf( 'Discord returned HTTP %d: %s', $code, wp_remote_retrieve_body( $response ) )
        );
    }

    return true;
}
