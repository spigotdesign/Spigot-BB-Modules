<?php

/**
 * This is an example module with only the basic
 * setup necessary to get it working.
 *
 * @class SpigotHoverCard
 */
class SpigotHoverCard extends FLBuilderModule {

    /** 
     * Constructor function for the module. You must pass the
     * name, description, dir and url in an array to the parent class.
     *
     * @method __construct
     */  
    public function __construct()
    {
        parent::__construct(array(
            'name'          => __('Hover Card', 'fl-builder'),
            'description'   => __('A custom module for Spigot Post feed items.', 'fl-builder'),
            'category'      => __('Custom', 'fl-builder'),
            'group'         => __('Spigot Modules', 'fl-builder'),

        ));
    }

    /**
     * Ensure backwards compatibility with old settings.
     *
     * @since 2.2
     * @param object $settings A module settings object.
     * @param object $helper A settings compatibility helper.
     * @return object
     */
    public function filter_settings( $settings, $helper ) {

        // Handle old link fields.
        if ( isset( $settings->link_target ) ) {
            $settings->link_url_target = $settings->link_target;
            unset( $settings->link_target );
        }
        if ( isset( $settings->link_nofollow ) ) {
            $settings->link_url_nofollow = $settings->link_nofollow;
            unset( $settings->link_nofollow );
        }

        return $settings;
    }

    /**
     * @method enqueue_scripts
     */
    public function enqueue_scripts() {
        $override_lightbox = apply_filters( 'fl_builder_override_lightbox', false );

        if ( $this->settings && 'lightbox' == $this->settings->link_type ) {
            if ( ! $override_lightbox ) {
                $this->add_js( 'jquery-magnificpopup' );
                $this->add_css( 'font-awesome-5' );
                $this->add_css( 'jquery-magnificpopup' );
            } else {
                wp_dequeue_script( 'jquery-magnificpopup' );
                wp_dequeue_style( 'jquery-magnificpopup' );
            }
        }
    }

    /**
     * @method update
     * @param $settings {object}
     */
    public function update( $settings ) {
        // Make sure we have a photo_src property.
        if ( ! isset( $settings->photo_src ) ) {
            $settings->photo_src = '';
        }

        // Cache the attachment data.
        $settings->data = FLBuilderPhoto::get_attachment_data( $settings->photo );

        // Save a crop if necessary.
        $this->crop();

        return $settings;
    }

    /**
     * @method delete
     */
    public function delete() {
        $cropped_path = $this->_get_cropped_path();

        if ( fl_builder_filesystem()->file_exists( $cropped_path['path'] ) ) {
            fl_builder_filesystem()->unlink( $cropped_path['path'] );
        }
    }

    /**
     * @method crop
     */
    public function crop() {
        // Delete an existing crop if it exists.
        $this->delete();

        // Do a crop.
        if ( ! empty( $this->settings->crop ) ) {

            $editor = $this->_get_editor();

            if ( ! $editor || is_wp_error( $editor ) ) {
                return false;
            }

            $cropped_path = $this->_get_cropped_path();
            $size         = $editor->get_size();
            $new_width    = $size['width'];
            $new_height   = $size['height'];

            // Get the crop ratios.
            if ( 'landscape' == $this->settings->crop ) {
                $ratio_1 = 1.43;
                $ratio_2 = .7;
            } elseif ( 'panorama' == $this->settings->crop ) {
                $ratio_1 = 2;
                $ratio_2 = .5;
            } elseif ( 'portrait' == $this->settings->crop ) {
                $ratio_1 = .7;
                $ratio_2 = 1.43;
            } elseif ( 'square' == $this->settings->crop ) {
                $ratio_1 = 1;
                $ratio_2 = 1;
            } elseif ( 'circle' == $this->settings->crop ) {
                $ratio_1 = 1;
                $ratio_2 = 1;
            }

            // Get the new width or height.
            if ( $size['width'] / $size['height'] < $ratio_1 ) {
                $new_height = $size['width'] * $ratio_2;
            } else {
                $new_width = $size['height'] * $ratio_1;
            }

            // Make sure we have enough memory to crop.
            try {
                ini_set( 'memory_limit', '300M' );
            } catch ( Exception $e ) {
                //
            }

            // Crop the photo.
            $editor->resize( $new_width, $new_height, true );

            // Save the photo.
            $editor->save( $cropped_path['path'] );

            /**
             * Let third party media plugins hook in.
             * @see fl_builder_photo_cropped
             */
            do_action( 'fl_builder_photo_cropped', $cropped_path, $editor );

            // Return the new url.
            return $cropped_path['url'];
        }

        return false;
    }

    /**
     * @method get_data
     */
    public function get_data() {
        if ( ! $this->data ) {

            // Photo source is set to "url".
            if ( 'url' == $this->settings->photo_source ) {
                $this->data                = new stdClass();
                $this->data->alt           = $this->settings->caption;
                $this->data->caption       = $this->settings->caption;
                $this->data->link          = $this->settings->photo_url;
                $this->data->url           = $this->settings->photo_url;
                $this->settings->photo_src = $this->settings->photo_url;
            } elseif ( is_object( $this->settings->photo ) ) {
                $this->data = $this->settings->photo;
            } else {
                $this->data = FLBuilderPhoto::get_attachment_data( $this->settings->photo );
            }

            // Data object is empty, use the settings cache.
            if ( ! $this->data && isset( $this->settings->data ) ) {
                $this->data = $this->settings->data;
            }
        }

        return $this->data;
    }

    /**
     * @method get_classes
     */
    public function get_classes() {
        $classes = array( 'fl-photo-img' );

        if ( 'library' == $this->settings->photo_source && ! empty( $this->settings->photo ) ) {

            $data = self::get_data();

            if ( is_object( $data ) ) {

                if ( isset( $data->id ) ) {
                    $classes[] = 'wp-image-' . $data->id;
                }

                if ( isset( $data->sizes ) ) {

                    foreach ( $data->sizes as $key => $size ) {

                        if ( $size->url == $this->settings->photo_src ) {
                            $classes[] = 'size-' . $key;
                            break;
                        }
                    }
                }
            }
        }

        return implode( ' ', $classes );
    }

    /**
     * @method get_src
     */
    public function get_src() {
        $src = $this->_get_uncropped_url();

        // Return a cropped photo.
        if ( $this->_has_source() && ! empty( $this->settings->crop ) ) {

            $cropped_path = $this->_get_cropped_path();

            // See if the cropped photo already exists.
            if ( fl_builder_filesystem()->file_exists( $cropped_path['path'] ) ) {
                $src = $cropped_path['url'];
            } elseif ( stristr( $src, FL_BUILDER_DEMO_URL ) && ! stristr( FL_BUILDER_DEMO_URL, $_SERVER['HTTP_HOST'] ) ) {
                $src = $this->_get_cropped_demo_url();
            } elseif ( stristr( $src, FL_BUILDER_OLD_DEMO_URL ) ) { // It doesn't, check if this is a OLD demo image.
                $src = $this->_get_cropped_demo_url();
            } else { // A cropped photo doesn't exist, try to create one.

                $url = $this->crop();

                if ( $url ) {
                    $src = $url;
                }
            }
        }

        return $src;
    }

    /**
     * @method get_link
     */
    public function get_link() {
        $photo = $this->get_data();

        if ( 'url' == $this->settings->link_type ) {
            $link = $this->settings->link_url;
        } elseif ( isset( $photo ) && 'lightbox' == $this->settings->link_type ) {
            $link = $photo->url;
        } elseif ( isset( $photo ) && 'file' == $this->settings->link_type ) {
            $link = $photo->url;
        } elseif ( isset( $photo ) && 'page' == $this->settings->link_type ) {
            $link = $photo->link;
        } else {
            $link = '';
        }

        return $link;
    }

    /**
     * @method get_alt
     */
    public function get_alt() {
        $photo = $this->get_data();

        if ( ! empty( $photo->alt ) ) {
            return htmlspecialchars( $photo->alt );
        } elseif ( ! empty( $photo->description ) ) {
            return htmlspecialchars( $photo->description );
        } elseif ( ! empty( $photo->caption ) ) {
            return htmlspecialchars( $photo->caption );
        } elseif ( ! empty( $photo->title ) ) {
            return htmlspecialchars( $photo->title );
        }
    }

    /**
     * @method get_attributes
     */
    public function get_attributes() {
        $photo = $this->get_data();
        $attrs = '';

        if ( isset( $this->settings->attributes ) ) {
            foreach ( $this->settings->attributes as $key => $val ) {
                $attrs .= $key . '="' . $val . '" ';
            }
        }

        if ( is_object( $photo ) && isset( $photo->sizes ) ) {
            foreach ( $photo->sizes as $size ) {
                if ( $size->url == $this->settings->photo_src && isset( $size->width ) && isset( $size->height ) ) {
                    $attrs .= 'height="' . $size->height . '" width="' . $size->width . '" ';
                }
            }
        }

        if ( ! empty( $photo->title ) ) {
            $attrs .= 'title="' . htmlspecialchars( $photo->title ) . '" ';
        }

        if ( FLBuilderModel::is_builder_active() ) {
            $attrs .= 'onerror="this.style.display=\'none\'" ';
        }

        /**
         * Filter image attributes as a string.
         * @since 2.2.3
         * @see fl_builder_photo_attributes
         */
        return apply_filters( 'fl_builder_photo_attributes', $attrs );
    }

    /**
     * @method _has_source
     * @protected
     */
    protected function _has_source() {
        if ( 'url' == $this->settings->photo_source && ! empty( $this->settings->photo_url ) ) {
            return true;
        } elseif ( 'library' == $this->settings->photo_source && ! empty( $this->settings->photo_src ) ) {
            return true;
        }

        return false;
    }

    /**
     * @method _get_editor
     * @protected
     */
    protected function _get_editor() {
        if ( $this->_has_source() && null === $this->_editor ) {

            $url_path  = $this->_get_uncropped_url();
            $file_path = str_ireplace( home_url(), ABSPATH, $url_path );

            if ( fl_builder_filesystem()->file_exists( $file_path ) ) {
                $this->_editor = wp_get_image_editor( $file_path );
            } else {
                $this->_editor = wp_get_image_editor( $url_path );
            }
        }

        return $this->_editor;
    }

    /**
     * @method _get_cropped_path
     * @protected
     */
    protected function _get_cropped_path() {
        $crop      = empty( $this->settings->crop ) ? 'none' : $this->settings->crop;
        $url       = $this->_get_uncropped_url();
        $cache_dir = FLBuilderModel::get_cache_dir();

        if ( empty( $url ) ) {
            $filename = uniqid(); // Return a file that doesn't exist.
        } else {

            if ( stristr( $url, '?' ) ) {
                $parts = explode( '?', $url );
                $url   = $parts[0];
            }

            $pathinfo = pathinfo( $url );

            if ( isset( $pathinfo['extension'] ) ) {
                $dir      = $pathinfo['dirname'];
                $ext      = $pathinfo['extension'];
                $name     = wp_basename( $url, ".$ext" );
                $new_ext  = strtolower( $ext );
                $filename = "{$name}-{$crop}.{$new_ext}";
            } else {
                $filename = $pathinfo['filename'] . "-{$crop}.png";
            }
        }

        return array(
            'filename' => $filename,
            'path'     => $cache_dir['path'] . $filename,
            'url'      => $cache_dir['url'] . $filename,
        );
    }

    /**
     * @method _get_uncropped_url
     * @protected
     */
    protected function _get_uncropped_url() {
        if ( 'url' == $this->settings->photo_source ) {
            $url = $this->settings->photo_url;
        } elseif ( ! empty( $this->settings->photo_src ) ) {
            $url = $this->settings->photo_src;
        } else {
            $url = FL_BUILDER_URL . 'img/pixel.png';
        }

        return $url;
    }

    /**
     * @method _get_cropped_demo_url
     * @protected
     */
    protected function _get_cropped_demo_url() {
        $info = $this->_get_cropped_path();

        return FL_BUILDER_DEMO_CACHE_URL . $info['filename'];
    }

    /**
     * Returns link rel
     * @since 2.0.6
     */
    public function get_rel() {
        $rel = array();
        if ( '_blank' == $this->settings->link_url_target ) {
            $rel[] = 'noopener';
        }
        if ( isset( $this->settings->link_url_nofollow ) && 'yes' == $this->settings->link_url_nofollow ) {
            $rel[] = 'nofollow';
        }
        $rel = implode( ' ', $rel );
        if ( $rel ) {
            $rel = ' rel="' . $rel . '" ';
        }
        return $rel;
    }
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('SpigotHoverCard', array(
    'general'       => array( // Tab
        'title'         => __('General', 'fl-builder'), // Tab title
        'sections'      => array( // Tab Sections
            'general'       => array( // Section
                'title'         => __('Content', 'fl-builder'), // Section Title
                'fields'        => array( // Section Fields
                    'heading'    => array(
                        'type'        => 'text',
                        'label'       => __( 'Heading', 'fl-builder' ),
                        'default'     => '',
                        'preview'     => array(
                            'type'     => 'text',
                            'selector' => '.fl-heading-text',
                        ),
                        'connections' => array( 'string' ),
                    ),

                    'text' => array(
                        'type'        => 'editor',
                        'label'       => '',
                        'rows'        => 20,
                        'wpautop'     => false,
                        'preview'     => array(
                            'type'     => 'text',
                            'selector' => '.fl-rich-text',
                        ),
                        'connections' => array( 'string' ),
                    ),

                    'link'    => array(
                        'type'          => 'link',
                        'label'         => __( 'Link', 'fl-builder' ),
                        'show_target'   => true,
                        'show_nofollow' => true,
                        'preview'       => array(
                            'type' => 'none',
                        ),
                        'connections'   => array( 'url' ),
                    ),
                    'photo'        => array(
                        'type'        => 'photo',
                        'label'       => __( 'Photo', 'fl-builder' ),
                        'connections' => array( 'photo' ),
                        'show_remove' => true,
                        'preview'     => array(
                            'type' => 'none',
                        ),
                    ),
                )
            ),
        )
    ),
    /*
    'style'   => array(
        'title'    => __( 'Style', 'fl-builder' ),
        'sections' => array(
            'header' => array(
                'title'  => 'Heading',
                'fields' => array(
                    'color'      => array(
                        'type'        => 'color',
                        'connections' => array( 'color' ),
                        'show_reset'  => true,
                        'show_alpha'  => true,
                        'label'       => __( 'Color', 'fl-builder' ),
                        'preview'     => array(
                            'type'      => 'css',
                            'selector'  => '.fl-module-content *',
                            'property'  => 'color',
                            'important' => true,
                        ),
                    ),
                    'typography' => array(
                        'type'       => 'typography',
                        'label'      => __( 'Typography', 'fl-builder' ),
                        'responsive' => true,
                        'preview'    => array(
                            'type'      => 'css',
                            'selector'  => '{node}.fl-module-heading .fl-heading',
                            'important' => true,
                        ),
                    ),
                ),
            ),

            'content' => array(
                'title'  => 'Content',
                'fields' => array(
                    'color'      => array(
                        'type'        => 'color',
                        'connections' => array( 'color' ),
                        'show_reset'  => true,
                        'show_alpha'  => true,
                        'label'       => __( 'Color', 'fl-builder' ),
                        'preview'     => array(
                            'type'      => 'css',
                            'selector'  => '.fl-module-content *',
                            'property'  => 'color',
                            'important' => true,
                        ),
                    ),
                    'typography' => array(
                        'type'       => 'typography',
                        'label'      => __( 'Typography', 'fl-builder' ),
                        'responsive' => true,
                        'preview'    => array(
                            'type'      => 'css',
                            'selector'  => '{node}.fl-module-heading .fl-heading',
                            'important' => true,
                        ),
                    ),
                ),
            ),
        ),
    ), */
));
