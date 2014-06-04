=== Gallery Slice ===
Contributors: honza.skypala
Donate link: http://www.honza.info
Tags: gallery, ajax, image, images
Requires at least: 3.9
Tested up to: 3.9.1
Stable tag: 1.2
License: WTFPL license applies

Slice down galleries on archive pages to preview-only, with link to load full gallery via Ajax

== Description ==

The standard gallery in WordPress has one limitation -- it always shows all the pictures in the gallery. I know there are alternative galleries available, which support pagination, but I never thought this is a good approach. The reader never wants to see n-th page of the gallery -- if the first page got his attention, then he wants to see all of the rest, not split into pages anymore. At least, this is how I see it.

So, if you run a blog, on which you publish huge galleries with many many many pictures, maybe you want to show only preview of the gallery on archive pages (homepage, categories, archives etc.), so the individual posts are not too long. The gallery-preview then contains a link, which loads the rest of the gallery if the reader wishes so, via Ajax. Well, this plugin brings exactly that, all with standard WordPress gallery.

Single posts are untouched, they still show full galleries, at least for now.

You can reach similar functionality just by creating two galleries and putting the &lt;!--more--&gt; tag in between them -- and the archive page shows only the first gallery, while single post shows the whole gallery. But, this plugin has several advantages: it can be set and forget, and then the plugin automatically applies it for every post/gallery published. If the gallery is followed by another text, then this text is still displayed on the archives page. Loading of the rest of gallery is handled by Ajax (with fallback to single-post URL if Ajax fails).  

In options, you specify two values: first one threshold -- amount of images, if the gallery exceeds this many, it is sliced into a preview. This is the second value, how many images should be shown in a preview. This allows to have 2 different values for that, e.g. 9 images for preview, but 12 images for threshold, which makes some flexibility, that if the gallery is 10 images big, then it is not shortening by jsut one image. Anyway, you can also specify the same values, if you wish.

These two values are specified on three levels: global (per blog), per post and per gallery tag. You can also specify not to slice a gallery in specific post, resp. not to slice a specific gallery.

Plugin supports several lightbox implementations.

== Installation ==

1.	Upload the full directory to wp-content/plugins
2.	Activate plugin Gallery Slice in plugins administration
3.	You can modify the settings of the behaviour in Settings → Media

== Screenshots ==

1.	Gallery of many pictures sliced-down to only 9 thumbnails. Link "Full gallery →" added via which the user can get the rest of gallery, using Ajax
2.	Plug-in settings

== Frequently Asked Questions ==

You can specify the options on three levels -- globally, per post or even per individual gallery.

For global options, please go to Settings → Media, section Gallery Slice

For per post options, when you create a new post, there will be a new block Gallery Slice, usually displayed in the bottom right. The settings you specify here apply only to the actual post, not to the other ones.

For specifying options per individual gallery, you use new attributes in the gallery shortcode:

* sliceto -- the amount of thumbnails, to which the gallery should be sliced to
* noslice -- do not slice down this gallery (even if threshold reached). Attribute without value
* link2full -- text for the hyperlink to load rest of the gallery

Examples:

<code>[gallery noslice]
[gallery sliceto="6" link2full="Show the rest"]</code>

== Changelog ==

= 1.2 =
* Loading animation option integrated with WordPress Media Library
* Added some missing strings to i18n
* Smaller fixes and optimizations
= 1.1 =
* Loading animation added; user can specify his own loading gif in the options
= 1.0 =
* Initial release

== License ==

WTFPL license applies

== ToDo's ==
* enable plugin to work in opposite manner - do not automatically slice down galleries, but only on posts / tags where the downto attr is set
* re-check WP-Minify on possibility to use the compiled JS files (internal)
* enable slicing w/ Ajax loading on single posts
