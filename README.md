# Better Internal Link Search #

Improve the internal link popup manager in WordPress with time-saving enhancements and features.

__Contributors:__ [Brady Vercher](https://twitter.com/bradyvercher)  
__Requires:__ 4.4  
__Tested up to:__ 4.7  
__License:__ [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html)

Better Internal Link Search improves the default internal link searching feature in WordPress in a number of ways, making it faster and easier to find the content you want to link to.

The most basic feature limits results to posts and pages that contain your search term in the title, rather than returning every post that contains the term in the title *or content field* -- this greatly reduces the number of results on sites with a lot of content and should improve accuracy.

Beyond that simple change are more powerful features that can be customized for your particular site, including creating shortcuts for often-used links and even searching external sites without leaving WordPress!

## Improvements and Features ##

* Search by title only when inserting links in the editor or adding pages to a nav menu.
* Includes terms from any taxonomy in the search results so you can easily link to term archives.
* Scheduled posts are included in search results.
* Text highlighted in the editor when the internal link popup is opened is searched for automatically.
* Adds a shortcut for quickly linking to the homepage. Just type 'home'.
* Provides the ability to create custom shortcuts with a little code.
* Powerful modifiers included for searching additional data sources to make linking fast and easy (media, authors, Wikipedia, GitHub, iTunes, Spotify, Codex).
* Extendable so developers can add their own sources.

## Typeahead Search ##

Instantly see search results for pages, posts, media items or other custom post types on their Manage Posts screens. As soon as you start typing in the search field, the table instantly updates with results for whichever post type you're viewing. Pressing enter or clicking the search button will continue to return posts using the default search algorithm (post titles and content).

## Shortcuts ##

Type `home` or `siteurl` in the search field and quickly get links to those locations. Additional shortcuts can be added with a little bit of code in your functions.php, so if you find yourself linking to a particular URL over and over again, add a shortcut and save yourself some time!

If you don't remember which shortcuts have been registered, type `shortcuts` to list them all.

## Search Modifiers ##

Search modifiers are the most powerful feature of Better Internal Link Search. Although they can be a bit more complex to use, they have the potential to save a lot of time when repeatedly linking to external websites. For example, searching for the term 'interrobang' on Wikipedia would look like this:

`-wikipedia interrobang`

A few simple modifiers have been included by default and should serve as examples for developers that want to create their own or change the syntax. Basic support is built in for searching Wikipedia, iTunes, Spotify, the WordPress plugin directory, the WordPress Codex, GitHub repositories, listing a user's GitHub Gists, and linking to WordPress author archive URLs.

Type `-help` in the search field to view the available modifiers.

## Installation

*Better Internal Link Search* is available in the [WordPress plugin directory](https://wordpress.org/plugins/better-internal-link-search/), so it can be installed from your admin panel.
