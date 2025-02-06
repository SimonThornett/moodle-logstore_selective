## Tool Logstore Selective

Description
------------------------

The purpose of this plugin is to create a logstore that allows for only a selective list of events to be logged.
The benefit of this is that the the current logstore_standard can be very large and difficult to query without frequent
cleanups.

This plugin is able to run alongside the standard plugin, however the settings page for the plugin lists all available
event triggers (all disabled by default) and allows you to specify exactly which events you want to log into the 
table 'logstore_selective_log'.

The selective logstore uses all standard reports and as such can be viewed from the UI.

Contributing and support
------------------------

Issues, and pull requests using github are welcome and encouraged!

If you would like commercial support or would like to sponsor additional improvements
to this plugin please contact us:

https://www.catalyst-eu.net/contact-us

Crafted by Catalyst IT
----------------------

This plugin was developed by Catalyst EU:

https://www.catalyst-eu.net/