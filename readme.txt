=== Better Internal Link Search ===
Contributors: blazersix, bradyvercher
Tags: links, internal links, search, editor, nav menus
Requires at least: 3.3.2
Tested up to: 3.3.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Search by post or page title when adding links into the editor or adding pages to a nav menu.

== Description ==

On sites with a large amount of content, searching for a particular post or page can be difficult because WordPress matches the title and content fields. Better Internal Link Search limits matches to the title field only when creating links in the editor via the "Insert/edit link" popup or when searching for a page to add to a nav menu. Typically when you're linking content, the title of the post or page is known, so matching the content field becomes unnecessary and, at times, tedious.

As an example, if there are 50 pages linking to a "Contact" page and you'd like to create a new link to the "Contact" page, all 50 of those pages would turn up when searching for "contact". With Better Internal Link Search activated, only the pages with "contact" in their title will show up in the results, making it easier to find the desired page.

For a slight producivity boost, if any text is selected in the editor when the link button is clicked on the editor toolbar, a search will be performed automatically for a post or page with that title.

Simple perhaps, and maybe a little esoteric, but it can save quite a bit of frustration when working on sites with a large number of pages.

== Installation ==

Installing Better Internal Link Search is just like installing most other plugins. [Check out the codex](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) if you have any questions.

== Screenshots ==

1. Standard search results. The "Contact Us" page is nowhere in sight and in fact, is about the 95th page in this particular list.
2. Results with Better Internal Link Search activated. The text selected in the editor was automatically searched and matches are based on title only.

== Changelog ==

= 1.0 =
* Initial release.