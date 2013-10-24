<?php

class acf_field_image extends acf_field
{
	
	/*
	*  __construct
	*
	*  Set name / label needed for actions / filters
	*
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function __construct()
	{
		// vars
		$this->name = 'image';
		$this->label = __("Image",'acf');
		$this->category = __("Content",'acf');
		$this->defaults = array(
			'return_format'	=>	'array',
			'preview_size'	=>	'thumbnail',
			'library'		=>	'all'
		);
		$this->l10n = array(
			'select'		=>	__("Select Image",'acf'),
			'edit'			=>	__("Edit Image",'acf'),
			'update'		=>	__("Update Image",'acf'),
			'uploadedTo'	=>	__("uploaded to this post",'acf'),
		);
		
		
		// do not delete!
    	parent::__construct();
    	
    	
		// filters
		add_filter('get_media_item_args', array($this, 'get_media_item_args'));
		add_filter('wp_prepare_attachment_for_js', array($this, 'wp_prepare_attachment_for_js'), 10, 3);
		
		
		// JSON
		//add_action('wp_ajax_acf/fields/image/get_images', array($this, 'ajax_get_images'), 10, 1);
		//add_action('wp_ajax_nopriv_acf/fields/image/get_images', array($this, 'ajax_get_images'), 10, 1);
	}
	
	
	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function render_field( $field )
	{
		// vars
		$div_atts = array(
			'class'					=> 'acf-image-uploader clearfix',
			'data-preview_size'		=> $field['preview_size'],
			'data-library'			=> $field['library']
		);
		$input_atts = array(
			'type'					=> 'hidden',
			'name'					=> $field['name'],
			'value'					=> $field['value'],
			'class'					=> 'acf-image-value'
		);
		$url = '';
		
		
		// has value?
		if( $field['value'] && is_numeric($field['value']) )
		{
			$url = wp_get_attachment_image_src($field['value'], $field['preview_size']);
			$url = $url[0];
			
			$div_atts['class'] .= ' active';
		}
		
		?>
<div <?php acf_esc_attr_e( $div_atts ); ?>>
	<input <?php acf_esc_attr_e( $input_atts ); ?>/>
	<div class="has-image">
		<div class="hover">
			<ul class="acf-bl">
				<li><a class="acf-button-delete ir" href="#"><?php _e("Remove",'acf'); ?></a></li>
				<li><a class="acf-button-edit ir" href="#"><?php _e("Edit",'acf'); ?></a></li>
			</ul>
		</div>
		<img class="acf-image-image" src="<?php echo $url; ?>" alt=""/>
	</div>
	<div class="no-image">
		<p><?php _e('No image selected','acf'); ?> <input type="button" class="button add-image" value="<?php _e('Add Image','acf'); ?>" />
	</div>
</div>
		<?php
	}
	
	
	/*
	*  render_field_options()
	*
	*  Create extra options for your field. This is rendered when editing a field.
	*  The value of $field['name'] can be used (like bellow) to save extra data to the $field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field	- an array holding all the field's data
	*/
	
	function render_field_options( $field ) {
		
		// return_format
		acf_render_field_option( $this->name, array(
			'label'			=> __('Return Value','acf'),
			'instructions'	=> __('Specify the returned value on front end','acf'),
			'type'			=> 'radio',
			'name'			=> 'return_format',
			'prefix'		=> $field['prefix'],
			'value'			=> $field['return_format'],
			'layout'		=> 'horizontal',
			'choices'		=> array(
				'array'			=> __("Image Array",'acf'),
				'url'			=> __("Image URL",'acf'),
				'id'			=> __("Image ID",'acf')
			)
		));
		
		
		// preview_size
		acf_render_field_option( $this->name, array(
			'label'			=> __('Preview Size','acf'),
			'instructions'	=> __('Shown when entering data','acf'),
			'type'			=> 'radio',
			'name'			=> 'preview_size',
			'prefix'		=> $field['prefix'],
			'value'			=> $field['preview_size'],
			'layout'		=> 'horizontal',
			'choices'		=> acf_get_image_sizes()
		));
		
		
		// library
		acf_render_field_option( $this->name, array(
			'label'			=> __('Library','acf'),
			'instructions'	=> __('Limit the media library choice','acf'),
			'type'			=> 'radio',
			'name'			=> 'library',
			'prefix'		=> $field['prefix'],
			'value'			=> $field['library'],
			'layout'		=> 'horizontal',
			'choices' 		=> array(
				'all'			=> __('All', 'acf'),
				'uploadedTo'	=> __('Uploaded to post', 'acf')
			)
		));		
	}
	
	
	/*
	*  format_value_for_api()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is passed back to the api functions such as the_field
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/
	
	function format_value_for_api( $value, $post_id, $field )
	{
		
		// validate
		if( !$value )
		{
			return false;
		}
		
		
		// format
		if( $field['return_format'] == 'url' )
		{
			$value = wp_get_attachment_url( $value );
		}
		elseif( $field['return_format'] == 'array' )
		{
			$attachment = get_post( $value );
			
			
			// validate
			if( !$attachment )
			{
				return false;	
			}
			
			
			// create array to hold value data
			$src = wp_get_attachment_image_src( $attachment->ID, 'full' );
			
			$value = array(
				'ID' => $attachment->ID,
				'id' => $attachment->ID,
				'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
				'title' => $attachment->post_title,
				'caption' => $attachment->post_excerpt,
				'description' => $attachment->post_content,
				'url' => $src[0],
				'width' => $src[1],
				'height' => $src[2],
				'sizes' => array(),
			);
			
			
			// find all image sizes
			$image_sizes = get_intermediate_image_sizes();
			
			if( $image_sizes )
			{
				foreach( $image_sizes as $image_size )
				{
					// find src
					$src = wp_get_attachment_image_src( $attachment->ID, $image_size );
					
					// add src
					$value[ 'sizes' ][ $image_size ] = $src[0];
					$value[ 'sizes' ][ $image_size . '-width' ] = $src[1];
					$value[ 'sizes' ][ $image_size . '-height' ] = $src[2];
				}
				// foreach( $image_sizes as $image_size )
			}
			// if( $image_sizes )
			
		}
		
		return $value;
		
	}
	
	
	/*
	*  get_media_item_args
	*
	*  @description: 
	*  @since: 3.6
	*  @created: 27/01/13
	*/
	
	function get_media_item_args( $vars )
	{
	    $vars['send'] = true;
	    return($vars);
	}
	
	
	/*
   	*  ajax_get_images
   	*
   	*  @description: 
   	*  @since: 3.5.7
   	*  @created: 13/01/13
   	*/
	
   	/*
function ajax_get_images()
   	{
   		// vars
		$options = array(
			'nonce' => '',
			'images' => array(),
			'preview_size' => 'thumbnail'
		);
		$return = array();
		
		
		// load post options
		$options = array_merge($options, $_POST);
		
		
		// verify nonce
		if( ! wp_verify_nonce($options['nonce'], 'acf_nonce') )
		{
			die(0);
		}
		
		
		if( $options['images'] )
		{
			foreach( $options['images'] as $id )
			{
				$url = wp_get_attachment_image_src( $id, $options['preview_size'] );
				
				
				$return[] = array(
					'id' => $id,
					'url' => $url[0],
				);
			}
		}
		
		
		// return json
		echo json_encode( $return );
		die;
		
   	}
*/
   		
	
	/*
	*  image_size_names_choose
	*
	*  @description: 
	*  @since: 3.5.7
	*  @created: 13/01/13
	*/
	
	function image_size_names_choose( $sizes )
	{
		global $_wp_additional_image_sizes;
			
		if( $_wp_additional_image_sizes )
		{
			foreach( $_wp_additional_image_sizes as $k => $v )
			{
				$title = $k;
				$title = str_replace('-', ' ', $title);
				$title = str_replace('_', ' ', $title);
				$title = ucwords( $title );
				
				$sizes[ $k ] = $title;
			}
			// foreach( $image_sizes as $image_size )
		}
		
        return $sizes;
	}
	
	
	/*
	*  wp_prepare_attachment_for_js
	*
	*  @description: This sneaky hook adds the missing sizes to each attachment in the 3.5 uploader. It would be a lot easier to add all the sizes to the 'image_size_names_choose' filter but then it will show up on the normal the_content editor
	*  @since: 3.5.7
	*  @created: 13/01/13
	*/
	
	function wp_prepare_attachment_for_js( $response, $attachment, $meta )
	{
		// only for image
		if( $response['type'] != 'image' )
		{
			return $response;
		}
		
		
		// make sure sizes exist. Perhaps they dont?
		if( !isset($meta['sizes']) )
		{
			return $response;
		}
		
		
		$attachment_url = $response['url'];
		$base_url = str_replace( wp_basename( $attachment_url ), '', $attachment_url );
		
		if( isset($meta['sizes']) && is_array($meta['sizes']) )
		{
			foreach( $meta['sizes'] as $k => $v )
			{
				if( !isset($response['sizes'][ $k ]) )
				{
					$response['sizes'][ $k ] = array(
						'height'      =>  $v['height'],
						'width'       =>  $v['width'],
						'url'         => $base_url .  $v['file'],
						'orientation' => $v['height'] > $v['width'] ? 'portrait' : 'landscape',
					);
				}
			}
		}

		return $response;
	}
	
	
	/*
	*  update_value()
	*
	*  This filter is appied to the $value before it is updated in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value - the value which will be saved in the database
	*  @param	$post_id - the $post_id of which the value will be saved
	*  @param	$field - the field array holding all the field options
	*
	*  @return	$value - the modified value
	*/
	
	function update_value( $value, $post_id, $field )
	{
		// array?
		if( is_array($value) && isset($value['ID']) )
		{
			$value = $value['ID'];	
		}
		
		// object?
		if( is_object($value) && isset($value->ID) )
		{
			$value = $value->ID;
		}
		
		// return
		return $value;
	}
	
	
}

new acf_field_image();

?>