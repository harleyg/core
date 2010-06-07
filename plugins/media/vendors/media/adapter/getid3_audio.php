<?php
/**
 * Getid3 Audio Media Adapter File
 *
 * Copyright (c) 2007-2010 David Persson
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP version 5
 * CakePHP version 1.2
 *
 * @package    media
 * @subpackage media.libs.media.adapter
 * @copyright  2007-2010 David Persson <davidpersson@gmx.de>
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://github.com/davidpersson/media
 */

/**
 * Getid3 Audio Media Adapter Class
 *
 * @package    media
 * @subpackage media.libs.media.adapter
 * @link       http://getid3.sourceforge.net/
 */
class Getid3AudioMediaAdapter extends MediaAdapter {
	var $require = array(
		'mimeTypes' => array(
			'application/ogg',
			'audio/ogg',
			'audio/mpeg',
			'audio/ms-wma',
			'audio/ms-asf',
			'audio/realaudio',
			'audio/pn-realaudio',
			'audio/pn-multirate-realaudio',
			'audio/wav',
			'audio/riff',
			'audio/wavpack',
			'audio/musepack', /* MPC */
			'audio/aac',
			'audio/mp4', /* AAC */
			'audio/m4a', /* AAC */
			'audio/m4b', /* AAC */
			'audio/ac3',
			'audio/aiff',
			'audio/ape',
			'audio/shorten',
			'audio/basic',
			'audio/midi',
			'audio/flac',
			'audio/voc',
			'audio/s3m',
			'audio/xm',
			'audio/it',
			'audio/mod',
			'audio/matroska',
			/* Not in freedesktop.org database */
			'audio/pac',
			'audio/bonk',
			'audio/dts',
			'audio/cda',
			/*
			 * This is a reminder since audio Media shouldn't have 'application/octet-stream'
			 * MIME type.
			 *
			 * LA (Lossless Audio), OptimFROG, TTA, LiteWave,
			 * RKAU, AVR (Audio Visual Research) and some Ogg files.
			 */
			'application/octet-stream',
		  ),
		'imports' => array(
			array('type' => 'Vendor', 'name' => 'getID3', 'file' => 'getid3/getid3.php')
		),
		'extensions' => array('gd'),
	);

	function initialize($Media) {
		if (isset($Media->objects['getID3'])) {
			return true;
		}

		if (!isset($Media->file)) {
			return false;
		}

		$Object = new getID3();
		$Object->encoding = 'UTF-8';
		$Object->analyze($Media->file);

		if (isset($Object->info['error'])) {
			return false;
		}

		getid3_lib::CopyTagsToComments($Object->info);

		$Media->objects['getID3'] =& $Object;
		return true;
	}

	function artist($Media) {
		if (isset($Media->objects['getID3']->info['comments']['artist'][0])) {
			return $Media->objects['getID3']->info['comments']['artist'][0];
		}
		if (isset($Media->objects['getID3']->info['comments']['author'][0])) {
			return $Media->objects['getID3']->info['comments']['author'][0];
		}
	}

	function title($Media) {
		if (isset($Media->objects['getID3']->info['comments']['title'][0])) {
			return $Media->objects['getID3']->info['comments']['title'][0];
		}
	}

	function album($Media) {
		if (isset($Media->objects['getID3']->info['comments']['album'][0])) {
			return $Media->objects['getID3']->info['comments']['album'][0];
		}
	}

	function year($Media) {
		foreach (array('year', 'date', 'creation_date') as $field) {
			if (!isset($Media->objects['getID3']->info['comments'][$field][0])) {
				continue;
			}
			$date = $Media->objects['getID3']->info['comments'][$field][0];

			if ($field !== 'year') {
				$date = strftime('%Y', strtotime($date));
			}
			if ($date) {
				return $date;
			}
		}
	}

	function duration($Media) {
		if (isset($Media->objects['getID3']->info['playtime_seconds'])) {
			return $Media->objects['getID3']->info['playtime_seconds'];
		}
	}

	function track($Media) {
		if (isset($Media->objects['getID3']->info['comments']['track_number'][0])) {
			return $Media->objects['getID3']->info['comments']['track_number'][0];
		}
		if (isset($Media->objects['getID3']->info['comments']['tracknumber'][0])) {
			return $Media->objects['getID3']->info['comments']['tracknumber'][0];
		}
	}

	function samplingRate($Media) {
		if (isset($Media->objects['getID3']->info['audio']['sample_rate'])) {
			return $Media->objects['getID3']->info['audio']['sample_rate'];
		}
	}

	function bitRate($Media) {
		if (isset($Media->objects['getID3']->info['ogg']['bitrate_nominal'])) {
			return $Media->objects['getID3']->info['ogg']['bitrate_nominal'];
		}
		if (isset($Media->objects['getID3']->info['bitrate'])) {
			return $Media->objects['getID3']->info['bitrate'];
		}
	}

	function convert($Media, $mimeType) {
		if (Media::name(null, $mimeType) === 'Image') {
			$coverArt = $this->__coverArt($Media);

			if (!$coverArt) {
				return false;
			}

			$resource = @imagecreatefromstring($coverArt);

			if (!is_resource($resource)) {
				return false;
			}

			$Image = Media::factory(array('gd' => $resource), 'image/gd');
			return $Image->convert($mimeType);
		}
		return false;
	}

	function __coverArt($Media) {
		if (!empty($Media->objects['getID3']->info['id3v2']['APIC'][0]['data'])) {
			return $Media->objects['getID3']->info['id3v2']['APIC'][0]['data'];
		}
		if (!empty($Media->objects['getID3']->info['id3v2']['PIC'][0]['data'])) {
			return $Media->objects['getID3']->info['id3v2']['PIC'][0]['data'];
		}
		if (!empty($Media->objects['getID3']->info['flac']['PICTURE'][0]['image_data'])) {
			return $Media->objects['getID3']->info['flac']['PICTURE'][0]['image_data'];
		}
		if (!empty($Media->objects['getID3']->info['vorbiscomment']['coverart'][0])) {
			return base64_decode($Media->objects['getID3']->info['vorbiscomment']['coverart'][0]);
		}
		return false;
	}
}
?>