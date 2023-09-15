<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class Disciple_Tools_Tasks_Base
 * Load the core post type hooks into the Disciple.Tools system
 */
class Disciple_Tools_Tasks_Base {

    public $post_type = 'tasks';
    public $single_name = 'Task';
    public $plural_name = 'Tasks';
    public static function post_type(){
        return 'tasks';
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {

        //setup post type
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 ); //after contacts

        //setup tiles and fields
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
//        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        add_filter( 'dt_get_post_type_settings', [ $this, 'dt_get_post_type_settings' ], 20, 2 );

        add_action( 'dt_record_notifications_section', [ $this, 'dt_record_notifications_section' ], 10, 2 );


        // hooks
//        add_filter( 'dt_post_update_fields', [ $this, 'dt_post_update_fields' ], 10, 3 );
        add_filter( 'dt_post_create_fields', [ $this, 'dt_post_create_fields' ], 10, 2 );
        add_action( 'dt_post_created', [ $this, 'dt_post_created' ], 10, 3 );
        add_action( 'dt_post_updated', [ $this, 'dt_post_updated' ], 10, 3 );

        //list
        add_filter( 'dt_user_list_filters', [ $this, 'dt_user_list_filters' ], 120, 2 );
        add_filter( 'dt_filter_access_permissions', [ $this, 'dt_filter_access_permissions' ], 20, 2 );

        add_filter( 'desktop_navbar_menu_options', [ $this, 'desktop_navbar_menu_options' ], 25, 1 );
    }

    public function after_setup_theme(){
        $this->single_name = __( 'Task', 'disciple-tools-tasks' );
        $this->plural_name = __( 'Tasks', 'disciple-tools-tasks' );

        if ( class_exists( 'Disciple_Tools_Post_Type_Template' ) ) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }

    public function desktop_navbar_menu_options( $tabs ){
        if ( isset( $tabs[$this->post_type] ) ){
            $tabs[$this->post_type]['hidden'] = true;
        }
        return $tabs;
    }

      /**
     * Set the singular and plural translations for this post types settings
     * The add_filter is set onto a higher priority than the one in Disciple_tools_Post_Type_Template
     * so as to enable localisation changes. Otherwise the system translation passed in to the custom post type
     * will prevail.
     */
    public function dt_get_post_type_settings( $settings, $post_type ){
        if ( $post_type === $this->post_type ){
            $settings['label_singular'] = __( 'Task', 'disciple-tools-tasks' );
            $settings['label_plural'] = __( 'Tasks', 'disciple-tools-tasks' );
        }
        return $settings;
    }

    public function dt_set_roles_and_permissions( $expected_roles ){

        // if the user can access contact they also can access this post type
        foreach ( $expected_roles as $role => $role_value ){
            if ( isset( $expected_roles[$role]['permissions']['access_contacts'] ) && $expected_roles[$role]['permissions']['access_contacts'] ){
                $expected_roles[$role]['permissions']['access_' . $this->post_type ] = true;
                $expected_roles[$role]['permissions']['create_' . $this->post_type] = true;
                $expected_roles[$role]['permissions']['update_' . $this->post_type] = true;
            }
        }

        if ( isset( $expected_roles['dt_admin'] ) ){
            $expected_roles['dt_admin']['permissions']['view_any_'.$this->post_type ] = true;
            $expected_roles['dt_admin']['permissions']['update_any_'.$this->post_type ] = true;
        }
        if ( isset( $expected_roles['administrator'] ) ){
            $expected_roles['administrator']['permissions']['view_any_'.$this->post_type ] = true;
            $expected_roles['administrator']['permissions']['update_any_'.$this->post_type ] = true;
            $expected_roles['administrator']['permissions']['delete_any_'.$this->post_type ] = true;
        }

        return $expected_roles;
    }

    public function dt_custom_fields_settings( $fields, $post_type ){

        if ( $post_type === $this->post_type ){

            $fields['name']['tile'] = 'status';
            $fields['status'] = [
                'name'        => __( 'Status', 'disciple-tools-tasks' ),
                'description' => __( 'Set the current status.', 'disciple-tools-tasks' ),
                'type'        => 'key_select',
                'default'     => [
                    'todo' => [
                        'label' => __( 'Todo', 'disciple-tools-tasks' ),
                        'color' => '#F43636'
                    ],
                    'done'   => [
                        'label' => __( 'Done', 'disciple-tools-tasks' ),
                        'color' => '#4CAF50'
                    ],
                ],
                'tile'     => 'status',
                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                'default_color' => '#366184',
                'show_in_table' => 10,
            ];

            $fields['assigned_contact'] = [
                'name'        => __( 'Assigned Contact', 'disciple-tools-tasks' ),
                'type' => 'connection',
                'post_type' => 'contacts',
                'p2p_direction' => 'to',
                'p2p_key' => 'task_to_assigned',
                'tile' => 'status',
                'show_in_table' => 15
            ];
            $fields['record_link'] = [
                'name' => __( 'Record Link', 'disciple-tools-tasks' ),
                'description' => __( 'Link to the record this task is related to.', 'disciple-tools-tasks' ),
                'type' => 'text',
                'default' => '',
                'tile' => 'details',
                'icon' => get_template_directory_uri() . '/dt-assets/images/link.svg',
                'show_in_table' => 20,
            ];
            $fields['linked_comment'] = [
                'name' => __( 'Linked Comment', 'disciple-tools-tasks' ),
                'type' => 'text',
                'default' => '',
                'tile' => 'details',
                'icon' => get_template_directory_uri() . '/dt-assets/images/link.svg',
            ];

            $all_post_types = DT_Posts::get_post_types();
            foreach ( $all_post_types as $p ){
                if ( $p === $this->post_type ){
                    continue;
                }
                $fields[ 'connected_task_' . $p ] = [
                    'name' => $p,
                    'description' => '',
                    'type' => 'connection',
                    'post_type' => $p,
                    'p2p_direction' => 'any',
                    'p2p_key' => $p . '_to_' . $this->post_type,
                    'tile' => 'details',
                    'icon' => get_template_directory_uri() . '/dt-assets/images/group-type.svg',
                    'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
                ];
            }
        }

        if ( $post_type !== $this->post_type ){
            $fields['dt_tasks'] = [
                'name' => $this->plural_name,
                'description' => '',
                'type' => 'connection',
                'post_type' => $this->post_type,
                'p2p_direction' => 'any',
                'p2p_key' => $post_type . '_to_' . $this->post_type,
                'tile' => 'status',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-type.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
                'hidden' => true,
            ];
        }

        return $fields;
    }

    /**
     *
     * Display tasks on records
     *
     *
     * @param $post_type
     * @param $dt_post
     * @return void
     */
    public function dt_record_notifications_section( $post_type, $dt_post ){
        if ( isset( $dt_post['dt_tasks'] ) ): ?>
            <?php foreach ( $dt_post['dt_tasks'] as $connected_task ) :
                $task = DT_Posts::get_post( 'tasks', $connected_task['ID'], true, false );
                if ( $task['status']['key'] !== 'todo' ){
                    continue;
                }
                ?>
            <section class="cell small-12" id="task-<?php echo esc_html( $task['ID'] ); ?>">
                <div class="bordered-box detail-notification-box" style="background-color: #FF9800; text-align: start">
                    <div class="section-subheader">

                        <i class="mdi mdi-account"></i><?php echo esc_html( $task['assigned_contact'][0]['post_title'] ?? 'Nobody' ); ?>
                    </div>
                    <div style="display: flex; justify-content: space-between">
                        <div>
                            <?php echo esc_html( $task['title'] ); ?>
                            <a href="<?php echo esc_html( $task['permalink'] ); ?>">
                                <img class="dt-icon dt-white-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/open-link.svg' ) ?>"/>
                            </a>
                        </div>
                        <div>
                            <?php if ( !empty( $task['linked_comment'] ) ) : ?>
                                <button class="button small task-scroll-comment" style="margin-bottom: 0" data-comment="<?php echo esc_html( $task['linked_comment'] ); ?>">See Comment</button>

                            <?php endif; ?>
                        <button class="task-complete-button button small loader" style="background: #4caf50; margin: 0" data-task="<?php echo esc_html( $task['ID'] ); ?>"><?php esc_html_e( 'Complete', 'disciple_tools' )?></button>
                        </div>
                    </div>
                </div>
            </section>

            <script>
              jQuery(document).ready(function ($) {
                $('.task-scroll-comment').on('click', function () {
                  let comment_id = $(this).data('comment');
                  let target = $(`#comments-wrapper [data-comment-id="${comment_id}"]`).closest('.activity-text')
                  target.get(0).scrollIntoView({behavior: 'smooth'});
                  target.addClass('comment-highlighted');
                  setTimeout(function () {
                    target.addClass('comment-fadeOut');
                  }, 1000);

                })
                $('.task-complete-button').on('click', function () {
                  let task_id = $(this).data('task');
                  $(this).addClass('loading');
                  window.API.update_post('tasks', task_id, {status: 'done'}).then(function (response) {
                    $(`#task-${task_id}`).fadeOut(300);
                  })
                })
              })
            </script>
            <?php endforeach; ?>
            <style>
                .comment-highlighted {
                    background-color: #FF9800;
                    transition: background-color 1s .5s;
                }
                .comment-fadeOut {
                    background-color: white;
                }
            </style>
        <?php endif;
    }

    public function dt_details_additional_tiles( $tiles, $post_type = '' ){
        if ( $post_type === $this->post_type ){
            $tiles['connections'] = [ 'label' => __( 'Connections', 'disciple-tools-tasks' ) ];
            $tiles['other'] = [ 'label' => __( 'Other', 'disciple-tools-tasks' ) ];
        }
        return $tiles;
    }

    // filter at the start of post creation
    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            $post_fields = DT_Posts::get_post_field_settings( $post_type );
            if ( isset( $post_fields['status'] ) && !isset( $fields['status'] ) ){
                $fields['status'] = 'todo';
            }
        }
        return $fields;
    }

    public function create_task_for_webform( $post_type, $post_id, $initial_fields ){
        if ( $post_type === 'contacts' ){
            if ( isset( $initial_fields['notes'] ) && str_contains( $initial_fields['notes'][0], 'Source Form' ) ){
                global $wpdb;
                $post_comments = DT_Posts::get_post_comments( 'contacts', $post_id, false );
                //find the created comment

                $notes_exploded = explode( "\r\n", $initial_fields['notes'][0] );
                $name = 'Review Webform';
                if ( isset( $notes_exploded[1] ) && str_contains( $notes_exploded[1], 'Description: ' ) ){
                    $name = str_replace( 'Description: ', '', $notes_exploded[1] );
                } elseif ( get_current_user_id() === 0 && !empty( wp_get_current_user()->display_name ) ){
                    $name = 'Review ' . wp_get_current_user()->display_name;
                }

                $task = [
                    'title' => $name,
                    'status' => 'todo',
                    'record_link' => get_permalink( $post_id ),
                    'connected_task_' . $post_type => [ 'values' => [ [ 'value' => $post_id ] ] ],
                    'linked_comment' => isset( $post_comments['comments'][0]['comment_ID'] ) ? $post_comments['comments'][0]['comment_ID'] : '',
                ];
                if ( isset( $initial_fields['assigned_to'] ) ){
                    $user_id = dt_get_user_id_from_assigned_to( $initial_fields['assigned_to'] );
                    $user_contact = get_user_option( 'corresponds_to_contact', $user_id );
                    $task['assigned_contact'] = [ 'values' => [ [ 'value' => $user_contact ] ] ];
                }
                $create = DT_Posts::create_post( 'tasks', $task, true, false );
            }
        }
    }

    public function notification_for_task( $post_type, $post_id, $initial_fields ){
        if ( $post_type !== $this->post_type ){
            return;
        }
        $value = $initial_fields['assigned_contact']['values'][0]['value'] ?? null;
        if ( empty( $value ) && empty( $initial_fields['assigned_contact']['values'][0]['delete'] ) ){
            return;
        }
        $user_id = get_post_meta( $value, 'corresponds_to_user', true );
        if ( $user_id ){
            DT_Posts::add_shared( $post_type, $post_id, $user_id, null, false, false, false );
            Disciple_Tools_Notifications::insert_notification_for_subassigned( $user_id, $post_id );

            $args = [
                'user_id'             => $user_id,
                'source_user_id'      => get_current_user_id(),
                'post_id'             => $post_id,
                'secondary_item_id'   => 0,
                'notification_name'   => 'assigned_to',
                'notification_action' => 'alert',
                'notification_note'   => '<a href="' . home_url( '/' ) . get_post_type( $post_id ) . '/' . $post_id . '" >' . strip_tags( get_the_title( $post_id ) ) . '</a> was sub-assigned to you.',
                'date_notified'       => current_time( 'mysql' ),
                'is_new'              => 1,
                'field_key'           => 'comments',
                'field_value'         => ''
            ];

            do_action( 'send_notification_on_channels', $user_id, $args, 'assigned_to', [] );
        }
    }

    //action when a post has been created
    public function dt_post_created( $post_type, $post_id, $initial_fields ){
        $this->create_task_for_webform( $post_type, $post_id, $initial_fields );
        $this->notification_for_task( $post_type, $post_id, $initial_fields );
    }
    public function dt_post_updated( $post_type, $post_id, $initial_fields ){
        $this->create_task_for_webform( $post_type, $post_id, $initial_fields );
        $this->notification_for_task( $post_type, $post_id, $initial_fields );
    }

    //list page filters function
    private static function count_records_assigned_to_me_by_status(){
        global $wpdb;
        $post_type = self::post_type();
        $current_user = get_current_user_id();

        $contact_id = Disciple_Tools_Users::get_contact_for_user( $current_user );

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT status.meta_value as status, count(p2p.p2p_to) as count
            FROM $wpdb->p2p as p2p
            INNER JOIN $wpdb->posts a ON( a.ID = p2p.p2p_to AND a.post_type = %s and a.post_status = 'publish' )
            INNER JOIN $wpdb->postmeta status ON ( status.post_id = p2p.p2p_to AND status.meta_key = 'status' )
            WHERE ( p2p.p2p_from = %d AND p2p.p2p_type = 'task_to_assigned' )

            GROUP BY status.meta_value
        ", $post_type, $contact_id ), ARRAY_A);

        return $results;
    }

    //build list page filters
    public static function dt_user_list_filters( $filters, $post_type ){

        if ( $post_type === self::post_type() ){
            $records_assigned_to_me_by_status_counts = self::count_records_assigned_to_me_by_status();
            $fields = DT_Posts::get_post_field_settings( $post_type );
            /**
             * Setup my filters
             */
            $status_counts = [];
            $total_my = 0;
            foreach ( $records_assigned_to_me_by_status_counts as $count ){
                $total_my += $count['count'];
                dt_increment( $status_counts[$count['status']], $count['count'] );
            }

            // add assigned to me filters
            $filters['filters'][] = [
                'ID' => 'my_all',
                'tab' => 'all',
                'name' => __( 'My Tasks', 'disciple-tools-tasks' ),
                'query' => [
                    'assigned_contact' => [ 'me' ],
                    'sort' => 'status'
                ],
                'count' => $total_my,
            ];
            //add a filter for each status
            foreach ( $fields['status']['default'] as $status_key => $status_value ) {
                if ( isset( $status_counts[$status_key] ) ){
                    $filters['filters'][] = [
                        'ID' => 'my_' . $status_key,
                        'tab' => 'all',
                        'name' => 'My ' . $status_value['label'],
                        'query' => [
                            'assigned_contact' => [ 'me' ],
                            'status' => [ $status_key ],
                            'sort' => '-post_date'
                        ],
                        'count' => $status_counts[$status_key]
                    ];
                }
            }
        }
        return $filters;
    }

    // access permission
    public static function dt_filter_access_permissions( $permissions, $post_type ){
        if ( $post_type === self::post_type() ){
            if ( DT_Posts::can_view_all( $post_type ) ){
                $permissions = [];
            }
        }
        return $permissions;
    }

    // scripts
    public function scripts(){
        if ( is_singular( $this->post_type ) && get_the_ID() && DT_Posts::can_view( $this->post_type, get_the_ID() ) ){
            $test = '';
            // @todo add enqueue scripts
        }
    }
}


