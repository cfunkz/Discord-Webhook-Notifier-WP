/* Discord Webhook Notifier — Admin JS v2.0 */
(function ($) {
    'use strict';

    // ─── Toast ──────────────────────────────────────────────────────────────
    function toast(msg, type = 'success', duration = 3800) {
        const icons = { success: '✅', error: '❌', info: 'ℹ️' };
        const $t = $('<div class="dwn-toast ' + type + '">' + icons[type] + ' ' + msg + '</div>');
        $('body').append($t);
        setTimeout(() => $t.fadeOut(300, () => $t.remove()), duration);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────
    function getCardData($card) {
        const filterType = $card.find('.dwn-field-filter:checked').val() || 'all';
        const cats    = $card.find('.dwn-field-categories:checked').map((_, el) => $(el).val()).get();
        const tags    = $card.find('.dwn-field-tags:checked').map((_, el) => $(el).val()).get();
        const authors = $card.find('.dwn-field-authors:checked').map((_, el) => $(el).val()).get();

        // All booleans sent as explicit '1' or '0' strings — never empty — so PHP
        // can distinguish "unchecked" from "key not present" without ambiguity.
        const cb = (sel) => $card.find(sel).is(':checked') ? '1' : '0';
        const data = {
            id:               $card.find('.dwn-field-id').val(),
            name:             $card.find('.dwn-field-name').val().trim(),
            url:              $card.find('.dwn-field-url').val().trim(),
            username:         $card.find('.dwn-field-username').val().trim(),
            avatar_url:       $card.find('.dwn-field-avatar').val().trim(),
            message:          $card.find('.dwn-field-message').val().trim(),
            filter_type:      filterType,
            on_publish:       cb('.dwn-field-on-publish'),
            on_update:        cb('.dwn-field-on-update'),
            enabled:          cb('.js-toggle-enabled'),
            show_author:      cb('.dwn-field-show-author'),
            show_excerpt:     cb('.dwn-field-show-excerpt'),
            show_categories:  cb('.dwn-field-show-categories'),
            show_tags:        cb('.dwn-field-show-tags'),
            image_position:   $card.find('.dwn-field-image-position:checked').val() || 'large',
            color_new:        $card.find('.dwn-field-color-new').val().trim(),
            color_update:     $card.find('.dwn-field-color-update').val().trim(),
        };

        if (cats.length)    data.categories = cats;
        if (tags.length)    data.tags        = tags;
        if (authors.length) data.authors     = authors;

        return data;
    }

    function validateCard($card) {
        let valid = true;

        const $name = $card.find('.dwn-field-name');
        if (!$name.val().trim()) {
            $name.addClass('error').attr('placeholder', '⚠ Name is required');
            valid = false;
        } else { $name.removeClass('error'); }

        const $url = $card.find('.dwn-field-url');
        const url  = $url.val().trim();
        if (!url) {
            $url.addClass('error');
            valid = false;
        } else if (url.indexOf('discord.com/api/webhooks') === -1 && url.indexOf('discordapp.com/api/webhooks') === -1) {
            $url.addClass('error');
            toast('URL must be a Discord webhook URL (discord.com/api/webhooks/...)', 'error');
            valid = false;
        } else { $url.removeClass('error'); }

        return valid;
    }

    function markDirty($card) {
        $card.attr('data-saved', '0');
        if (!$card.find('.dwn-badge--unsaved').length) {
            $card.find('.dwn-card-name').after('<span class="dwn-badge dwn-badge--new dwn-badge--unsaved" style="margin-left:8px">Unsaved</span>');
        }
    }
    function markClean($card, name) {
        $card.attr('data-saved', '1');
        $card.find('.dwn-badge--unsaved').remove();
        if (name) $card.find('.dwn-card-name').text(name);
    }

    // ─── Init a card ────────────────────────────────────────────────────────
    function initCard($card) {

        // Expand / collapse
        $card.find('.js-toggle-card').on('click', function (e) {
            if ($(e.target).closest('button, input, label, a').length) return;
            $card.toggleClass('dwn-card--open');
        });

        // Auto-expand new (unsaved) cards
        if ($card.data('saved') === 0 || $card.attr('data-saved') === '0') {
            $card.addClass('dwn-card--open');
        }

        // Live name preview in header
        $card.find('.dwn-field-name').on('input', function () {
            const v = $(this).val().trim() || 'Unnamed Webhook';
            $card.find('.dwn-card-name').text(v);
            markDirty($card);
        });

        // Live URL preview
        $card.find('.dwn-field-url').on('input', function () {
            const v = $(this).val().trim();
            $card.find('.dwn-card-url').text(v ? v.substring(0, 72) + (v.length > 72 ? '…' : '') : 'No URL set');
            markDirty($card);
        });

        // Any field change → dirty
        $card.find('.dwn-input, .dwn-field-on-publish, .dwn-field-on-update, .dwn-field-categories, .dwn-field-tags, .dwn-field-authors, .dwn-field-filter, .dwn-field-avatar, .dwn-field-username, .dwn-field-message, .dwn-field-show-author, .dwn-field-show-excerpt, .dwn-field-show-categories, .dwn-field-show-tags').on('change input', function () {
            markDirty($card);
        });

        // Toggle enabled (instant AJAX, no need to save)
        $card.find('.js-toggle-enabled').on('change', function () {
            const enabled  = $(this).is(':checked');
            const id       = $(this).data('id') || $card.find('.dwn-field-id').val();
            $card.find('.dwn-toggle-label').text(enabled ? 'Enabled' : 'Disabled');
            $card.toggleClass('dwn-card--disabled', !enabled);

            // Only save the toggle if the card is already saved (has an ID)
            if (id) {
                $.post(DWN.ajax_url, { action: 'dwn_toggle_webhook', nonce: DWN.nonce, id, enabled: enabled ? '1' : '0' })
                    .fail(() => toast('Could not update webhook status.', 'error'));
            }
        });

        // Filter radio tabs
        $card.find('.dwn-field-filter').on('change', function () {
            const val = $(this).val();
            $card.find('.dwn-radio').removeClass('active');
            $(this).closest('.dwn-radio').addClass('active');
            $card.find('.dwn-filter-panel').removeClass('dwn-filter-panel--active');
            $card.find('.dwn-filter-panel[data-filter="' + val + '"]').addClass('dwn-filter-panel--active');
            markDirty($card);
        });

        // Switch rows — clicking the div toggles the inner checkbox
        // (outer element is <div> not <label> to avoid nested-label double-fire)
        $card.find('.js-switch-row').on('click', function (e) {
            // If user clicked directly on the label/input, let browser handle it normally
            if ($(e.target).closest('label, input').length) return;
            const $cb = $(this).find('input[type=checkbox]');
            $cb.prop('checked', !$cb.prop('checked')).trigger('change');
        });

        // Image position radio
        $card.find('.dwn-field-image-position').on('change', function () {
            $card.find('.dwn-img-opt').removeClass('active');
            $(this).closest('.dwn-img-opt').addClass('active');
            markDirty($card);
        });

        // Color pickers — sync color wheel ↔ text input
        $card.find('.dwn-field-color-new, .dwn-field-color-update').on('input change', function () {
            const val = $(this).val();
            const $row = $(this).closest('.dwn-color-wrap');
            $row.find('.dwn-color-text').val(val);
            $row.find('.dwn-color-preview').css('background', val);
            $row.find('input[type=color]').val(val);
            markDirty($card);
        });
        $card.find('.dwn-color-text').on('input', function () {
            const val = $(this).val().trim();
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                const $row = $(this).closest('.dwn-color-wrap');
                $row.find('.dwn-color-preview').css('background', val);
                $row.find('input[type=color]').val(val);
                // Update the right hidden field
                const isNew = $row.find('.dwn-field-color-new').length > 0;
                if (isNew) $card.find('.dwn-field-color-new').val(val);
                else $card.find('.dwn-field-color-update').val(val);
            }
            markDirty($card);
        });

        // Click a variable chip to insert into message textarea
        $card.find('.dwn-var').on('click', function () {
            const $ta = $card.find('.dwn-field-message');
            const v   = $(this).text();
            const ta  = $ta[0];
            const pos = ta.selectionStart;
            const before = ta.value.substring(0, pos);
            const after  = ta.value.substring(ta.selectionEnd);
            ta.value = before + v + after;
            ta.selectionStart = ta.selectionEnd = pos + v.length;
            ta.focus();
            markDirty($card);
        });

        // ── Save ──────────────────────────────────────────────────────────
        $card.find('.js-save-btn').on('click', function () {
            if (!validateCard($card)) return;

            const $btn = $(this);
            $btn.addClass('saving').text('Saving…')
               .css({ opacity: '0.65', pointerEvents: 'none' });

            const data = getCardData($card);

            $.post(DWN.ajax_url, {
                action:  'dwn_save_webhook',
                nonce:   DWN.nonce,
                webhook: data,
            })
            .done(function (res) {
                if (res.success) {
                    // Update stored ID if new
                    if (res.data && res.data.id) {
                        $card.find('.dwn-field-id').val(res.data.id);
                        $card.attr('data-id', res.data.id);
                        $card.find('.js-save-btn').data('id', res.data.id);
                        $card.find('.js-delete-btn').data('id', res.data.id);
                        $card.find('.js-toggle-enabled').data('id', res.data.id);
                    }
                    markClean($card, data.name || 'Unnamed Webhook');
                    $card.removeClass('dwn-card--new');
                    $btn.removeClass('saving').addClass('saved')
                        .text('✓ Saved')
                        .css({ background: '#16a34a', borderColor: '#15803d', color: '#fff', opacity: '1', pointerEvents: 'none' });
                    setTimeout(() => {
                        $btn.removeClass('saved')
                            .text('Save Webhook')
                            .css({ background: '', borderColor: '', color: '', opacity: '', pointerEvents: '' });
                    }, 2500);
                    toast('Webhook saved!', 'success');
                } else {
                    toast(res.data || 'Save failed.', 'error');
                    $btn.removeClass('saving saved').text('Save Webhook')
                        .css({ background: '', borderColor: '', color: '', opacity: '', pointerEvents: '' });
                }
            })
            .fail(function () {
                toast('Request failed. Check your connection.', 'error');
                $btn.removeClass('saving saved').text('Save Webhook')
                    .css({ background: '', borderColor: '', color: '', opacity: '', pointerEvents: '' });
            });
        });

        // ── Delete ────────────────────────────────────────────────────────
        $card.find('.js-delete-btn').on('click', function () {
            const id = $(this).data('id') || $card.find('.dwn-field-id').val();
            const name = $card.find('.dwn-card-name').text();

            if (!confirm('Delete "' + name + '"?\n\nThis cannot be undone.')) return;

            // If never saved, just remove from DOM
            if (!id || $card.attr('data-saved') === '0') {
                $card.slideUp(200, () => {
                    $card.remove();
                    checkEmpty();
                });
                return;
            }

            $.post(DWN.ajax_url, { action: 'dwn_delete_webhook', nonce: DWN.nonce, id })
                .done(function (res) {
                    if (res.success) {
                        $card.slideUp(200, () => { $card.remove(); checkEmpty(); });
                        toast('Webhook deleted.', 'info');
                    } else {
                        toast(res.data || 'Delete failed.', 'error');
                    }
                })
                .fail(() => toast('Request failed.', 'error'));
        });

        // ── Test Webhook ──────────────────────────────────────────────────
        $card.find('.js-test-btn').on('click', function () {
            const url  = $card.find('.dwn-field-url').val().trim();
            if (!url) { toast('Enter a webhook URL first.', 'error'); return; }

            const $btn = $(this);
            const orig = $btn.html();
            $btn.html('Sending…').prop('disabled', true);

            $.post(DWN.ajax_url, { action: 'dwn_test_webhook', nonce: DWN.nonce, url })
                .done(function (res) {
                    if (res.success) toast('Test sent! Check your Discord channel.', 'success');
                    else             toast(res.data || 'Test failed.', 'error');
                })
                .fail(() => toast('Request failed.', 'error'))
                .always(() => { $btn.html(orig).prop('disabled', false); });
        });

        // ── Discard Changes ───────────────────────────────────────────────
        $card.find('.js-discard-btn').on('click', function () {
            if ($card.attr('data-saved') === '0') {
                $card.slideUp(200, () => { $card.remove(); checkEmpty(); });
            } else {
                toast('No unsaved changes.', 'info');
            }
        });
    }

    // ─── Add New Webhook ────────────────────────────────────────────────────
    $('#dwn-add-btn').on('click', function () {
        const newId = 'dwn_' + Date.now();

        // Clone the hidden template card
        const $tpl = $('#dwn-card-template').find('.dwn-card').clone(false);
        $tpl.attr('data-id', newId).attr('data-saved', '0');
        $tpl.find('.dwn-field-id').val(newId);
        $tpl.find('.js-save-btn').data('id', newId);
        $tpl.find('.js-delete-btn').data('id', newId);
        $tpl.find('.js-toggle-enabled').data('id', newId);

        // Fix radio name uniqueness
        $tpl.find('.dwn-field-filter').each(function () {
            $(this).attr('name', 'filter_type_' + newId);
        });

        $('#dwn-empty').hide();
        $('#dwn-list').append($tpl);
        initCard($tpl);

        $('html, body').animate({ scrollTop: $tpl.offset().top - 100 }, 300);
    });

    // ─── Check empty state ───────────────────────────────────────────────────
    function checkEmpty() {
        if ($('#dwn-list .dwn-card').length === 0) {
            $('#dwn-empty').show();
        }
    }

    // ─── Init existing cards ─────────────────────────────────────────────────
    $('#dwn-list .dwn-card').each(function () { initCard($(this)); });

    // ─── Warn on leave with unsaved changes ──────────────────────────────────
    window.addEventListener('beforeunload', function (e) {
        const hasUnsaved = $('#dwn-list .dwn-card[data-saved="0"]').length > 0;
        if (hasUnsaved) {
            e.preventDefault();
            e.returnValue = 'You have unsaved webhook changes. Leave anyway?';
        }
    });

    // ─── Spin animation ──────────────────────────────────────────────────────
    const spinStyle = document.createElement('style');
    spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(spinStyle);

})(jQuery);
