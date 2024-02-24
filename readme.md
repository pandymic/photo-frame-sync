This is a fairly straightforward app built to get some extra life out of the old tablet and smartphone devices I had laying round. It's purpose is to run as a progressive web app (PWA) in fullscreen mode and cycle through a slideshow of family photos creating a cheap digital photo frame. It's pretty quick and dirty. What you see is actually the second version of the app with some added functionality over the original which simply picked a photo at random and refreshed every few minutes. I thought it would be nice to have a shared state between all devices so they're always displaying the same photo.

The entire codebase consists of both a client-facing and server-side components. Their specific functionality is as follows:

## Server Side

The server-side component is a single PHP script called *sync-daemon* run via the command-line live under the `/dist/bin` directory. This script is meant to be registered and run as a systemd service unit. It runs persistently and handles the following in a continous loop (or until something goes wrong...)

1. Select an image at random from the photos folder.
    * If the image has been selected in the last *x* runs select another image.
    * If the image has been optimized use the optimized webp image.
    * If the image has not been optimized, first create the optimized webp image.
2. Update the *photos.json" file with the base 64 encoded Data URL containing the image.
    * Add the image to the latest selected image history to avoid duplicates.   
3. Wait 30 seconds and run again.

### Assumptions & Notes

#### Photos
* Photos live in the folder `/photos` relative to the project directory (i.e. `../../photos` relative to this script)
* It expects photos to be JPEG files that end with either the extension ".jpg" or ".jpeg"
* All photos are assumed to be landscape orientation
* ImageMagick and the PHP Imagick extension must be installed with WebP support
* The Photos folder must have write permissions so that the Images can be cropped, scaled, and compressed (i.e. "optimized") as needed
* Optimized images are saved in the photos folder with the same filename but with the ".webp" extension

#### Sync
* Assumes there is a PHP 8.1 binary at `/opt/remi/php81/root/bin/php` *[Temporary]*
* The current photo is stored as a base 64 encoded Data URL in the *photo.json*
* The *photo.json* file lives in the folder `/dist/photo-frame/sync` relative to the project directory (i.e. `../photo-frame/sync` relative to this script)
* In addition to storing the latest photo this file includes a list of all previously used photos to avoid duplicates
* This can be sliced to *x* length to allow photos to be selected from the pool at random except for any of the last *x* files.
* For best performance the folder `/dist/photo-frame/sync` can be mounted as a tmpfs volume. This keeps the data in memory and eliminates the need for constant disk reads and writes. Below is an example of the fstab entry

<pre>
none    <i>[path-to-project]</i>/dist/photo-frame/sync    tmpfs    defaults,size=16m,uid=apache,gid=apache    0    0
</pre>

#### Service Unit (systemd)

* Below is an exaple service unit configuration (e.g. `/etc/systemd/system/photo-frame-sync.service`)

<pre>
[Unit]
Description=Photo Frame Synchronization
After=network.target

[Service]
User=apache
ProtectSystem=full
PrivateDevices=true
ProtectHome=true
NoNewPrivileges=true
WorkingDirectory=<i>[path-to-project]</i>/dist/bin/
ExecStart=/opt/remi/php81/root/bin/php ./photo-frame-sync
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target
</pre>

## Client Side

The client-side component is a simple single-page web application with a mix of standard HTML, CSS, and Javascript for front-end functionality as well as a very rudimentary API framework in the back-end to handle update requests.

### Front-end

The web app is fairly straightforward. All elements are laid out using Flexbox. Typefaces are loaded via Google Fonts. The page makes a series of Fetch requests to itself at regular intervals in order to retrieve the latest photo, the current local weather, and a greeting message. All data and functionality is contained within the `frameData` object.

Data is polled at various intervals depending on the context. If the underlying value changes between calls it's corresponding DOM element is updated during an Animation Frame request.

The script supports basic tap/click support. Single tap/click toggles the visibility of the information layer. Double tap/click toggles fullscreen mode.

Below is an example of the .webmanifest file that can be used to register this single-page app as a PWA. (e.g. `/dist/photo-frame.webmanifest`)

<pre>
{
  "name": "Synchronized Photo Frame",
  "short_name": "Photo Frame",
  "theme_color": "#50b0d5",
  "background_color": "#50b0d5",
  "display": "fullscreen",
  "orientation": "landscape",
  "scope": "/",
  "start_url": "/",
  "icons": [
    {
      "src": "/favicon.png",
      "type": "image/png",
      "sizes": "192x192"
    }
  ]
}
</pre>

### Back-end

The API currently supports three endpoints.

#### checkPhotoUpdate

This checks the contents of the *photo.json* file and returns the base 64 encoded Data URL of the image source if the image has been updated by the server side sync-daemon since last run.

#### weatherUpdate

Gets the latest weather data from the OpenWeatherMap API. Returns an HTML formatted string including iconography and temperature units.

#### greetingUpdate

Gets the greeting from a server-side configuration file called `greeting.conf`. It's assumed that the file is a valid PHP script with a `return` statement on its last line. It lives in the folder `/etc` relative to the project directory (i.e. `../../etc` relative to this script.) The value returned by this script as a string. If the greeting configuration file cannot be found or the file returns false it is assumed there is no greeting and nothing will be displayed.

### Assumptions & Notes

#### OpenWeatherMap

* A valid auth token must be provided to use the API.
* This token is returned from a server-side configuration file called `openweathermap.conf`. It's assumed that the file is a valid PHP script with a `return` statement on its last line. It lives in the folder `/etc` relative to the project directory (i.e. `../../etc` relative to this script.)
* The file returns an object as follows

<pre>
&lt;?php

return (object)[
  'appId' => '<i>API_KEY_OR_TOKEN_HERE</i>',
];
</pre>
