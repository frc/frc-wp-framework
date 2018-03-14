<?php
namespace FRC;

/*
Create this just so that we can wrap regular wp_post's
through the system and as this is not a custom post type,
there is no need to put that through the registering machine.
*/
class Post extends Post_Base_Class {
public $custom_post_type = false;
}
