<?php

/**
 * Your custom tile class
 */
class DT_Tasks_Tile extends DT_Dashboard_Tile
{

    /**
     * Register any assets the tile needs or do anything else needed on registration.
     * @return mixed
     */
    public function setup() {
//        wp_enqueue_script( $this->handle, 'path-t0-your-tiles-script.js', [], null, true);
    }

    /**
     * Render the tile
     */
    public function render() {
        global $wpdb;
        $contact_id = get_user_option( 'corresponds_to_contact', get_current_user_id() );

        $my_tasks = DT_Posts::list_posts( 'tasks', [
            'fields' => [ 'assigned_contact' => [ $contact_id ], 'status' => [ 'todo' ] ],
            'sort' => '-post_date',
            'limit' => 20
        ], true );

        $team_tasks = [];

        $nobody_tasks = DT_Posts::list_posts( 'tasks', [
            'fields' => [ 'assigned_contact' => []],
            'sort' => '-post_date',
            'limit' => 20
        ], true );


        ?>
        <div class='tile-header'>
            Tasks
        </div>
        <div class="tile-body">
            <strong>My Tasks</strong>
            <?php foreach ( array_slice( $my_tasks['posts'], 0, 8 ) as $c ) :
                $record_link = $c['record_link'] ?? $c['permalink'];
                ?>
                <div class="tile-row">
                    <a href="<?php echo esc_url( $record_link ) ?>">
                        <?php echo esc_html( $c['name'] ) ?> - <?php echo esc_html( $c['post_date']['formatted'] ) ?>
                    </a>
                </div>
            <?php endforeach;?>

            <br>
            <strong>Team Tasks</strong>
            <br>
            <br>
            <strong>Nobody Tasks</strong>
            <?php foreach ( array_slice( $nobody_tasks['posts'], 0, 8 ) as $c ) :
                $record_link = $c['record_link'] ?? $c['permalink'];
                ?>
                <div class="tile-row">
                    <a href="<?php echo esc_url( $record_link ) ?>">
                        <?php echo esc_html( $c['name'] ) ?> - <?php echo esc_html( $c['post_date']['formatted'] ) ?>
                    </a>
                </div>
            <?php endforeach;?>
        </div>
        <?php

    }
}

/**
 * Next, register our class. This can be done in the after_setup_theme hook.
 */
DT_Dashboard_Plugin_Tiles::instance()->register(
    new DT_Tasks_Tile(
        'dt_tasks_tile',                     //handle
        __( 'Tasks', 'your-plugin' ), //label
        [
            'priority' => 3,
            'span' => 1
        ]
    ));