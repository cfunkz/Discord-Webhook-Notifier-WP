<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap dwn-wrap">

    <div class="dwn-header">
        <div class="dwn-header-left">
            <svg class="dwn-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 127.14 96.36" width="40" height="40"><path fill="#fff" d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.25,60,73.25,53s5-12.74,11.44-12.74S96.23,46,96.12,53,91.08,65.69,84.69,65.69Z"/></svg>
            <div>
                <h1>Discord Webhook Notifier</h1>
                <p>Send post notifications to Discord with full control over the embed layout.</p>
            </div>
        </div>
        <button type="button" id="dwn-add-btn" class="dwn-btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Webhook
        </button>
    </div>

    <div id="dwn-notice-area"></div>

    <div id="dwn-list">
        <?php if ( empty( $webhooks ) ) : ?>
            <div class="dwn-empty" id="dwn-empty">
                <div class="dwn-empty-icon">🔗</div>
                <h3>No webhooks yet</h3>
                <p>Click <strong>Add Webhook</strong> to connect your first Discord channel.</p>
            </div>
        <?php else : ?>
            <?php foreach ( $webhooks as $hook ) : ?>
                <?php dwn_render_webhook_card( $hook, $categories, $tags, $authors ); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Hidden template card for JS cloning -->
    <div id="dwn-card-template" style="display:none" aria-hidden="true">
        <?php
        dwn_render_webhook_card( [
            'id' => '__TEMPLATE__', 'name' => '', 'url' => '', 'username' => '',
            'avatar_url' => '', 'message' => '', 'filter_type' => 'all',
            'categories' => [], 'tags' => [], 'authors' => [],
            'on_publish' => true, 'on_update' => false, 'enabled' => true,
            'show_author' => true, 'show_excerpt' => true,
            'show_categories' => true, 'show_tags' => true,
            'image_position' => 'large',
            'color_new' => '#57F287', 'color_update' => '#5865F2',
        ], $categories, $tags, $authors, true );
        ?>
    </div>

    <div class="dwn-guide">
        <h3>📖 Setup &amp; Template Variables</h3>
        <div class="dwn-guide-cols">
            <div>
                <h4>How to get a Discord Webhook URL</h4>
                <ol>
                    <li>Open Discord → your server → <strong>Server Settings</strong></li>
                    <li>Go to <strong>Integrations → Webhooks</strong></li>
                    <li>Click <strong>New Webhook</strong> and pick a channel</li>
                    <li>Click <strong>Copy Webhook URL</strong> and paste it above</li>
                </ol>
                <p style="margin-top:10px;font-size:.82em;color:#6b7280;">
                    💡 <strong>Bot Display Name</strong> — Discord may require "Manage Webhook" permission to override names on some servers. Check your server's integration settings if the name doesn't change.
                </p>
            </div>
            <div>
                <h4>Custom message template variables</h4>
                <table class="dwn-vars-table">
                    <tr><td><code>{title}</code></td><td>Post title</td></tr>
                    <tr><td><code>{url}</code></td><td>Post permalink</td></tr>
                    <tr><td><code>{author}</code></td><td>Author display name</td></tr>
                    <tr><td><code>{excerpt}</code></td><td>Post excerpt (55 words)</td></tr>
                    <tr><td><code>{cats}</code></td><td>Category list</td></tr>
                    <tr><td><code>{tags}</code></td><td>Tag list</td></tr>
                    <tr><td><code>{site}</code></td><td>Site name</td></tr>
                    <tr><td><code>{date}</code></td><td>Published date</td></tr>
                </table>
                <p style="margin-top:8px;font-size:.8em;color:#6b7280;">Example: <code>@here 🆕 {title} by {author} → {url}</code></p>
            </div>
        </div>
    </div>

</div>

<?php
function dwn_render_webhook_card( $hook, $categories, $tags, $authors, $is_new = false ) {
    $id           = esc_attr( $hook['id'] ?? '' );
    $name         = esc_attr( $hook['name'] ?? '' );
    $url          = esc_attr( $hook['url'] ?? '' );
    $username     = esc_attr( $hook['username'] ?? '' );
    $avatar_url   = esc_attr( $hook['avatar_url'] ?? '' );
    $message      = esc_textarea( $hook['message'] ?? '' );
    $filter_type  = $hook['filter_type'] ?? 'all';
    $sel_cats     = array_map( 'intval', (array)( $hook['categories'] ?? [] ) );
    $sel_tags     = array_map( 'intval', (array)( $hook['tags'] ?? [] ) );
    $sel_authors  = array_map( 'intval', (array)( $hook['authors'] ?? [] ) );
    $on_publish   = ! empty( $hook['on_publish'] );
    $on_update    = ! empty( $hook['on_update'] );
    $enabled      = ! empty( $hook['enabled'] );
    $display_name = $hook['name'] ?: 'Unnamed Webhook';

    // Display options
    $show_author     = isset( $hook['show_author'] )     ? (bool)$hook['show_author']     : true;
    $show_excerpt    = isset( $hook['show_excerpt'] )    ? (bool)$hook['show_excerpt']    : true;
    $show_categories = isset( $hook['show_categories'] ) ? (bool)$hook['show_categories'] : true;
    $show_tags       = isset( $hook['show_tags'] )       ? (bool)$hook['show_tags']       : true;
    $image_position  = $hook['image_position'] ?? 'large';
    $color_new       = esc_attr( $hook['color_new']    ?? '#57F287' );
    $color_update    = esc_attr( $hook['color_update'] ?? '#5865F2' );
    ?>
    <div class="dwn-card <?php echo $enabled ? '' : 'dwn-card--disabled'; ?> <?php echo $is_new ? 'dwn-card--new' : ''; ?>"
         data-id="<?php echo $id; ?>" data-saved="<?php echo $is_new ? '0' : '1'; ?>">

        <!-- Header -->
        <div class="dwn-card-header js-toggle-card">
            <div class="dwn-card-header-left">
                <span class="dwn-card-chevron">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </span>
                <div class="dwn-card-info">
                    <span class="dwn-card-name"><?php echo esc_html( $display_name ); ?></span>
                    <span class="dwn-card-url"><?php echo $url ? esc_html( substr( $url, 0, 72 ) . ( strlen($url) > 72 ? '…' : '' ) ) : '<em>No URL set</em>'; ?></span>
                </div>
                <?php if ( $is_new ) : ?><span class="dwn-badge dwn-badge--unsaved">Unsaved</span><?php endif; ?>
            </div>
            <div class="dwn-card-header-right" onclick="event.stopPropagation()">
                <label class="dwn-toggle">
                    <input type="checkbox" class="js-toggle-enabled" data-id="<?php echo $id; ?>" <?php checked( $enabled ); ?>>
                    <span class="dwn-toggle-track"><span class="dwn-toggle-thumb"></span></span>
                    <span class="dwn-toggle-label"><?php echo $enabled ? 'Enabled' : 'Disabled'; ?></span>
                </label>
            </div>
        </div>

        <!-- Body -->
        <div class="dwn-card-body">
            <input type="hidden" class="dwn-field-id" value="<?php echo $id; ?>">

            <!-- ── Section 1: Connection ── -->
            <div class="dwn-section">
                <h4 class="dwn-section-title">🔗 Connection</h4>
                <div class="dwn-grid dwn-grid-2">

                    <div class="dwn-field">
                        <label>Webhook Name <span class="dwn-req">*</span></label>
                        <input type="text" class="dwn-input dwn-field-name" value="<?php echo $name; ?>" placeholder="e.g. #announcements">
                        <span class="dwn-field-hint">Label shown in this settings page.</span>
                    </div>

                    <div class="dwn-field">
                        <label>Discord Webhook URL <span class="dwn-req">*</span></label>
                        <div class="dwn-input-group">
                            <input type="url" class="dwn-input dwn-field-url" value="<?php echo $url; ?>" placeholder="https://discord.com/api/webhooks/...">
                            <button type="button" class="dwn-btn-ghost js-test-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg> Test
                            </button>
                        </div>
                        <span class="dwn-field-hint">Server Settings → Integrations → Webhooks → Copy URL.</span>
                    </div>

                    <div class="dwn-field">
                        <label>Bot Display Name</label>
                        <input type="text" class="dwn-input dwn-field-username" value="<?php echo $username; ?>" placeholder="<?php echo esc_attr( get_bloginfo('name') ); ?>">
                        <span class="dwn-field-hint">Name shown next to the bot's avatar in Discord. Leave blank to use site name.</span>
                    </div>

                    <div class="dwn-field">
                        <label>Bot Avatar URL</label>
                        <input type="url" class="dwn-input dwn-field-avatar" value="<?php echo $avatar_url; ?>" placeholder="https://example.com/logo.png">
                        <span class="dwn-field-hint">Square image for the bot's icon. Leave blank to use the webhook's default.</span>
                    </div>

                </div>
            </div>

            <!-- ── Section 2: Message ── -->
            <div class="dwn-section">
                <h4 class="dwn-section-title">💬 Message Above Embed</h4>
                <div class="dwn-field">
                    <textarea class="dwn-input dwn-field-message" rows="2" placeholder="@everyone — New post: {title} → {url}"><?php echo $message; ?></textarea>
                    <span class="dwn-field-hint">Plain text or ping sent above the embed card. Supports:
                        <?php foreach ( ['{title}','{url}','{author}','{excerpt}','{cats}','{tags}','{site}','{date}'] as $v ) : ?>
                            <code class="dwn-var" title="Click to insert"><?php echo $v; ?></code>
                        <?php endforeach; ?>
                    </span>
                </div>
            </div>

            <!-- ── Section 3: Embed Layout ── -->
            <div class="dwn-section">
                <h4 class="dwn-section-title">🎨 Embed Layout &amp; Content</h4>

                <div class="dwn-layout-grid">

                    <!-- Left: Toggles — NOTE: outer element is <div> not <label>
                         to avoid nested-label double-click bug in all browsers -->
                    <div class="dwn-layout-toggles">
                        <p class="dwn-sublabel">Show in embed</p>

                        <div class="dwn-switch-row js-switch-row">
                            <span class="dwn-switch-label">
                                <strong>Author row</strong>
                                <span>Gravatar + &ldquo;Written by&hellip;&rdquo; at top</span>
                            </span>
                            <label class="dwn-toggle dwn-toggle--sm">
                                <input type="checkbox" class="dwn-field-show-author" <?php checked( $show_author ); ?>>
                                <span class="dwn-toggle-track"><span class="dwn-toggle-thumb"></span></span>
                            </label>
                        </div>

                        <div class="dwn-switch-row js-switch-row">
                            <span class="dwn-switch-label">
                                <strong>Excerpt</strong>
                                <span>Post description / first ~55 words</span>
                            </span>
                            <label class="dwn-toggle dwn-toggle--sm">
                                <input type="checkbox" class="dwn-field-show-excerpt" <?php checked( $show_excerpt ); ?>>
                                <span class="dwn-toggle-track"><span class="dwn-toggle-thumb"></span></span>
                            </label>
                        </div>

                        <div class="dwn-switch-row js-switch-row">
                            <span class="dwn-switch-label">
                                <strong>Categories</strong>
                                <span>📂 Categories field below excerpt</span>
                            </span>
                            <label class="dwn-toggle dwn-toggle--sm">
                                <input type="checkbox" class="dwn-field-show-categories" <?php checked( $show_categories ); ?>>
                                <span class="dwn-toggle-track"><span class="dwn-toggle-thumb"></span></span>
                            </label>
                        </div>

                        <div class="dwn-switch-row js-switch-row">
                            <span class="dwn-switch-label">
                                <strong>Tags</strong>
                                <span>🏷️ Tag chips below categories</span>
                            </span>
                            <label class="dwn-toggle dwn-toggle--sm">
                                <input type="checkbox" class="dwn-field-show-tags" <?php checked( $show_tags ); ?>>
                                <span class="dwn-toggle-track"><span class="dwn-toggle-thumb"></span></span>
                            </label>
                        </div>
                    </div>

                    <!-- Right: Image position + Colors -->
                    <div class="dwn-layout-right">
                        <div class="dwn-field">
                            <p class="dwn-sublabel">Featured image placement</p>
                            <div class="dwn-img-options">
                                <?php
                                $img_opts = [
                                    'large'     => [ '🖼️', 'Large', 'Full-width below excerpt' ],
                                    'thumbnail' => [ '⬜', 'Thumbnail', 'Small square top-right' ],
                                    'both'      => [ '🖼️⬜', 'Both', 'Large image + thumbnail' ],
                                    'none'      => [ '🚫', 'None', 'No image in embed' ],
                                ];
                                foreach ( $img_opts as $val => [$icon, $label, $desc] ) : ?>
                                    <label class="dwn-img-opt <?php echo $image_position === $val ? 'active' : ''; ?>">
                                        <input type="radio" class="dwn-field-image-position" name="img_pos_<?php echo $id; ?>" value="<?php echo $val; ?>" <?php checked( $image_position, $val ); ?>>
                                        <span class="dwn-img-opt-icon"><?php echo $icon; ?></span>
                                        <span class="dwn-img-opt-label"><?php echo $label; ?></span>
                                        <span class="dwn-img-opt-desc"><?php echo $desc; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="dwn-colors">
                            <div class="dwn-field">
                                <label>New post color</label>
                                <div class="dwn-color-wrap">
                                    <input type="color" class="dwn-color-picker dwn-field-color-new" value="<?php echo $color_new; ?>">
                                    <input type="text"  class="dwn-input dwn-color-text" value="<?php echo $color_new; ?>" maxlength="7" placeholder="#57F287">
                                    <span class="dwn-color-preview" style="background:<?php echo $color_new; ?>"></span>
                                </div>
                            </div>
                            <div class="dwn-field">
                                <label>Updated post color</label>
                                <div class="dwn-color-wrap">
                                    <input type="color" class="dwn-color-picker dwn-field-color-update" value="<?php echo $color_update; ?>">
                                    <input type="text"  class="dwn-input dwn-color-text" value="<?php echo $color_update; ?>" maxlength="7" placeholder="#5865F2">
                                    <span class="dwn-color-preview" style="background:<?php echo $color_update; ?>"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── Section 4: Triggers ── -->
            <div class="dwn-section">
                <h4 class="dwn-section-title">🔔 Trigger Events</h4>
                <div class="dwn-checkboxes">
                    <label class="dwn-checkbox">
                        <input type="checkbox" class="dwn-field-on-publish" <?php checked( $on_publish ); ?>>
                        <span class="dwn-checkbox-box"></span>
                        <span>✨ New post published</span>
                    </label>
                    <label class="dwn-checkbox">
                        <input type="checkbox" class="dwn-field-on-update" <?php checked( $on_update ); ?>>
                        <span class="dwn-checkbox-box"></span>
                        <span>✏️ Existing post updated</span>
                    </label>
                </div>
            </div>

            <!-- ── Section 5: Filters ── -->
            <div class="dwn-section">
                <h4 class="dwn-section-title">🔍 Post Filter</h4>
                <div class="dwn-filter-selector">
                    <?php
                    $filter_opts = [ 'all' => 'All posts', 'categories' => 'By category', 'tags' => 'By tag', 'authors' => 'By author' ];
                    foreach ( $filter_opts as $val => $label ) : ?>
                        <label class="dwn-radio <?php echo $filter_type === $val ? 'active' : ''; ?>">
                            <input type="radio" class="dwn-field-filter" name="filter_type_<?php echo $id; ?>" value="<?php echo $val; ?>" <?php checked( $filter_type, $val ); ?>>
                            <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="dwn-filter-panel <?php echo $filter_type === 'categories' ? 'dwn-filter-panel--active' : ''; ?>" data-filter="categories">
                    <?php if ( $categories ) : ?>
                        <div class="dwn-checklist">
                            <?php foreach ( $categories as $cat ) : ?>
                                <label class="dwn-checklist-item">
                                    <input type="checkbox" class="dwn-field-categories" value="<?php echo $cat->term_id; ?>" <?php checked( in_array($cat->term_id, $sel_cats) ); ?>>
                                    <span><?php echo esc_html($cat->name); ?></span>
                                    <span class="dwn-count"><?php echo $cat->count; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?><p class="dwn-empty-filter">No categories found.</p><?php endif; ?>
                </div>

                <div class="dwn-filter-panel <?php echo $filter_type === 'tags' ? 'dwn-filter-panel--active' : ''; ?>" data-filter="tags">
                    <?php if ( $tags ) : ?>
                        <div class="dwn-checklist">
                            <?php foreach ( $tags as $tag ) : ?>
                                <label class="dwn-checklist-item">
                                    <input type="checkbox" class="dwn-field-tags" value="<?php echo $tag->term_id; ?>" <?php checked( in_array($tag->term_id, $sel_tags) ); ?>>
                                    <span><?php echo esc_html($tag->name); ?></span>
                                    <span class="dwn-count"><?php echo $tag->count; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?><p class="dwn-empty-filter">No tags found.</p><?php endif; ?>
                </div>

                <div class="dwn-filter-panel <?php echo $filter_type === 'authors' ? 'dwn-filter-panel--active' : ''; ?>" data-filter="authors">
                    <?php if ( $authors ) : ?>
                        <div class="dwn-checklist">
                            <?php foreach ( $authors as $author ) : ?>
                                <label class="dwn-checklist-item">
                                    <input type="checkbox" class="dwn-field-authors" value="<?php echo $author->ID; ?>" <?php checked( in_array($author->ID, $sel_authors) ); ?>>
                                    <span><?php echo esc_html($author->display_name); ?></span>
                                    <span class="dwn-count"><?php echo esc_html($author->user_email); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?><p class="dwn-empty-filter">No authors found.</p><?php endif; ?>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="dwn-card-footer">
                <button type="button" class="dwn-btn-danger js-delete-btn" data-id="<?php echo $id; ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    Delete
                </button>
                <div class="dwn-card-footer-right">
                    <button type="button" class="dwn-btn-ghost js-discard-btn">Discard</button>
                    <button type="button" class="dwn-btn-primary js-save-btn" data-id="<?php echo $id; ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Webhook
                    </button>
                </div>
            </div>

        </div>
    </div>
    <?php
}
?>
