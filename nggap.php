<?php
/*
Plugin Name: NextGEN Gallery as Post
Plugin URI: https://github.com/mcsf/nextgen-gallery-as-post
Description: Treat NextGEN galleries as regular posts (of a custom type). This allows for them to e.g. automatically show up on post streams, be searched through, have special taxonomies, and so on. This works by assigning a "proxy post" to each gallery and keeping that proxy's data up to date.
Version: 0.1
Author: mcsf
Author URI: http://github.com/mcsf
License: GPL2
*/

/*  Copyright 2012 Miguel Fonseca <miguelcsf@gmail.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


define("DEBUG", false);
define("NGGCF_IMAGES", 1);
define("NGGCF_GALLERY", 2);
define("NGGCF_PRIORITY", 10);

$priority = NGGCF_PRIORITY + 1;


add_action( 'init', 'nggap_register_type' );
function nggap_register_type() {
	register_post_type( 'nggap_gallery', array(
			'labels' => array(
				'name' => '[hidden] NGGAP Proxy Posts',
			),
			'public' => true,
			'show_ui' => DEBUG,
			'has_archive' => true,
			'exclude_from_search' => false,
		)
	);
}


add_filter( 'pre_get_posts' , 'nggap_show_type', $priority );
function nggap_show_type( $query ) {
	if ( $query->is_main_query() && ( is_category() || is_home() || is_search() ) ) {
		$types = $query->get( 'post_type' );
		$types[] = 'nggap_gallery';
		$query->set( 'post_type', $types );
	}
	return $query;
}


add_action( 'ngg_created_new_gallery', 'nggap_create_gallery', $priority );
function nggap_create_gallery( $gid ) {
	$post = array(
		'post_type' => 'nggap_gallery',
		'post_status' => 'draft',
	);

	$id = wp_insert_post( $post );
	if ( $id ) {
		update_post_meta( $id, 'nggap_gid', $gid );
		do_action( 'nggap_create_gallery', $gid );
	}
	else
		error_log( 'nggap_create_gallery: error inserting post' );
}


add_action( 'ngg_update_gallery', 'nggap_update_gallery', $priority, 2 );
function nggap_update_gallery( $gid, $postdata ) {
	$post = nggap_get_related_post( $gid );

	$post->post_title = esc_attr( $postdata['title'] );
	$post->post_content = esc_attr( $postdata['gallerydesc'] );
	$post->post_author = (int) $postdata['author'];

	do_action( 'nggap_update_gallery', $gid, $post );

	if ( ! wp_update_post( $post ) )
		error_log( 'nggap_update_gallery: error updating post' );
}


add_action( 'ngg_delete_gallery', 'nggap_delete_gallery', $priority );
function nggap_delete_gallery( $gid ) {
	$post = nggap_get_related_post( $gid );
	if ( $post ) {
		do_action( 'nggap_delete_gallery', $gid );
		wp_delete_post( $post->ID, true );
	}
	else error_log('nggap_delete_gallery: no related post');
}


add_filter( 'get_edit_post_link', 'nggap_edit_post_link' );
function nggap_edit_post_link( $url ) {
	$pattern = '@^(http.*/wp-admin/)post.php\?post=([0-9]+)@';

	if ( ! preg_match( $pattern, $url, $matches ) )
		return $url;

	$base = $matches[1];
	$id = $matches[2];

	if ( get_post_type( $id ) == 'nggap_gallery' ) {
		$gid = get_post_meta( $id, 'nggap_gid', true );
		$url = $base . "admin.php?page=nggallery-manage-gallery&mode=edit&gid={$gid}";
	}

	return $url;
}


function nggap_get_related_post( $gid ) {
	$posts = get_posts( array(
		'post_type' => 'nggap_gallery',
		'meta_key' => 'nggap_gid',
		'meta_value' => $gid,
		'post_status' => 'any',
	) );

	if ( count( $posts ) == 1 )
		return $posts[0];

	return false;
}


function nggap_get_ngg_field_id( $gid, $field, $is_image = false ) {
	$ngg_type = $is_image ? NGGCF_IMAGES : NGGCF_GALLERY;

	# search for object-specific fields first, then generic ones
	foreach ( array( $gid, null ) as $arg ) {
		foreach ( nggcf_get_field_list( $ngg_type, $arg ) as $set )
			if ( $set->field_name == $field )
				return $set->id;
	}
}


function nggap_get_gid( $post_id = null ) {
	if ( ! $post_id ) $post_id = get_the_ID();
	return get_post_meta( $post_id, 'nggap_gid', true );
}


function nggap_embed_gallery( $content ) {
	if ( get_post_type() == 'nggap_gallery' ) {
		$gid = nggap_get_gid();
		$content .= "[nggallery id={$gid}]";
	}
	return $content;
}


function nggap_enable_auto_embed() {
	add_filter( 'the_content', 'nggap_embed_gallery' );
}

