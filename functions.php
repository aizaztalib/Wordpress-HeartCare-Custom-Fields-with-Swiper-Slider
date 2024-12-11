<?php
// Add Meta Box
function add_custom_fields_meta_box() {
    add_meta_box(
        'custom_fields_meta_box',
        'Custom Fields',
        'custom_fields_meta_box_callback',
        'heart-care',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_fields_meta_box');

// Render Meta Box
function custom_fields_meta_box_callback($post) {
    wp_nonce_field('save_custom_fields_meta', 'custom_fields_nonce');

    // Retrieve saved data
    $custom_fields = get_post_meta($post->ID, '_custom_fields', true);
    if (empty($custom_fields)) {
        $custom_fields = [['image' => '', 'detailed_text' => '', 'summary_text' => '', 'url' => '', 'url_title' => '']];
    }

    echo '<div id="custom-fields-container">';
    foreach ($custom_fields as $index => $field) {
        echo '<div class="custom-field-group">';
        // Image field
        echo '<label><strong>Upload Image:</strong></label><br>';
        echo '<input type="text" id="image_' . $index . '" name="custom_fields[' . $index . '][image]" value="' . esc_attr($field['image']) . '" style="width: 80%;" />';
        echo '<button type="button" class="button upload-image-button">Upload Image</button>';
        echo '<button type="button" class="button button-secondary remove-image-button">Remove Image</button>';
        echo '<div class="image-preview" style="margin-top: 10px;">';
        if (!empty($field['image'])) {
            echo '<img src="' . esc_url($field['image']) . '" style="max-width: 150px; height: auto; border-radius: 5px;" />';
        }
        echo '</div>';
        // Detailed text
        echo '<label for="detailed_text_' . $index . '"><strong>Detailed Text:</strong></label><br>';
        echo '<textarea id="detailed_text_' . $index . '" name="custom_fields[' . $index . '][detailed_text]" rows="5" style="width: 100%;">' . esc_textarea($field['detailed_text']) . '</textarea>';
        // Summary text
        echo '<label for="summary_text_' . $index . '"><strong>Summary Text:</strong></label><br>';
        echo '<textarea id="summary_text_' . $index . '" name="custom_fields[' . $index . '][summary_text]" rows="3" style="width: 100%;">' . esc_textarea($field['summary_text']) . '</textarea>';
        // URL field
        echo '<label for="url_' . $index . '"><strong>URL Link:</strong></label><br>';
        echo '<input type="url" id="url_' . $index . '" name="custom_fields[' . $index . '][url]" value="' . esc_url($field['url']) . '" style="width: 100%;" />';
        // URL title field
        echo '<label for="url_title_' . $index . '"><strong>URL Title:</strong></label><br>';
        echo '<input type="text" id="url_title_' . $index . '" name="custom_fields[' . $index . '][url_title]" value="' . esc_attr($field['url_title']) . '" style="width: 100%;" />';
        // Remove button
        echo '<button type="button" class="button remove-field-group">Remove Group</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="button" id="add-new-field-group" class="button">Add New Group</button>';
}

// Save Meta Data
function save_custom_fields_meta_data($post_id) {
    if (!isset($_POST['custom_fields_nonce']) || !wp_verify_nonce($_POST['custom_fields_nonce'], 'save_custom_fields_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['custom_fields'])) {
        $custom_fields = array_map(function ($field) {
            return [
                'image' => sanitize_text_field($field['image']),
                'detailed_text' => sanitize_textarea_field($field['detailed_text']),
                'summary_text' => sanitize_textarea_field($field['summary_text']),
                'url' => filter_var($field['url'], FILTER_VALIDATE_URL) ? esc_url_raw($field['url']) : '',
                'url_title' => sanitize_text_field($field['url_title']),
            ];
        }, $_POST['custom_fields']);
        update_post_meta($post_id, '_custom_fields', $custom_fields);
    } else {
        delete_post_meta($post_id, '_custom_fields');
    }
}
add_action('save_post', 'save_custom_fields_meta_data');

// Display Custom Fields in Swiper Slider
function display_custom_fields_in_content($content) {
    if (is_singular('heart-care') && in_the_loop() && is_main_query()) {
        $custom_fields = get_post_meta(get_the_ID(), '_custom_fields', true);
        if (!empty($custom_fields)) {
            $content .= '<div class="swiper-container-wrapper" style="max-width: 800px; margin: 0 auto; overflow: hidden;"><div class="swiper-container"><div class="swiper-wrapper">';
            foreach ($custom_fields as $field) {
                $content .= '<div class="swiper-slide">';
                $content .= '<div style="display: flex; align-items: center; gap: 20px;">';
                $content .= '<div style="flex: 1;">';
                $content .= !empty($field['detailed_text']) ? '<p><strong>Detailed Text:</strong> ' . esc_html($field['detailed_text']) . '</p>' : '';
                $content .= !empty($field['summary_text']) ? '<p><strong>Summary Text:</strong> ' . esc_html($field['summary_text']) . '</p>' : '';
                $content .= !empty($field['url']) ? '<p><a href="' . esc_url($field['url']) . '" target="_blank">' . esc_html($field['url_title']) . '</a></p>' : '';
                $content .= '</div>';
                $content .= '<div style="flex: 1;">' . (!empty($field['image']) ? '<img src="' . esc_url($field['image']) . '" style="max-width: 100%; height: auto; border-radius: 10px;" />' : '') . '</div>';
                $content .= '</div></div>';
            }
            $content .= '</div><div class="swiper-pagination"></div></div></div>';
        }
    }
    return $content;
}
add_filter('the_content', 'display_custom_fields_in_content');


// Enqueue Swiper Scripts and Styles
function enqueue_swiper_scripts() {
    if (is_singular('heart-care') || is_post_type_archive('heart-care')) {
        wp_enqueue_style('swiper-style', 'https://unpkg.com/swiper/swiper-bundle.min.css', [], null);
        wp_enqueue_script('swiper-script', 'https://unpkg.com/swiper/swiper-bundle.min.js', [], null, true);
        $swiper_inline_script = "
        document.addEventListener('DOMContentLoaded', function() {
            new Swiper('.swiper-container', {
                loop: true,
                autoplay: { delay: 5000, disableOnInteraction: false },
                slidesPerView: 1,
                pagination: { el: '.swiper-pagination', clickable: true },
            });
        });
        ";
        wp_add_inline_script('swiper-script', $swiper_inline_script);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_swiper_scripts');

// Enqueue Admin Scripts
function enqueue_custom_meta_box_scripts($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        if (!wp_script_is('media-editor', 'enqueued')) {
            wp_enqueue_media();
        }
        wp_enqueue_script(
            'custom-meta-box-script',
            get_template_directory_uri() . '/custom-meta-box.js',
            ['jquery'],
            null,
            true
        );
        wp_add_inline_script(
            'custom-meta-box-script',
            '
            jQuery(document).ready(function($) {
                let mediaUploader;

                $("#add-new-field-group").on("click", function() {
                    var index = $("#custom-fields-container .custom-field-group").length;
                    var newGroup = \'<div class="custom-field-group">\' +
                        \'<label><strong>Upload Image:</strong></label><br>\' +
                        \'<input type="text" name="custom_fields[\' + index + \'][image]" style="width: 80%;" />\' +
                        \'<button type="button" class="button upload-image-button">Upload Image</button>\' +
                        \'<button type="button" class="button button-secondary remove-image-button">Remove Image</button>\' +
                        \'<div class="image-preview" style="margin-top: 10px;"></div>\' +
                        \'<label><strong>Detailed Text:</strong></label><br>\' +
                        \'<textarea name="custom_fields[\' + index + \'][detailed_text]" rows="5" style="width: 100%;"></textarea>\' +
                        \'<label><strong>Summary Text:</strong></label><br>\' +
                        \'<textarea name="custom_fields[\' + index + \'][summary_text]" rows="3" style="width: 100%;"></textarea>\' +
                        \'<label><strong>URL Link:</strong></label><br>\' +
                        \'<input type="url" name="custom_fields[\' + index + \'][url]" style="width: 100%;" />\' +
                        \'<label><strong>URL Title:</strong></label><br>\' +
                        \'<input type="text" name="custom_fields[\' + index + \'][url_title]" style="width: 100%;" />\' +
                        \'<button type="button" class="button remove-field-group">Remove Group</button>\' +
                    \'</div>\';
                    $("#custom-fields-container").append(newGroup);
                });

                $(document).on("click", ".remove-field-group", function() {
                    $(this).closest(".custom-field-group").remove();
                });

                $(document).on("click", ".upload-image-button", function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var input = button.siblings("input");

                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    mediaUploader = wp.media({
                        title: "Select Image",
                        button: { text: "Use this image" },
                        multiple: false
                    });

                    mediaUploader.on("select", function() {
                        var attachment = mediaUploader.state().get("selection").first().toJSON();
                        input.val(attachment.url);
                        button.siblings(".image-preview").html(\'<img src="\' + attachment.url + \'" style="max-width: 150px; height: auto;" />\');
                    });

                    mediaUploader.open();
                });

                $(document).on("click", ".remove-image-button", function() {
                    var input = $(this).siblings("input");
                    var preview = $(this).siblings(".image-preview");
                    input.val("");
                    preview.html("");
                });
            });
            '
        );
    }
}
add_action('admin_enqueue_scripts', 'enqueue_custom_meta_box_scripts');
