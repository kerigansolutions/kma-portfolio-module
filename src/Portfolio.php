<?php
namespace KeriganSolutions\KMAPortfolio;

class Portfolio
{
    public $menuName;
    public $menuIcon;
    public $singularName;
    public $pluralName;
    public $hideGallery;

    public function __construct()
    {
        $this->menuName = 'Portfolio';
        $this->singularName = 'Project';
        $this->pluralName = 'Portfolio';
        $this->menuIcon = 'portfolio';
        $this->hideGallery = false;

        // Create REST API Routes
        add_action( 'rest_api_init', [$this, 'addRoutes'] );
    }

    public function menuName($menuName)
    {
        $this->menuName = $menuName;

        return $this;
    }

    public function menuIcon($menuIcon)
    {
        $this->menuIcon = $menuIcon;

        return $this;
    }

    public function singularName($singularName)
    {
        $this->singularName = $singularName;

        return $this;
    }

    public function pluralName($pluralName)
    {
        $this->pluralName = $pluralName;

        return $this;
    }

    public function hideGallery()
    {
        $this->hideGallery = true;

        return $this;
    }

    public function registerFields()
    {
        // ACF Group: slide Details
        acf_add_local_field_group(array(
            'key' => 'group_project_details',
            'title' => $this->singularName . ' Details',
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'project',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
        ));

        // Image
        acf_add_local_field(array(
            'key' => 'featured_image',
            'label' => 'Featured Image',
            'name' => 'image',
            'type' => 'image',
            'parent' => 'group_project_details',
            'instructions' => '',
            'required' => 0,
            'return_format' => 'array',
            'preview_size' => 'large',
            'library' => 'all',
            'min_width' => 0,
            'min_height' => 0,
            'max_width' => 0,
            'max_height' => 0,
        ));

        if (!$this->hideGallery) {
            // Gallery
            acf_add_local_field(array(
                'key' => 'gallery',
                'label' => 'Additional Photos',
                'name' => 'gallery',
                'type' => 'gallery',
                'parent' => 'group_project_details',
                'instructions' => '',
                'required' => 0,
                'max' => 20,
                'preview_size' => 'thumbnail',
                'max_size' => '10',
                'mime_types' => 'jpg,jpeg,png,tiff,tif,svg,swf,pdf,webp',
            ));
        }
    }

    public function use()
    {
        add_action('init', [$this, 'project_init']);
        add_filter('post_updated_messages', [$this, 'project_updated_messages']);
        if (function_exists('acf_add_local_field_group')) {
            add_action('acf/init', [$this, 'registerFields']);
        }
    }

    /*
     * Query WP for slides
     */
    public function queryProjects($limit = -1, $location = '', $type = '')
    {
        $request = [
            'posts_per_page' => $limit,
            'offset' => 0,
            'order' => 'ASC',
            'orderby' => 'menu_order',
            'post_type' => 'project',
            'post_status' => 'publish',
        ];

        if($location != '' || $type != ''){
            $request['tax_query'] = [];
        }

        if ($location != '') {
            $locationarray = [
                'taxonomy' => 'build-location',
                'field' => 'slug',
                'terms' => $location,
                'include_children' => false,
            ];
            $request['tax_query'][] = $locationarray;
        }
        if ($type != '') {
            $typearray = [
                'taxonomy' => 'construction-type',
                'field' => 'slug',
                'terms' => $type,
                'include_children' => false,
            ];
            $request['tax_query'][] = $typearray;
        }


        $projectList = get_posts($request);

        $projectArray = [];
        foreach ($projectList as $project) {
            array_push($projectArray, [
                'id' => (isset($project->ID) ? $project->ID : null),
                'name' => (isset($project->post_title) ? $project->post_title : null),
                'slug' => (isset($project->post_name) ? $project->post_name : null),
                'photo' => get_field('image', $project->ID),
                'gallery' => get_field('gallery', $project->ID),
                'link'    => get_permalink($project->ID),
                'build_location' => get_the_terms($project->ID, 'build-location'),
                'construciton_type' => get_the_terms($project->ID, 'construction-type')
            ]);
        }

        return $projectArray;
    }

    /*
     * Get slides using REST API endpoint
     */
    public function getProjects($request)
    {
        $limit = $request->get_param('limit');
        $location = $request->get_param('build-location');
        $type = $request->get_param('construction-type');
        return rest_ensure_response($this->queryProjects($limit, $location, $type));
    }

    /**
     * Add REST API routes
     */
    public function addRoutes()
    {
        register_rest_route(
            'kerigansolutions/v1',
            '/projects',
            [
                'methods' => 'GET',
                'callback' => [$this, 'getProjects']
            ]
        );
    }
    public function project_init()
    {
        register_post_type('project', array(
            'labels' => array(
                'name' => __($this->menuName, 'wordplate'),
                'singular_name' => __($this->singularName, 'wordplate'),
                'all_items' => __($this->menuName, 'wordplate'),
                'archives' => __($this->menuName . ' Archives', 'wordplate'),
                'attributes' => __($this->singularName . 'Attributes', 'wordplate'),
                'insert_into_item' => __('Insert into ' . $this->singularName, 'wordplate'),
                'uploaded_to_this_item' => __('Uploaded to this ' . $this->singularName, 'wordplate'),
                'featured_image' => _x('Featured Image', 'project', 'wordplate'),
                'set_featured_image' => _x('Set featured image', 'project', 'wordplate'),
                'remove_featured_image' => _x('Remove featured image', 'project', 'wordplate'),
                'use_featured_image' => _x('Use as featured image', 'project', 'wordplate'),
                'filter_items_list' => __('Filter Projects list', 'wordplate'),
                'items_list_navigation' => __($this->menuName . ' list navigation', 'wordplate'),
                'items_list' => __($this->menuName . ' list', 'wordplate'),
                'new_item' => __('New ' . $this->singularName, 'wordplate'),
                'add_new' => __('Add New', 'wordplate'),
                'add_new_item' => __('Add New ' . $this->singularName, 'wordplate'),
                'edit_item' => __('Edit ' . $this->singularName, 'wordplate'),
                'view_item' => __('View ' . $this->singularName, 'wordplate'),
                'view_items' => __('View ' . $this->menuName, 'wordplate'),
                'search_items' => __('Search ' . $this->menuName, 'wordplate'),
                'not_found' => __('No Projects found', 'wordplate'),
                'not_found_in_trash' => __('No Projects found in trash', 'wordplate'),
                'parent_item_colon' => __('Parent Project:', 'wordplate'),
                'menu_name' => __($this->menuName, 'wordplate'),
            ),
            'public' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'supports' => array('title', 'editor'),
            'has_archive' => true,
            'rewrite' => true,
            'query_var' => true,
            'menu_icon' => 'dashicons-' . $this->menuIcon,
            'show_in_rest' => true,
            'rest_base' => 'project',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ));

    }

    /**
     * Sets the post updated messages for the `project` post type.
     *
     * @param  array $messages Post updated messages.
     * @return array Messages for the `project` post type.
     */
    public function project_updated_messages($messages)
    {
        global $post;

        $permalink = get_permalink($post);

        $messages['project'] = array(
            0 => '', // Unused. Messages start at index 1.
		/* translators: %s: post permalink */
            1 => sprintf(__($this->singularName . ' updated. <a target="_blank" href="%s">View ' . $this->singularName . '</a>', 'wordplate'), esc_url($permalink)),
            2 => __('Custom field updated.', 'wordplate'),
            3 => __('Custom field deleted.', 'wordplate'),
            4 => __($this->singularName . ' updated.', 'wordplate'),
		/* translators: %s: date and time of the revision */
            5 => isset($_GET['revision']) ? sprintf(__($this->singularName . ' restored to revision from %s', 'wordplate'), wp_post_revision_title((int)$_GET['revision'], false)) : false,
		/* translators: %s: post permalink */
            6 => sprintf(__($this->singularName . ' published. <a href="%s">View ' . $this->singularName . '</a>', 'wordplate'), esc_url($permalink)),
            7 => __($this->singularName . ' saved.', 'wordplate'),
		/* translators: %s: post permalink */
            8 => sprintf(__($this->singularName . ' submitted. <a target="_blank" href="%s">Preview ' . $this->singularName . '</a>', 'wordplate'), esc_url(add_query_arg('preview', 'true', $permalink))),
		/* translators: 1: Publish box date format, see https://secure.php.net/date 2: Post permalink */
            9 => sprintf(
                __($this->singularName . ' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview ' . $this->singularName . '</a>', 'wordplate'),
                date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)),
                esc_url($permalink)
            ),
		/* translators: %s: post permalink */
            10 => sprintf(__($this->singularName . ' draft updated. <a target="_blank" href="%s">Preview ' . $this->singularName . '</a>', 'wordplate'), esc_url(add_query_arg('preview', 'true', $permalink))),
        );

        return $messages;
    }

}
