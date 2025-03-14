<?php

class CUSTOMER_USER_TAG_MAIN
{
    private static $instance;

    // Constructor function
    private function __construct()
    {
        $this->init_hooks();
    }

    public function init_hooks()
    {
        // Register Custom User Tags Taxonomy
        add_action('init', [$this, 'register_custom_user_tags_taxonomy']);

        // Add Custom User Tags Page under User Admin Page
        add_action('admin_menu', [$this, 'add_custom_user_tags_sub_page']);

        // Assign User Tags Taxonomy to Add and Edit Page of User
        add_action('show_user_profile', [$this, 'add_user_tag_field']);
        add_action('edit_user_profile', [$this, 'add_user_tag_field']);

        // Store the User Tag Fields for Profile and Edit User Page.
        add_action('edit_user_profile_update', [$this, 'attach_user_tags_field']);
        add_action('personal_options_update', [$this, 'attach_user_tags_field']);

        // Add Custom User Tag Column in User Table
        add_filter('manage_users_columns', [$this, 'add_user_tags_column']);
        add_filter('manage_users_custom_column', [$this, 'filter_manage_users_custom_column'], 10, 3);

        // Store the User Tags in User meta
        add_action('profile_update', [$this, 'action_profile_update'], 10, 1);

        // Enqueue Script and Style for the Select2
        add_action('admin_enqueue_scripts', [$this, 'enqueue_select2_scripts']);

        // AJAX Handler for Dynamic User Tag Search
        add_action('wp_ajax_fetch_user_tags', [$this, 'ajax_handler_fetch_user_tags']);
        add_action('wp_ajax_nopriv_fetch_user_tags', [$this, 'ajax_handler_fetch_user_tags']);

        // Add a new dropdown filter for User Table
        add_action('manage_users_extra_tablenav', [$this, 'action_manage_users_extra_tablenav']);

        // Modify the Query to Filter Users Tags
        add_action('pre_get_users', [$this, 'action_reference_pre_get_users']);
    }

    public function enqueue_select2_scripts($hook)
    {
        if (!$hook = 'user.php') {
            return;
        }

        // Enqueue the Select2 Scripts CDN
        wp_enqueue_style('select2_css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], false, 'all');
        wp_enqueue_script('select2_js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), false);

        // Enqueue Custom JS
        wp_enqueue_script('custom_user_tags_js', CUSTOM_USER_TAG_URL . 'assets/js/custom-user-tags.js', array('jquery', 'select2_js'), false);

        // Localize the Custom JS
        wp_localize_script('custom_user_tags_js', 'custom_user_tag_obj', array(
            'ajaxURL' => admin_url('admin-ajax.php')
        ));
    }


    public function ajax_handler_fetch_user_tags()
    {
        // Get the Search Term
        $searchTerm = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        $args = array(
            'taxonomy' => 'user_tag',
            'hide_empty' => false,
            'search' => $searchTerm
        );

        // Get the Searched Terms
        $terms = get_terms($args);

        $results = array();

        if (!empty($terms)) {
            foreach ($terms as $term) {
                $results[] = array(
                    'id' => $term->term_id,
                    'text' => $term->name
                );
            }
        }

        // Send the JSON Data
        wp_send_json($results);
    }

    public function register_custom_user_tags_taxonomy()
    {
        // Args for the Custom User Tags Taxonomy
        $args = array(
            'labels' => array(
                'name' => __('User Tags', 'custom-user-tags'),
                'singular_name' => __('User Tag', 'custom-user-tags'),
                'menu_name' => __('User Tags', 'custom-user-tags')
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'query_var' => true,
            'rewrite' => false,
        );

        register_taxonomy('user_tag', 'user', $args);
    }

    public function add_custom_user_tags_sub_page()
    {
        add_users_page(
            __('User Tags', 'custom-user-tags'),
            __('User Tags', 'custom-user-tags'),
            'manage_options',
            'edit-tags.php?taxonomy=user_tag'
        );
    }

    public function add_user_tag_field($user)
    {
        $terms = get_terms(array(
            'taxonomy' => 'user_tag',
            'hide_empty' => false,
        ));

        // Get the User Tags Attached to the User
        $user_tags = wp_get_object_terms($user->ID, 'user_tag', array('fields' => 'ids'));

        ?>
        <h2><?php echo __('User Tags', 'custom-user-tags'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="user_tags"><?php echo __('Select Tags', 'custom-user-tags'); ?></label></th>
                <td>
                    <select name="user_tags[]" id="user_tags" multiple="multiple">
                        <?php
                        foreach ($terms as $term) {
                            ?>
                            <option value="<?php echo $term->term_id; ?>" <?php echo in_array($term->term_id, $user_tags) ? 'selected' : ''; ?>>
                                <?php echo esc_html($term->name); ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <p class="description">
                        <?php _e('Hold down the Ctrl / Command button to select multiple options.', 'custom-user-tags'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Store User Tag in User Meta
     * @param int $user_id
     * @return void
     */
    public function action_profile_update($user_id): void
    {
        $user_tags = wp_get_object_terms($user_id, 'user_tag', array('fields' => 'ids'));
        if (!empty($user_tags)) {
            update_user_meta($user_id, 'custom_user_tags', $user_tags);
        } else {
            delete_user_meta($user_id, 'custom_user_tags');
        }
    }

    public function attach_user_tags_field($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        $tags = isset($_POST['user_tags']) ? array_map('intval', $_POST['user_tags']) : array();

        wp_set_object_terms($user_id, $tags, 'user_tag', false);
    }

    public function add_user_tags_column($columns)
    {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Insert the "User Tags" column **after the "Email" column**
            if ($key === 'name') {
                $new_columns['user_tag'] = __('User Tags', 'custom-user-tags');
            }
        }

        return $new_columns;
    }

    /**
     * Filters the display output of custom columns in the Users list table.
     *
     * @param string $output      Custom column output. Default empty.
     * @param string $column_name Column name.
     * @param int    $user_id     ID of the currently-listed user.
     * @return string Custom column output. Default empty.
     */
    public function filter_manage_users_custom_column($output, $column_name, $user_id)
    {
        $user_tags = wp_get_object_terms($user_id, 'user_tag');
        $output = '';
        if ($user_tags) {
            $output .= "<div class='wp_user_tags_div' style='display: flex; flex-wrap: wrap; gap: 8px;'>";
            foreach ($user_tags as $user_tag) {
                $output .= "<span class='wp_user_tag' style='padding: 8px; border-radius: 4px; background-color: #e6f3fa;'>{$user_tag->name}</span>";
            }
            $output .= '</div>';
        } else {
            $output = '-';
        }

        return $output;
    }


    /**
     * Add a new dropdown filter
     * @param mixed $which
     * @return void
     */
    public function action_manage_users_extra_tablenav($which): void
    {
        if ($which == 'top') {
            // Get the currently selected User Tag
            $selected_tag = isset($_GET['filter_tag']) ? intval($_GET['filter_tag']) : '';

            ?>
            <div class="alignleft actions">
                <label class="screen-reader-text" for="filter_tag">
                    Filter by User Tags… </label>
                <select name="filter_tag" id="filter_tag" style="width: 200px;">
                    <?php if (!empty($selected_tag)): ?>
                        <?php
                        $term = get_term($selected_tag, 'user_tag'); 
                        if (!empty($term)) {
                            echo '<option value="' . $term->term_id . '" selected>' . esc_html($term->name) . '</option>';
                        }
                        ?>
                    <?php endif; ?>
                </select>
                <input type="submit" name="filterit" id="filterit" class="button" value="Filter">
            </div>
            <?php
        }
    }


    /**
     * Modify the User Query before Load
     * @param WP_User_Query $query
     * @return void
     */
    public function action_reference_pre_get_users(\WP_User_Query $query): void
    {
        global $pagenow;

        if (is_admin() && $pagenow == 'users.php' && isset($_GET['filter_tag']) && !empty($_GET['filter_tag'])) {
            $tag_id = intval($_GET['filter_tag']);

            // Modify Query
            $query->set('meta_query', array(
                array(
                    'key' => 'custom_user_tags',
                    'value' => $tag_id,
                    'compare' => 'LIKE'
                )
            ));
        }
    }

    // Create the Instance for Singleton Class
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new CUSTOMER_USER_TAG_MAIN();
        }
        return self::$instance;
    }
}