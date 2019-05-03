<?php
/*
Plugin Name: ozguryazilim
Description: Her yazının like'lanabileceği bir düğme.
Author: lore
Version: 0.1
Text Domain: ozguryazilim
*/

/* Widget başlangıç */
class ozguryazilim_Widget extends WP_Widget {
    // wordpress kayıt widget
    function __construct() {
        parent::__construct(
            'ozguryazilim_widget',
            esc_html__( 'ozguryazilim - Liked posts', 'text_domain' ),
            array( 'description' => esc_html__( 'A widget to show most liked posts', 'text_domain' ), )
        );
    }

    // Front end display of widget
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        $meta_key = '_ozguryazilim_up';

        $arguments = array (
            'post_type'				=> 'any',
            'post_status'			=> 'publish',
            'pagination'			=> false,
            'posts_per_page'		=> 10,
            'cache_results'			=> true,
            'meta_key'				=> $meta_key,
            'order'					=> 'DESC',
            'orderby'				=> 'meta_value_num',
            'ignore_sticky_posts'	=> true
        );

        $ozguryazilim_query = new WP_Query($arguments);

        if($ozguryazilim_query->have_posts()) {
            $return = '<ul class="ozguryazilim-top-list">';

            while ($ozguryazilim_query->have_posts()) {
                $ozguryazilim_query->the_post();

                $return .= '<li>';
                $return .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';



                $meta_values = get_post_meta(get_the_ID(), $meta_key);

                $return .= ' (+';

                if( sizeof($meta_values) > 0){
                    $return .= $meta_values[0];
                } else {
                    $return .= "0";
                }
                $return .= ')';

            }

            $return .= '</li></ul>';

            wp_reset_postdata();
        }

        echo $return;

        echo $args['after_widget'];
    }

    // widget başlığı için form
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Please, provide a title', 'text_domain' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    // Formdaki alınan verilerin  widget başlıği güncelle()
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;
    }
} // class ozguryazilim_Widget

/* Register ozguryazilim_Widget widget */
function register_ozguryazilim_widget() {
    register_widget( 'ozguryazilim_Widget' );
}
add_action( 'widgets_init', 'register_ozguryazilim_widget' );


/* Tanımlar. */
define('ozguryazilim_url', plugins_url() ."/".dirname( plugin_basename( __FILE__ ) ) );
define('ozguryazilim_path', WP_PLUGIN_DIR."/".dirname( plugin_basename( __FILE__ ) ) );

/* Init scripts */
if  ( ! function_exists( 'ozguryazilim_scripts' ) ):
	function ozguryazilim_scripts() {
		wp_enqueue_script('ozguryazilim_scripts', ozguryazilim_url . '/js/ozguryazilim.js', array('jquery'), '4.0.1');
		wp_localize_script(
		        'ozguryazilim_scripts',
                'ozguryazilim_ajax',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( 'ozguryazilim-nonce' )
                )
        );
	}
	add_action('wp_enqueue_scripts', 'ozguryazilim_scripts');
endif;

/* Init styles */
if  ( ! function_exists( 'ozguryazilim_styles' ) ):
	function ozguryazilim_styles()	{
	    wp_register_style( "ozguryazilim_styles",  ozguryazilim_url . '/css/style.css' , "", "1.0.0");
	    wp_enqueue_style( 'ozguryazilim_styles' );
	}
	add_action('wp_enqueue_scripts', 'ozguryazilim_styles');
endif;

/* İçerik için like bağlantısı oluşturma */
if  ( ! function_exists( 'ozguryazilim_getlink' ) ):
	function ozguryazilim_getlink($post_ID = '', $type_of_vote = '') {
		$post_ID = intval( sanitize_text_field( $post_ID ) );
		$type_of_vote = intval ( sanitize_text_field( $type_of_vote ) );

		if( $post_ID == '' ) $post_ID = get_the_ID();

		$ozguryazilim_up_count = get_post_meta($post_ID, '_ozguryazilim_up', true) != '' ? get_post_meta($post_ID, '_ozguryazilim_up', true) : '0';

		$link_up = '<span class="ozguryazilim-up" data-vote="1">👍 <strong>' . $ozguryazilim_up_count . '</strong></span>';

		$ozguryazilim_link = '<div  class="ozguryazilim-container" id="ozguryazilim-'.$post_ID.'" data-content-id="'.$post_ID.'">' . $link_up . '</div>';

		return $ozguryazilim_link;
	}
endif;

if  ( ! function_exists( 'add_like_button' ) ):
    function add_like_button($content) {
        global $post;
        if ($post->post_type == 'post') {
            ob_start();
            if (is_single()) {
                $content .= ob_get_contents();
                $content .= ozguryazilim_getlink();
            }
            ob_end_clean();
        }
        return $content;
    }
endif;

add_filter('the_content', 'add_like_button');

/* Aajx isteğini yerine  */
if  ( ! function_exists( 'ozguryazilim_add_vote_callback' ) ):
	function ozguryazilim_add_vote_callback() {
		check_ajax_referer( 'ozguryazilim-nonce', 'nonce' );

		global $wpdb;

		$post_ID = intval( $_POST['postid'] );
		$type_of_vote = intval( $_POST['type'] );

		$meta_name = "_ozguryazilim_up";
		$ozguryazilim_count = get_post_meta($post_ID, $meta_name, true) != '' ? get_post_meta($post_ID, $meta_name, true) : '0';

		if ( $type_of_vote == 1 || $type_of_vote == -1) {
		    $ozguryazilim_count = $ozguryazilim_count + $type_of_vote ;
		}

		update_post_meta($post_ID, $meta_name, $ozguryazilim_count);

		$results = ozguryazilim_getlink($post_ID, $type_of_vote);

		die($results);
	}

	add_action( 'wp_ajax_ozguryazilim_add_vote', 'ozguryazilim_add_vote_callback' );
	add_action('wp_ajax_nopriv_ozguryazilim_add_vote', 'ozguryazilim_add_vote_callback');
endif;

/* Beğeniler için yönetici sayfası */
if  ( ! function_exists( 'load_custom_wp_admin_style' ) ):
    function load_custom_wp_admin_style() {
            wp_enqueue_style( 'ozguryazilim_styles',   ozguryazilim_url . '/css/jquery.DataTables.min.css' , false, "1.0.0" );
            wp_enqueue_script('custom_wp_admin_js', ozguryazilim_url . '/js/jquery.DataTables.min.js',  array('jquery'), '1.0.0');
    }
endif;
add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

if  ( ! function_exists( 'ozguryazilim_menu' ) ):
    function ozguryazilim_menu() {
        add_options_page( 'ozguryazilim Options', 'ozguryazilim', 'manage_options', 'ozguryazilim-plugin', 'ozguryazilim_options' );
    }
endif;

add_action( 'admin_menu', 'ozguryazilim_menu' );

if  ( ! function_exists( 'ozguryazilim_options' ) ):
    function ozguryazilim_options() {
        global $wpdb;

        echo '<div class="wrap">';
        echo '<p>En çok sevilen etiketler.</p>';
        echo '</div>';
        ?>
    <table id='myTable'>
        <thead>
            <tr>
                <td>Etiket</td>
                <td>Beğenilme</td>
            </tr>
        </thead>

        <tbody>
    <?php
        $tags = get_tags();
        foreach($tags as $tag) {
            $sum = 0;
            echo '<tr><td><strong>'.$tag->name. '</strong></td>';
            $args=array(
                'tag__in' => array($tag->term_id),
                'showposts'=>-1
            );
            $my_query = new WP_Query($args);
            if( $my_query->have_posts() ) {
                while ($my_query->have_posts()) : $my_query->the_post();
                    if (get_post_meta(get_the_ID(), '_ozguryazilim_up', true) != '') {
                        $sum += get_post_meta(get_the_ID(), '_ozguryazilim_up', true);
                    }
                endwhile;
                echo '<td>' . $sum . '</td>' ;
            }
            echo '</tr>';
        }
    ?>
        </tbody>
    </table>
    <?php
    } // ozguryazilim seçenekler();
endif;

