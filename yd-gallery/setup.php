<?php
/**
 * Author: Vitaly Kukin
 * Date: 24.04.2016
 * Time: 10:01
 */

function ydg_install(){

	update_site_option('ydg-version', YDG_VERSION);
}

function ydg_installed(){

	if( !current_user_can('install_plugins') ) return false;

	if( get_site_option('ydg-version') < YDG_VERSION )
		ydg_install();

	return true;
}
add_action('admin_menu', 'ydg_installed');

function ydg_uninstall(){
	do_action('ydg_uninstall');
}

function ydg_deactivate(){}

function ydg_activate(){
	ydg_installed();

	do_action('ydg_activate');
}

function ydg_init_scripts(){

	$sceen = get_current_screen();

	if( ! isset( $sceen->post_type ) || ! in_array($sceen->post_type, array('post', 'page')) )
		return false;

	printf('<link id="%s" href="%s" rel="stylesheet" type="text/css" />', 'ydg-style', YDG_URL . '/css/main.css');

	wp_register_script('ajaxQueue', YDG_URL . '/js/jquery.ajaxQueue.min.js', array('jquery'), '1.0');
	wp_register_script('handlebars', YDG_URL . '/js/handlebars.min.js', array('jquery'), '1.0');
	wp_register_script('gallery-main', YDG_URL . '/js/gallery.js', array('ajaxQueue', 'handlebars'), '1.0');
	
	wp_enqueue_script('gallery-main');

	return true;
}
add_action('admin_head', 'ydg_init_scripts');

function ydg_metabox_post(){

	add_meta_box(
		"ydg_post",
		'Галерея',
		"ydg_call_gallery",
		array('post', 'page'),
		'normal',
		'high'
	);

}
add_action('add_meta_boxes', 'ydg_metabox_post');

function ydg_call_gallery(){

	global $post;
	?>
	<p class="description">Добавьте изображения в галерею. Для вставки в пост, используйте [ydg-gallery], наложить фильтр в полноэкранном режиме: [ydg-gallery filter="1"]</p>

	<?php

	printf(
            '<div class="row">
                <a href="javascript:;" id="ydg-upload-image" class="button button-primary button-large">
                    <span class="dashicons dashicons-plus"></span> %s
                </a>
            </div>',
            __('Add Media', 'ydg')
        );

        ?>

        <script id="tmpl-item-media" type="text/template">
            <div class="image-item">
                <div class="inner-item">
                    <input type="hidden" name="gallery[]" value="{{id}}">
                    <div class="card tile card-image card-black bg-image bg-opaque8">
                        <div class="cover-image" style="background-image: url('{{url}}')"></div>
                        <div class="context has-action-left has-action-right">
                            <div class="tile-action">
                                <a href="javascript:;" data-toggle="move-left"><i class="dashicons dashicons-arrow-left-alt2"></i></a>
                                <a href="javascript:;" data-toggle="move-right"><i class="dashicons dashicons-arrow-right-alt2"></i></a>
                            </div>
                            <div class="tile-action right">
                                <a href="javascript:;" data-toggle="remove"><i class="dashicons dashicons-no"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </script>

        <div class="row" id="ydg-gallery">
            <?php

				$gallery = get_post_meta($post->ID, 'gallery', true);
                $items = ydg_get_gallery_admin( $gallery );

                if( $items ) foreach($items as $item) {

                    $id = isset($item['media']) ? $item['media'] : $item['url'];

                    printf(
                        '<div class="image-item">
                            <div class="inner-item">
                                <input type="hidden" name="gallery[]" value="%s">
                                <div class="card tile card-image card-black bg-image bg-opaque8">
                                    <div class="cover-image" style="background-image: url(%s)"></div>
                                    <div class="context has-action-left has-action-right">
                                        <div class="tile-action">
                                            <a href="javascript:;" data-toggle="move-left"><i class="dashicons dashicons-arrow-left-alt2"></i></a>
                                            <a href="javascript:;" data-toggle="move-right"><i class="dashicons dashicons-arrow-right-alt2"></i></a>
                                        </div>
                                        <div class="tile-action right">
                                            <a href="javascript:;" data-toggle="remove"><i class="dashicons dashicons-no"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>',
                        $id,
                        $item['medium']['medium']['url']
                        );
                }
            ?>
        </div>
	<?php
}

function ydg_get_gallery_admin( $args ) {

	$args = ydg_try_unserialize( $args );

	if ( ! $args ) {
		return false;
	}

	$foo = array();

	$media = array();

	foreach ( $args as $i => $item ) {
		$media[]            = $item;
		$foo[ $i ]['media'] = $item;
	}



	$media = ydg_get_list_images( $media, 'medium' );

	foreach ( $foo as $i => $item ) {

		if ( isset( $item['media'] ) ) {

			if ( isset( $media[ $item['media'] ] ) ) {
				$foo[ $i ]['medium'] = $media[ $item['media'] ];
			}
			else {
				unset( $foo[ $i ] );
			}
		}
	}

	return $foo;
}

function ydg_save_post_data( $post_id ){

	if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

	if( !current_user_can('edit_page', $post_id) ) return;

	ydg_save_data($post_id);

}
add_action('save_post', 'ydg_save_post_data', 10, 1);

function ydg_save_data( $post_id ){

	$gallery = isset($_POST['gallery']) ? ydg_parse_gallery($_POST['gallery']) : array();

	update_post_meta( $post_id, 'gallery', $gallery );
}