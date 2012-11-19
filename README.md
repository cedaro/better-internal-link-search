# Better Internal Link Search #

On WordPress sites with a large amount of content, searching for a particular post or page can be difficult because WordPress matches the title and content fields. Better Internal Link Search helps remedy that in addition to providing a number of other enhancements.

The most basic feature limits results to posts and pages that contain your search query in their titles, rather than returning every post that contains the query in the title *or content fields*--this greatly reduces the number of results on sites with a large amount of content and should improve accuracy. In addition, term archives are included in the results so you can more easily link to them.

Beyond those simple changes are more powerful features that can be customized for your particular site, including creating shortcuts and even searching external sites without leaving WordPress!

## Improvements and Features ##

* Search by post or page title when adding links to the editor or adding pages to a nav menu.
* Includes terms from any taxonomy in the search results so you can easily link to term archives.
* Scheduled posts are included in search results.
* Text highlighted in the editor when opening the internal link popup is searched for automatically.
* Adds a shortcut for quickly linking to the homepage. Just type 'home'.
* Provides the ability to create custom shortcuts with a little code.
* Powerful modifiers included for searching additional data sources to make linking fast and easy (Wikipedia, GitHub, iTunes, Spotify, Codex).
* Extendable so developers can add their own sources.

### Instant Search (New!) ###

On Manage Posts screens, typing in the search field will filter the list of posts instantly, making it much quicker to find a matching post, page, or other custom post type. Pressing enter or clicking the search button will continue to return posts using the default search algorithm (post titles and content).

## Shortcuts ##

Type `home` or `siteurl` in the search field and quickly get links to those locations.  Additional shortcuts can be added with a little bit of code in your functions.php, so if you find yourself linking to a particular URL over and over again, add a shortcut and save yourself some time!

If you don't remember which shortcuts have been registered, type `shortcuts` to list them all.

## Search Modifiers ##

Search modifiers are the most powerful feature of Better Internal Link Search. Although they can be a bit more complex to use, they have the potential to save a lot of time when repeatedly linking to external websites. For example, searching for the term 'interrobang' on Wikipedia would look like this:

`-wikipedia interrobang`

A few simple modifiers have been included by default and should serve as examples for developers that want to create their own or change the syntax. Basic support is built in for searching Wikipedia, iTunes, Spotify, the WordPress plugin directory, the WordPress Codex, GitHub repositories, listing a user's GitHub Gists, and linking to WordPress author archive URLs.

Type `-help` in the search field to view the available modifiers.

## Credits ##

Built by Brady Vercher ([@bradyvercher](http://twitter.com/bradyvercher))  
Copyright 2012  Blazer Six, Inc.(http://www.blazersix.com/) ([@blazersix](http://twitter.com/BlazerSix))