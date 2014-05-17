=== Gallery Slice ===
Contributors: honza.skypala
Donate link: http://www.honza.info
Tags: gallery, ajax, image, images
Requires at least: 3.9
Tested up to: 3.9.1
Stable tag: 1.0
License: WTFPL license applies

Slice down galleries on archive pages to preview-only, with link to load full gallery via Ajax

== Description ==

If you run a blog, on which you publish huge galleries with many many many pictures, maybe you want to show only preview of the gallery on archive pages (homepage, categories, archives etc.), so the posts are not too long. The gallery-preview contains a link, which loads the rest of the gallery, via Ajax. Single posts are untouched, they still show full galleries. Well, this plugin brings exactly that, all with standard WordPress gallery.

You can reach similar functionality just by creating two galleries and putting the !--more-- tag in between them -- and the archive page shows only the first gallery, while single post shows the whole gallery. But this plugin has several advantages: it can be set and forget, and then the plugin automatically applies it for every post/gallery published. The preview can be followed by another text, which is still displayed on the archives page. Loading of the rest of gallery is handled by Ajax (fallback to single-post URL if Ajax fails).  

You specify two values: first one threshold -- amount of images, if the gallery exceeds this many, it is sliced into a preview. This is the second value, how many images should be shown in a preview. This allows to have 2 different values for that, e.g. 9 images for preview, but 12 images for threshold, which makes some flexibility, that if the gallery is 10 images big, then it is not shortening by jsut one image. Anyway, you can also specify the same values, if you wish.

These two values are specified on three levels: global (per blog), per post and per gallery tag. You can also specify not to slice a gallery in specific post, resp. not to slice a specific gallery.

Plugin supports several lightbox implementations.

== Installation ==

1.	Upload the full directory to wp-content/plugins
2.	Activate plugin Gallery Slice in plugins administration
3.	You can modify the settings of the behaviour in Settings → Media

== Screenshots ==

1.	Gallery of many pictures sliced-down to only 9 thumbnails. Link "Full gallery →" added via which the user can get the rest of gallery, using Ajax
2.	Plug-in settings

== Changelog ==

= 1.0 =
* Initial release

== License ==

WTFPL license applies

== ToDo's ==
* enable plugin to work in opposite manner - do not automatically slice down galleries, but only on posts / tags where the downto attr is set
* re-check WP-Minify on possibility to use the compiled JS files (internal)
* add loading animation for Ajax
* enable slicing w/ Ajax loading on single posts
