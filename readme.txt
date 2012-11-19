=== Better Internal Link Search ===
Contributors: blazersix, bradyvercher
Tags: links, internal links, search, editor, nav menus
Requires at least: 3.4
Tested up to: 3.4.2
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Improve the internal link popup functionality with time saving enhancements and features.

== Description ==

Better Internal Link Search improves the default internal link searching feature in a number of ways, making it faster and easier to find the content you want to link to.

The most basic feature limits results to posts and pages that contain your search query in their titles, rather than returning every post that contains the query in the title *or content fields*--this greatly reduces the number of results on sites with a large amount of content and should improve accuracy. In addition, term archives are included in the results so you can more easily link to them.

Beyond those simple changes are more powerful features that can be customized for your particular site, including creating shortcuts and even searching external sites without leaving WordPress!

= What's New? =

= Instant Search =

Instantly see search results for pages, posts, media items or other custom post types on their Manage Posts screens. As soon as you start typing in the search field, the table instantly updates with results for whichever post type you're viewing. Pressing enter or clicking the search button will continue to return posts using the default search algorithm (post titles and content).

= Features =

* Search by post or page title when adding links to the editor or adding pages to a nav menu.
* Includes terms from any taxonomy in the search results so you can easily link to term archives.
* Scheduled posts are included in search results.
* Text highlighted in the editor when opening the internal link popup is searched for automatically.
* Adds a shortcut for quickly linking to the homepage. Just type 'home'.
* Provides the ability to create custom shortcuts with a little code.
* Powerful modifiers included for searching additional data sources to make linking fast and easy (Wikipedia, GitHub, iTunes, Spotify, Codex).
* Extendable so developers can add their own sources.

= Shortcuts =

Type `home` or `siteurl` in the search field and quickly get links to those locations.  Additional shortcuts can be added with a little bit of code in your functions.php, so if you find yourself linking to a particular URL over and over again, add a shortcut and save yourself some time!

= Search Modifiers =

Search modifiers are the most powerful feature of Better Internal Link Search. Although they can be a bit more complex to use, they have the potential to save a lot of time when repeatedly linking to external websites. For example, searching for the term 'interrobang' on Wikipedia would look like this:

`-wikipedia interrobang`

A few simple modifiers have been included by default and should serve as examples for developers that want to create their own or change the syntax. Basic support is built in for Wikipedia, iTunes, Spotify, the WordPress plugin directory, the Codex, GitHub repositories, listing a user's GitHub Gists, and linking to author archive URLs.

Type `-help` in the search field to view the available modifiers.

== Installation ==

Installing Better Internal Link Search is just like installing most other plugins. [Check out the codex](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) if you have any questions.

== Frequently Asked Questions ==

= How do I know which shortcuts are available? =
Type `shortcuts` in the search field and all of your registered shortcuts will be listed.

= How do I know which modifiers are available? =
Type `-` or `-help` in the search field and the search modifiers will be listed with their syntax and a brief description about what they do.

== Screenshots ==

1. Standard search results. The "Contact Us" page is nowhere in sight and in fact, is about the 95th page in this particular list.
2. Results with Better Internal Link Search activated. The text selected in the editor was automatically searched and matches are based on title only.
3. Shortcuts demonstration.
4. Search modifiers help list.
5. A search modifier in action.

== Changelog ==

= 1.2 =
* Added instant search on Manage Posts screens.
* Reorganized the plugin structure.
* Fixed a bug with paging for local search modifiers.
* Added "-media" search modifier by Erik Larsson (Twitter: @e_larsson).

= 1.1.2 =
* Fixed a bug that didn't allow builtin search modifiers to be disabled.
* Added an option on the Writing Settings screen to disable the automatic searching of text selected in the editor when the Internal Linking popup is activated, so that it doesn't cause a delay on sites with a lot of content.
* Added an upgrade routine to setup default settings and store the current version number for future upgrades.
* Added uninstall.php to remove options if the plugin is uninstalled.

= 1.1.1 =
* Fixed bug preventing link popup from opening in HTML mode.

= 1.1 =
* Include term archives in results.
* Include scheduled posts in results.
* Debounced the search field.
* Added multiple hooks for modifying results.
* Added shortcuts feature.
* Added search modifiers.

= 1.0 =
* Initial release.