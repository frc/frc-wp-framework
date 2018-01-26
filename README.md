# frc-wp-framework
Frantic WP Framework. Experimental features and other helpers to aid development.

Under heavy development.
**DO NOT USE YET.**

# Table of contents

* [Custom post type system](#custom-post-type-system)
* [Component system](#component-system)
* [Helper functions](#helper-functions)



# Instructions

## Requirements

* PHP 7.1
* Advanced custom fields (and pro to use the component system as it is based on the flexible field type)

## Introduction

The FRC WP Framework consists of multiple different systems. They are:

* Custom post type definition system
* Component system for the custom post type
* Normal wp_post and wp_query wrappers for caching and easy of use purposes.
* Random helpers:
    * Caching helpers
    * Rendering helpers
    * Other stuff...

## Custom post type system

The custom post type system is made for the ease of just describing your custom post type in php class form. It includes the posts acf_fields and categories etc.

A custom post type can just be created by defining a class in a php file in the templates inc -dir (or where ever you store these kind of files, the directory doesn't really matter) that inherits from the `FRC_Post_Base_Class`.

Like so:
```
class My_Custom_Post_Type inherits FRC_Post_Base_Class {

}
```

That's it. You've got a custom post type. It doesn't contain anything that interesting, but it is a basic run of the mill post -like post type.

You can use these objects just like regular WP_Post objects. These objects are fetched with the `frc_get_post()` -helper function. Like so:

```
$post = frc_get_post($post_id);

echo $post->ID;
```

Now that post type has been created. You can define some schemas to it. You can assign taxonomies for the custom post type.

Like so:
```
class My_Custom_Post_Type inherits FRC_Post_Base_Class {
    public $taxonomies = [
        'my_custom_taxonomy',
        'another_my_custom_taxonomy'
    ];
}
```

If you want to define more specifically the taxonomys arguments you can just:
```
... 

public $taxonomies = [
    'my_custom_taxonomy',
    'another_my_custom_taxonomy' => [
        'hierarchical' => true,

        ...

        additional arguments here
    ]
];

...
```
These arguments follow the basic wordpress [register_taxonomy](https://codex.wordpress.org/Function_Reference/register_taxonomy) functions arguments. There are default values preset all the time and these arguments just overwrite those in the defaut arguments.

You can also define similiarly the [register_post_type](https://codex.wordpress.org/Function_Reference/register_post_type) arguments with the `$args` -member:
```
class My_Custom_Post_Type inherits FRC_Post_Base_Class {
    public $args = [
        'description' => 'Description here',
        'has_archive' => true,

        ...

        additional arguments here
    ];
}
```

Similiarly these arguments also just overwrite the default ones.

Custom post types also has the ability to define ACF fields to be defined in the schema. Those can be defined by defining the `$acf_schema` -member in the class The schema follows the `acf_add_local_field_group`'s field -arguments.

Like so:
```
class My_Custom_Post_Type inherits FRC_Post_Base_Class {
    public $acf_schema = [
        [
            'name' => 'a_field',
            'lable' => 'This is the label',
            'type' => 'text'
        ],
        [
            'name' => 'another_name_field',
            'lable' => 'This is the label of the second field',
            'type' => 'text'
        ],

        ...

        additional arguments here
    ];
}
```

These fields appear in the custom post types add/edit page instantly. If you want some more fine tuned control, you can just define the `$acf_schema_groups` -member. This will go to the `acf_add_local_field_group` -function as is and it will overwrite the usage of `$acf_schema`.

You can also call `save()` -member functions to save the post data and the changed ACF field values.

The Custom post types also have some member functions that are called in different phases. For instance:
`saved()` -function is called when ever `save()` -method has been called.

The custom post type event member functions are:
* `init()` (Called after the custom post type object has been initialized)
* `prepared()` (Called after the custom post type objects preparing phase is done)
* `saved()` (Called whenever `save()` has been called)

These event member functions can be used to add additional functionality to the post type class.

Misc:
* Optionally you can define the `$included_acf_fields` -member to hold all the `acf_schema` field names that the post object loads up in when the object is loaded.
* `$served_from_cache` -member is defined true or false when the object is retrieved from cache. You can use this for debugging.


## Component system

Components are a bit more complex. 

More info here...


## Helper functions

```

```