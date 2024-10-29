=== Advanced Cart Recovery ===
Contributors: jkohlbach, RymeraWebCo, rymera01
Donate link:
Tags: woocommerce cart recovery, woocommerce abandoned cart, abandoned cart, abandoned cart recovery, abandoned cart recover, woocommerce recover cart, recover abandoned cart
Requires at least: 4.0
Tested up to: 4.7.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Recover abandoned shopping carts in WooCommerce and gain access to more revenue.

== DESCRIPTION ==

**VISIT OUR WEBSITE:** [Advanced Coupons](https://advancedcouponsplugin.com/?utm_source=WordPressOrg&utm_medium=ACRPlugin&utm_campaign=PluginListing)

**FREE VERSION:**

Advanced Cart Recovery is the best free plugin for recovering abandoned carts in WooCommerce. It automatically emails customers when they abandon their current shopping cart (eg. non-payment of orders, cancelled orders, etc) and asks them to continue with their purchase.

1. Detect when customers abandon cart and save their cart contents
1. Emails customer on a set schedule and provides a link to continue their order
1. Automatically sets up their shopping cart again so they can purchase

Some features at a glance:

**AUTOMATIC RECOVERY EMAILS**

Automatically sends emails on a schedule that you define to prompt customers into recovering their abandoned shopping cart.

**SIMPLE RESTORE CART LINK**

Gives the customer a simple link which restores their cart to the state it was in before they abandoned.

Makes continuing with their order a simple choice of proceeding to payment.

**SEE WHICH CUSTOMERS ARE ABANDONING**

Easy management screen shows exactly who abandoned their order and when. At a glance get a run down of how many cart recoveries are happening and how many are successful.

More amazing statistics are available in the Premium add-on with custom reports!

**ANTI-SPAM LAW COMPLIANT**

Complete blacklist and unsubscribe functionality built-in. View customers who unsubscribed and add additional blacklisted emails manually.

**WORKS OUT OF THE BOX**

Simply setup your email schedules once (WooCommerce->Settings, Cart Recovery tab) and the plugin will do all the heavy lifting for you from that time onwards.

It works straight out of the box with the latest WooCommerce and will work with most themes and plugins.

We're also constantly testing new themes and plugin combinations to ensure maximum compatibility.

**PREMIUM ADD-ON**

Click here for information about the Advanced Cart Recovery Premium add-on:
https://marketingsuiteplugin.com/product/advanced-cart-recovery/

Some premium features at a glance:

1. Add multiple scheduled emails to continue to prompt your customers to recover their cart. Not everyone responds on the first try!
1. Amazing new reports focused around abandoned cart recovery and how much money you are making by having this system in place
1. Generate personalised coupons on the fly in your emails and offer them to people as a deal sweetener
1. Exclude specific user roles from receiving abandoned cart reminder emails

We also have a whole bundle of marketing automation plugins available at:
https://marketingsuiteplugin.com

== Installation ==

1. Upload the `advanced-cart-recovery/` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Setup your scheduled emails and other settings under WooCommerce->Settings, Cart Recovery tab
1. View abandoned carts under WooCommerce->Abandoned Carts
1. ... Profit!

== Frequently asked questions ==

We'll be publishing a list of frequently asked questions in our knowledge base soon.

== Screenshots ==

Plenty of amazing screenshots for this plugin and more over at:
https://marketingsuiteplugin.com/product/advanced-cart-recovery/

== Changelog ==

= 1.3.2 =
* Improvement: Add compatibility with upcoming WooCommerce version 2.7.0
* Improvement: Add email lookup or complete in the field "Manually Unsubscribe Email Address"
* Bug Fix: When an order fails on payment timeout an ACR post can still be created

= 1.3.1 = 
* Improvement: Improve on how the data is displayed on view schedule dialog box
* Improvement: Adjust wording on abandoned cart list view when there is no abandoned carts present
* Improvement: Improve the email schedules styling
* Improvement: Improve the black list table styling
* Improvement: Add help link in the plugin listings
* Bug Fix: Non well formatted numeric value encountered error on blacklist entry
* Bug Fix: Copy link, on restore link meta box of acr cpt not working on firefox
* Bug Fix: Errors on debug log
* Bug Fix: If customer completes an order, remove all abandoned cart entries of that customer that have not sent emails yet

= 1.3.0 =
* Feature: Multi site compatibility
* Feature: Plugin tour
* Improvement: Add hover help buttons to email edit form
* Improvement: Product Bundles integration update
* Improvement: Composite Products integration update
* Bug Fix: Check plugin pre-requisites properly
* Bug Fix: Properly load settings defaults on activation
* Bug Fix: Fix wording when cart ID is not found during recovery

= 1.2.0 =
* Feature: Product Add-ons Integration
* Feature: Add new option called "Allow Recovery With Different Email" in settings
* Improvement: Tweak email validator to accept "email+2@email.com" format
* Improvement: When "Wrap emails with WooCommerce email header and footer?" option is checked, add "Heading text" field
* Improvement: Add a status filter on the "Abandoned Carts" cpt entry listing
* Improvement: Add new column for blacklist table on when was the email added
* Improvement: Show date on CPT edit screen
* Improvement: Display admin notice if plugin dependencies are not installed on plugin activation
* Improvement: Tidy up code.
* Bug Fix: Tidy up error message when adding already blacklisted email
* Bug Fix: When order is trashed, delete also its equivalent "Abandoned Cart" entry
* Bug Fix: If an order is changed status from completed to a status that is considered as "Abandoned Order Status" then this order should be candidate for re adding of entry to abandoned cart cpt
* Bug Fix: Bug on adding/editing "Email Schedules"
* Bug Fix: Restore only if the ACR entry is in "Not Recovered" status
* Bug Fix: Make missing wp cron notice dismissable
* Bug Fix: When updating an email, content is not recognized by validator when wysiwyg is in text mode

= 1.1.0 =
* Feature: Integrate Composite Products plugin
* Feature: Integrate Product Bundles plugin
* Improvement: If a user is deleted, delete also any entries associated with it to avoid issues
* Improvement: Clean up plugin options on un-installation

= 1.0.1 =
* Feature: Add Order number to the cart list view
* Improvement: Add premium version upsell graphics
* Improvement: Improve send email function to allow ajax request
* Improvement: Add hooks and filters needed in premium version
* Improvement: Add title attribute to "Preview" and "Edit" button
* Bug Fix: Fatal error on plugin activation
* Bug Fix: Deprecated non-static call

= 1.0.0 =
* Initial version

== Upgrade notice ==

There is a new version of Advanced Cart Recovery available.
