NextGEN Gallery as Post
=======================

Treat NextGEN galleries as regular posts (of a custom type).

This allows for them to e.g. automatically show up on post streams, be searched
through, have special taxonomies, and so on. This works by assigning a "proxy
post" to each gallery and keeping that proxy's data up to date.


Installation
============

1. Upload `nextgen-gallery-as-post` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Optionally, in your own `functions.php`, set up some hooks or enable
some of the plugin's ready-made hooks.


Hooks
=====

* `nggap_create_gallery`: (to be documented)
* `nggap_update_gallery`: (to be documented)
* `nggap_delete_gallery`: (to be documented)


Ready-made hooks
================

`nggap_enable_auto_embed()`: (to be documented)

