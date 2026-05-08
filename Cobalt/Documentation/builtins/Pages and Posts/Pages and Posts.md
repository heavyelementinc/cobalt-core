## Editor
### Page Title
This is the Page title used in the &lt;title&gt; tag as well as the landing 
splash section (and, if no OpenGraph title is set, it's used there, too.)

### URL Slug
This is the url "path" that your site is accessed through. It must not start 
with a "/" but it may contain slashes!

Note that there may be issues with route conflicts as this is the last route 
loaded.

### Body
The main content of the page or post.

### Visibility
 * **"Private"** — Post is unreadable by the public (even by the author).
 * **"Draft"** — Shareable using a "Preview Key" on the settings page.
 * **"Unlisted"** — Readable by the general public, not listed on the public Post index.
 * **"Public"** — Readable by the general public, included on the public Post index.

### Live Date
The live date controls when this page is visible. It is also used as the "Last 
Updated" field in the site map.

### Summary
This is a short summary that is displayed in link previews and in search results

### Splash Panel Image


### Splash Panel Layout


### Splash Direction


### Subtitle


### CTA (Call To Action)


### CTA HREF

## Metadata
### OpenGraph Title
This is the title of this page as seen through link previews on social meida.

If no value is set, this will default to the title of this post.

### Related Title
The headline of the Related Content section. The default value can be updated in 
the settings.

### Tags & Keywords
Tags are used to find how "related" two articles are. The more tags two posts 
have in common, the more "related" they are.

If there aren't enough "Related Posts" to fill the section (usually 3) then the 
most recent articles by this author will be listed.

### Flags
**Credit Publication on Fediverse** — Enabling this flag will override credit this
application versus rather than the author's Mastodon handle.

**Include tag links in post footer** — 

## Sidebar Content
### Aside Content
What content should live in the sidebar?

### Include Sidebar
Should this page show a sidebar?

### Aside Positioning
 * **Sidebar Left** — 
 * **Sidebar Right** — 
 * **Aside as Footer** — 
 * **Sticky** — 
 * **Include Table of Contents** — 
 * **TOC Before Content** — 

## Author Details
### Section Title
This is your chance to build credibility with the reader. Explain who you are, what you do, and why your opinion matters!

### Bio

### Author
Choose a user to be credited as the author of this page.

### Include Bio
Should this page show a biography of the author?

### Biography CTA Button Label

### Misc Author Settings

## Settings

### Splash Image Alignment

### Preview Key 
The Preview Key is used to grant outside access to Draft posts. You can share a 
preview key with as many people as you'd like.

Only one Preview Key can be issued at a time.

Provisioning a new Preview Key will invalidate the previous key.

**NOTE:** This document must be set to 'Draft' visibility for a Preview Key to 
grant access. 'Private' posts are always inaccessible.

### Style
Add valid CSS in this box. It will be injected into the page.

### Include in Route Group
If this is off, this page will not be included in the route group.

### Route Group
Pages may only be added to `web context` route groups. Listed above are all web 
context route groups in your application.

### Route Link Label
This is the visible text of the hyperlink. If nothing is defined here, the title 
of your page will be used.

### Route Order
This is the order that your link should fall in within the group. Note that it's
possible to use negative numbers but we recommend against using them since it 
quickly becomes confusing.

**NOTE:** "Route Groups" are used to generate navigation links. This section 
lets you dynamically add your page to any predefined Route Group (thus showing 
your page as a navigation link).

### Main Navigation
Display the main navigation in the header of your app on this page.

### Flags
 * **Access Exclusive to Users** — means that a user must be logged in to access this page
 * **Exclude Page from Sitemap** — will prevent this page from appearing on the sitemap
 * **Do Not Show Related Pages** — will hide the "Related Pages" section that normally appears just above the footer
 * **Hide View Count** — 
 * **Read Time Manually Set** — 
 * **Include Permalink in URL** — 
 * **Hide Webmention Interactions** — 

Flags are miscellaneous settings relative to this page. They control access and 
visibilty for the page among other things.


