<?php
/**
 * Author: Vitaly Kukin
 * Date: 24.04.2016
 * Time: 10:02
 */

if( ! function_exists('pr') ){
    function pr( $any ) {
        echo '<pre>';
        print_r($any);
        echo '</pre>';
    }
}

function ydg_nl2br_content( $content ) {
	
    $content = apply_filters('the_content', $content);
	
    $content = str_replace(']]>', ']]>', $content);
	
    return $content;
}

function ydg_init_front_scripts(){

    if( ! is_singular( array('post', 'page') ) ) return false;

    printf('<link id="%s" href="%s" rel="stylesheet" type="text/css" />', 'fotorama', YDG_URL . '/css/fotorama.css');

    wp_register_script('fotorama', YDG_URL . '/js/fotorama.js', array('jquery'), '4.6.4');
    wp_enqueue_script('fotorama');

    return true;
}
add_action('wp', 'ydg_init_front_scripts');

function ydg_shortcode( $atts ) {

    global $post;

    if( ! is_singular( array('post', 'page') ) ) return false;

    $atts = shortcode_atts( array('filter' => 0), $atts, 'ydg-gallery' );

    $gallery = get_post_meta($post->ID, 'gallery', true);

    $args = ydg_get_list_images( $gallery, array('thumbnail', 'large') );

    if( ! $args || count($args) == 0 ) return false;

    ob_start();
    ?>
    <div class="fotorama-listing">
        <div class="fotorama-wrap">
            <div class="fotorama <?php echo $atts['filter'] == 1 ? 'fotorama-set-filter' : '' ?>" data-width="900" data-ratio="3/2" data-nav="thumbs" data-thumbheight="64" data-allowfullscreen="native">
                <?php

                foreach( $args as $key => $val ) {
                    printf(
                        '<div data-img="%s" data-thumb="%s" title="%s"><div class="inner-content"><h2 class="title">%s</h2>%s</div></div>',
                        $val['large']['url'], $val['thumbnail']['url'], $val['alt'], $val['title'], $val['content']
                    );
                }

                ?>
            </div>
        </div>
    </div>
    <?php

    $output_string = ob_get_contents();
    ob_end_clean();

    return $output_string;
}
add_shortcode( 'ydg-gallery', 'ydg_shortcode' );

function ydg_try_unserialize( $str ) {

    if( !$str )
        return false;

    try{
        $list = unserialize($str);
    }
    catch( Exception $e ) {
        return false;
    }

    return $list;
}

function ydg_get_image_by_id( $id, $size = 'thumbnail' ) {

    $img = wp_get_attachment_image_src($id, $size, false);

    if ($img) {
        return $img[0];
    }

    return false;

}

function ydg_list_images( $gallery ) {

    $foo = array();

    if( !$gallery || count($gallery) == 0 )
        return false;
    else {
        foreach( $gallery as $key => $val) {
            $foo[] = array('id' => $val['id'], 'url' => ydg_get_image_by_id($val['id'], 'medium') );
        }
    }

    if( count($foo) > 0 )
        return $foo;

    return false;
}

function ydg_parse_gallery( $data = array() ) {

    if ( ! is_array( $data ) ) {
        return '';
    }

    $foo = array();

    foreach ( $data as $key => $val )
        $foo[] = intval($val);

    return ( count( $foo ) > 0 ) ? serialize( $foo ) : '';
}

function ydg_get_list_images( $gallery, $size = 'thumbnail' ){

    if( !is_array($gallery) )
        $gallery = ydg_try_unserialize($gallery);

    if( $gallery && count($gallery) == 0 )
        return false;

    global $wpdb;

    $fileds = implode(',', $gallery);

    $result = $wpdb->get_results(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
          WHERE meta_key = '_wp_attachment_metadata' AND post_id IN ({$fileds}) 
          ORDER BY FIELD(post_id, {$fileds})");

    if( !$result ) return false;

    if( !is_array($size) )
        $size = array($size);

    $upload_dir = wp_upload_dir();
    $upload_dir = $upload_dir['baseurl'];

    $args = array();

    foreach( $result as $item ) {

        $f = ydg_try_unserialize($item->meta_value);
        if( ! $f ) continue;

        $folder = substr($f['file'], 0, strrpos($f['file'], '/'));

        $args[$item->post_id]['full'] = array(
            'width'  => $f['width'],
            'height' => $f['height'],
            'url'    => $upload_dir . '/' . $f['file']
        );

        foreach( $size as $i )
            if( isset($f['sizes'][$i]) )
                $args[$item->post_id][$i] = array(
                    'width'  => $f['sizes'][$i]['width'],
                    'height' => $f['sizes'][$i]['height'],
                    'url'    => $upload_dir . '/' . $folder . '/' . $f['sizes'][$i]['file']
                );
    }

    if( count( $args ) > 0 ){

        $result = $wpdb->get_results("SELECT ID, post_title, post_content, post_excerpt FROM {$wpdb->posts} WHERE ID IN ({$fileds})");

        if( $result ) foreach( $result as $res ) {
            if( isset( $args[$res->ID] ) ){
                $args[$res->ID]['title'] = $res->post_title;
                $args[$res->ID]['alt'] = esc_attr($res->post_excerpt);
                $args[$res->ID]['content'] = ydg_nl2br_content($res->post_content);
            }
        }
    }

    return $args;
}

function ydg_ajax_get_image(){

    $id     = absint($_POST['id']);
    $size   = strip_tags($_POST['size']);

    $url = ydg_get_image_by_id( $id, $size );

    echo $url ? $url : '';

    die();
}
add_action('wp_ajax_ydg_get_image', 'ydg_ajax_get_image');
?>