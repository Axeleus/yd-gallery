/**
 * Created by Vitaly Kukin on 29.03.2016.
 */
jQuery(function($){

    var app = {

        objTotmpl : function ( tmpl, data ) {
            if(typeof Handlebars === 'undefined'){
                console.log('Handlebars not registry');
                return false
            }

            var template = Handlebars.compile(tmpl);
            return template(data);
        }
    };

    var innerPost = (function () {

        var obj  = {
            row     : '#tmpl-row-edit',
            img     : '#tmpl-item-media',
            gallery : '#ydg-gallery',
        };

        function renderMediaUploader() {

            var file_frame;

            if ( undefined !== file_frame ) {
                file_frame.open();
                return;
            }

            file_frame = wp.media.frames.file_frame = wp.media({
                frame    : 'post',
                state    : 'insert',
                multiple : true
            });

            file_frame.on( 'insert', function() {

                file_frame.state().get('selection').each(function(image){

                    if( ! checkExistingId(image.id) ) {
                        $.ajax({
                            url: ajaxurl,
                            data: {
                                action: 'ydg_get_image',
                                id: image.id,
                                size: 'medium'
                            },
                            type: "POST",
                            success: function (response) {

                                response = {id: image.id, url: response};
                                $(obj.gallery).append(app.objTotmpl($(obj.img).html(), response))
                            }
                        });
                    }
                });

            });

            file_frame.open();
        }

        function checkExistingId(id){

            var el = $(obj.gallery).find('.image-item');

            if( ! el.length ) return false;

            id = id.toString();

            var res = false;
            el.each(function(){

                var value = $(this).find('[name="gallery[]"]').val();

                if( value == id )
                    res = true;
            });

            return res;
        }

        return {

            manageGallery : function(){

                var el = obj.gallery;
                var item = '.image-item';

                $(el).sortable();

                $(el).on('click', '[data-toggle="remove"]', function() {
                    $(this).parents(item).remove();
                });
                
                $(el).on('click', '[data-toggle="move-left"]', function() {
                    var $th = $(this).parents(item);
                    if( $th.prev().length) {$th.prev().before( $th );}
                });

                $(el).on('click', '[data-toggle="move-right"]', function() {
                    var $th = $(this).parents(item);
                    if( $th.next().length) {$th.next().after( $th );}
                });
				
				$( '#ydg-upload-image' ).on( 'click', function( e ) {  

                    e.preventDefault();

                    renderMediaUploader();

                });
            },
            
            init : function(){
                this.manageGallery();
            }
        };
    })();

    innerPost.init();

});