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
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('SpigotHoverCard', array(
    'general'       => array( // Tab
        'title'         => __('General', 'fl-builder'), // Tab title
        'sections'      => array( // Tab Sections
            'general'       => array( // Section
                'title'         => __('Spigot Post Feed', 'fl-builder'), // Section Title
                'fields'        => array( // Section Fields
                    'post_items' => array(
                    'type'        => 'unit',
                    'label'       => 'Number of items',
                    'default'     => '3'
                    
                ),
                )
            )
        )
    )
));
