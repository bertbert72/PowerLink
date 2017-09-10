# PowerLink
Control a Visonic PowerLink 2 via PHP.  This can be used to hook into automation systems, other applications, or just a bit of fun.

## Requirements
* Visonic PowerLink 2
* Web Server with 
  * PHP 5+
  * PHP-Dom

## Installation
Copy the script into the scripts folder of your web server, e.g. /var/www/html/scripts/ and ensure that it has execute permissions.
Edit the script and ensure the following variables match your environment:

Variable | Description
---------|------------
$usr|Username for the PowerLink 2 web page
$pwd|Password for the user
$IP|IP address of the PowerLink 2 device

Note that if desired these can be left at the defaults and overridden via the URL.

## Usage
Pretty simple.  Just call the script with a command, e.g.

http://192.168.0.200/scripts/powerlink.php?command=status

That'll log onto the PowerLink device and return the status of the alarm in an XML format.  The full list of supported commands are:

Command | Description
--------|------------
status|Show the status of the alarm system.  On first call, this shows everything, subsequent updates show changes.
fullstatus|Like status except always show the full status tree.
ministatus|Like status but returns NOCHG if nothing has changed.
logs|The logs from the alarm system in a table format
disarm|Disarm the alarm
armhome|Arm the system in home mode
armaway|Arm the system in away mode
search|This is a device search.  I don't have any devices so not sure what it does...
logout|By default the script keeps the session open to the PowerLink.  This disconnects it.

## Additional options
In addition to the main commands there are a couple of additional items.  These are added to the URL as optional parameters, e.g.

http://192.168.0.200/scripts/powerlink.php?command=status&debug=true

The options are:

Option | Description
-------|------------
debug|Adds some debugging data to the output, pretty basic.  Options are true or false.
logout|If added, will cause the script to disconnect from PowerLink after running the command.  Slows things down though.  Options are true or false.
term|Used with the search option to say what you're searching for.
user|Override the user variable in the script
pass|Override the password variable in the script
ip|Override the ip variable in the script
