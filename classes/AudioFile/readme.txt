

********************************************************************************
 Class      AudioFile
 Version:   0.5.1
 Date:      09/09/2003
 Author:    michael kamleitner (mika@ssw.co.at)
            reto gassmann (gassi@gassi.cx) - additional mp3-code
	    chris snyder (csnyder@chxo.com) - additional ogg-vorbis & id3v2-code	  
 Thanks to: matthieu mary, kumar mcmillan
 WWW:	    http://www.entropy.at/forum.php?action=thread&t_id=15 
            (suggestions, bug-reports & general shouts are welcome)
 Copyright: copyright 2003 michael kamleitner, reto gassmann, chris snyder
 
            This file is part of classAudioFile.

            classAudioFile is free software; you can redistribute it and/or modify
            it under the terms of the GNU General Public License as published by
            the Free Software Foundation; either version 2 of the License, or
            (at your option) any later version.

            classAudioFile is distributed in the hope that it will be useful,
            but WITHOUT ANY WARRANTY; without even the implied warranty of
            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
            GNU General Public License for more details.

            You should have received a copy of the GNU General Public License
            along with classAudioFile; if not, write to the Free Software
            Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

********************************************************************************

********************************************************************************
 General
********************************************************************************

This class was basically built to recognize attributes of audio-files.
At the moment WAV, AIFF, .MP3 and .OGG are supported. The attributes which 
are extraced from the audio-file given in loadFile ($filename) are:

	// general information

	$wave_id		type of the file-header ("RIFF" in case of 
	                        .wav  & .aif, "MPEG" in case of mp3-files)
	$wave_type		includes "WAVE", "AIFF" or the used mp3-
				version (like "MPEG Version 2 (ISO/IEC 13818-3)")
	$wave_size		filesize
	$wave_compression	in case of .wav-files their are about 10 
				different compressions (see function getCompression).
				.aif-files don't use this attributes, mp3-
				files display the mpeg-layer
	$wave_channels		mono/stereo
	$wave_framerate		sample-rate
	$wave_byterate		bytes per second (.wave & .aiff) or bits per second (.mp3)
	$wave_bits		resolution of one sample (8/16...) - not used for .mp3	
	$wave_filename		the filename
	$wave_length		length in seconds

	// id3v1-tags

	$id3_tag		true/false if id3-tags exist
	$id3_title		
	$id3_artist
	$id3_album
	$id3_year
	$id3_comment
	$id3_genre
	
	// id3v2-tags
	
	id3v2			false if id3v2-tags do not exist
	id3v2->TIT2		title
	id3v2->TPE1		artist
	id3v2->TOPE		original artist
	id3v2->TALB		album
	id3v2->TYER		year
	id3v2->COMM		comment
	id3v2->TCOM		composer
	id3v2->TCON		genre
	id3v2->TENC		encoder
	id3v2->WXXX		website
	
	Note: Many files will have additional id3v2 tags (aka frames), 
	    see http://www.id3.org/id3v2.4.0-frames.txt for details.

	// ogg-tags
	
	vorbis_comment->TITLE
	vorbis_comment->ARTIST
	vorbis_comment->ALBUM
	vorbis_comment->DATE
	vorbis_comment->GENRE
	vorbis_comment->COMMENT
	
	Warning: These values may be arrays!  
	    The Vorbis spec allows multiple instances of any tag (in 
	    case of more than one artist, for example).
	    If this is the case in your file, vorbis_comment->ARTIST will be
	    an array of artist values. 


********************************************************************************
 Methods - how to use
********************************************************************************

To load a Audio-File just use the method  "loadFilename", for a simple 
check what attributes were extracted use "printSampleInfo":

$AF = new AudioFile;
$AF->loadFile($filename);
$AF->printSampleInfo();

To visualize a wave-file use the method "getVisualization":

$AF->getVisualization ($outputfilename);

At the moment only wave-files at 8/16/24/32 bit resolution and with 1 or 2 
channels are supported. The output-format is a portable network graphic
(png) or jpeg. If you don't want to generate graphic-files, but want
to send the output direct to the browser, just delete the parameter $output
in the CreatePng-statement. 

You can manipulate the look of the outcoming graphic
with these parameters:

	$visual_graph_color [string, "#RRGGBB"]
	$visual_background_color [string, "#RRGGBB"]
	$visual_grid_color [string, "#RRGGBB"]
	$visual_border_color [string, "#RRGGBB"]
	$visual_grid [true|false]
	$visual_border [true|false]
	$visual_width [in pixels, numeric]
	$visual_height [in pixels, numeric]
	$visual_fileformat ["jpeg" or "png"]
	$visual_graph_mode [0 or 1]

********************************************************************************
 test.php
********************************************************************************

The test.php displays all .wav, .aif, .mp3 & .ogg-files in the current directory.
click on a filename to load it into a classAudioFile-instance and 
to display the attributes.

********************************************************************************
 Links
********************************************************************************

This class was written for a webpage that supports its users to
upload samples, which are automatically processed [see it at 
http://www.entropy.at].

You can download this class at:

	http://www.entropy.at/forum.php?action=thread&t_id=15 
	                      (home of classAudioFile, including a
	                       board f. discussion & suggestions...)
	http://www.phpclasses.org
	http://php.resourceindex.com
	http://www.hotscripts.com
	http://freshmeat.net/projects/phpaudiofile/?topic_id=809%2C120%2C96%2C914

********************************************************************************
 Changes
********************************************************************************

V 0.5.1 - division by zero-error, which occured on some mp3s fixed, however,
          vbr isn't still supported!
        - chris snyder's support for ogg-vorbis and id3v2-tags added!
        - chris snyder's patch of the visualization-function added!
        - applied the GPL (general public license)!
V 0.5   - visualization output is now fully scalable with the attributes
          visual_width & visual_height
        - visualization file-format can be chosen between JPEG and PNG
        - fixed an error which lead to wrong bitrate/length-indication with mp3s
        - test.php now works with "register_globals = Off", the main class worked 
          with this setting before as well
V 0.4   - added basic visualization-functioniality for uncompressed wave-
  	  files. 
        - initial release on freshmeat.net
V 0.3   - id3v1-tags are accessible via the object-attributes
V 0.2   - included mp3-support (code by reto gassmann)
V 0.1   - initial release

********************************************************************************
 Known issues
********************************************************************************

 - length of the samples is not calculated correctly for .aif-files
 - variable bitrate (VBR)-mp3s are not recognized correctly


